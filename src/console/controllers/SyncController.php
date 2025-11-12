<?php

namespace brightlabs\craftsalesforce\console\controllers;

use craft\db\Query;
use yii\console\ExitCode;
use craft\helpers\Console;
use craft\console\Controller;
use brightlabs\craftsalesforce\Salesforce;
use brightlabs\craftsalesforce\elements\Assignment;
use brightlabs\craftsalesforce\elements\Log;
use brightlabs\craftsalesforce\inc\Logs;
use brightlabs\craftsalesforce\inc\SalesforceQueryBuilder;
use Craft;
use DateTime;
use yii\db\QueryBuilder;
use craft\helpers\ElementHelper;

class SyncController extends Controller
{
    public $defaultAction = 'assignments';
    protected $salesforceApiVersion;
    protected $salesforceInstanceUrl;
    protected $salesforceClientId;
    protected $salesforceClientSecret;
    protected $salesforceToken;
    protected $salesforcePublishUpdates = [];
    protected $salesforceUnpublishUpdates = [];

    protected $json = [];

    /** Paginate through records */
    protected $maxRequestRetries = 10;
    protected $nextRecordsQuery = '';
    protected $done = true;

    /** Log */
    protected $totalRequests = 0;
    protected $totalRecords = 0;
    protected $processedRecords = 0;
    protected $updatedRecords = 0;
    protected $skippedRecords = 0;
    protected $deletedRecords = 0;
    protected $startTime = null;
    protected $endTime = null;
    protected $timeElapsed = 0;
    protected $logEntries = [];

    public function beforeAction($action): bool
    {
        $this->startTime = new DateTime();

        $this->salesforceApiVersion = Salesforce::getInstance()->settings->getSalesforceApiVersion();
        $this->salesforceInstanceUrl = Salesforce::getInstance()->settings->getSalesforceInstanceUrl();
        $this->salesforceClientId = Salesforce::getInstance()->settings->getSalesforceClientId();
        $this->salesforceClientSecret = Salesforce::getInstance()->settings->getSalesforceClientSecret();

        $this->salesforceToken = $this->getSalesforceToken();

        return parent::beforeAction($action);
    }

    /**
     * Get Salesforce bearer token
     *
     * @return string|null
     */
    protected function getSalesforceToken(): ?string
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => rtrim($this->salesforceInstanceUrl, '/') . '/services/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->salesforceClientId,
                'client_secret' => $this->salesforceClientSecret
            ]
        ));

        $response = curl_exec($curl);
        $jsonResponse = json_decode($response);

        curl_close($curl);

        try {
            $displayToken = substr($jsonResponse->access_token, 0, 7);
            if (empty($displayToken)) {
                $jsonError = json_encode($jsonResponse);
                Logs::log("Missing access token: {$jsonError}", $this->logEntries, ['fgColor' => Console::FG_RED]);
            }
            Logs::log("Retrieved access token: {$displayToken}...", $this->logEntries, ['fgColor' => Console::FG_BLUE]);
        } catch (\Throwable $th) {

            $log = new Log();
            $log->title = date('jS M Y g:i:s a');
            $log->logErrors = json_encode(
                (object) [
                    'phpError' => $th->getMessage(),
                    'salesforceResponse' => $jsonResponse,
                ]
            );
            Salesforce::getInstance()->log->saveLog($log);

            dd('Missing plugin configuration or invalid credentials.');
        }


        return $jsonResponse->access_token ?? '';
    }

    /**
     * Sync salesforce data
     */
    public function actionAssignments($nextQuery = null): int
    {
        if (empty($nextQuery)) {
            $query = new SalesforceQueryBuilder;
            $query->select([
                'Id',
                'Name',
                'Hybrid_Volunteering_Nature__c',
                'Workplace__c',
                'Duration__c',
                'Start_Date__c',
                'Position_Description_URL__c',
                'Application_Close_Date__c',
                'Position_Summary__c',
                'Base_Allowance_Figure__c',
                'Living_Allowance_Copy__c',
                'Special_Conditions_Copy__c',
                'Sector__c',
                'Country__r.Name',
                'PD_Link__c',
                'Published_Status__c',
                'LastModifiedDate',
                'Position_ID__c',
                '(SELECT Recruitment__c.Id,Recruitment__c.Name,Recruitment__c.Start_Date__c,Recruitment__c.End_Date__c,Recruitment__c.Publish__c FROM Recruitment__r WHERE Recruitment__c.Publish__c IN (\'AVP Portal (Public)\', \'AVP Portal (Private)\'))'
            ])
                ->from('Position__c')
                ->rawWhere('Id IN (SELECT Position__c FROM Recruitment__c WHERE Publish__c IN (\'AVP Portal (Public)\', \'AVP Portal (Private)\'))');

            $response = $this->query($query);
        }

        if (!empty($nextQuery) && !$this->done) {
            Logs::log("Recursion query: {$this->nextRecordsQuery}", $this->logEntries, ['fgColor' => Console::FG_BLUE]);

            $this->getSalesforceToken();

            $query = new SalesforceQueryBuilder;
            $query->setTextQuery($this->nextRecordsQuery);
            $response = $this->query($query);
        }

        $this->createAssignments($response);

        /** Recursion :) */
        if (!$this->done) {
            return $this->actionAssignments($this->nextRecordsQuery);
        }

        $this->publishAssignments();

        $this->endTime = new DateTime();
        $this->timeElapsed = $this->endTime->diff($this->startTime);

        Logs::log("Total requests: {$this->totalRequests}", $this->logEntries, ['fgColor' => Console::FG_GREEN]);
        Logs::log("Created/updated records: {$this->updatedRecords}", $this->logEntries, ['fgColor' => Console::FG_GREEN]);
        Logs::log("Deleted records: {$this->deletedRecords}", $this->logEntries, ['fgColor' => Console::FG_GREEN]);
        Logs::log("Skipped records: {$this->skippedRecords}", $this->logEntries, ['fgColor' => Console::FG_GREEN]);
        Logs::log("Time taken: {$this->timeElapsed->format('%Hh %Im %Ss')}", $this->logEntries, ['fgColor' => Console::FG_GREEN]);

        $this->logSuccess();

        return ExitCode::OK;
    }


    protected function createAssignments($response)
    {
        if (!$response->records ?? false) {
            return;
        }

        // Start database transaction
        $transaction = Craft::$app->db->beginTransaction();

        try {
            foreach ($response->records as $index => $record) {

                if ($record->Position_ID__c === null) {
                    Logs::log("({$this->processedRecords}/{$this->totalRecords}) Skipped(Missing Position_ID__c): {$record->Name} - {$record->Id}", $this->logEntries, ['fgColor' => Console::FG_PURPLE]);
                    $this->skippedRecords++;
                    continue;
                }

                $assignment = Assignment::find()
                    ->positionId($record->Position_ID__c)
                    ->one() ?? new Assignment();

                $assignment->title = $record->Name;
                $assignment->salesforceId = (string) $record->Id;
                $assignment->positionId = (string) $record->Position_ID__c;
                $assignment->hybridVolunteeringNature = (string) $record->Hybrid_Volunteering_Nature__c;
                $assignment->workplace = (string) $record->Workplace__c;
                $assignment->duration = (string) $record->Duration__c;
                $assignment->startDate = (string) $record->Start_Date__c;
                $assignment->positionDescriptionUrl = (string) $record->Position_Description_URL__c;
                $assignment->applicationCloseDate = (string) $record->Application_Close_Date__c;
                $assignment->positionSummary = (string) $record->Position_Summary__c;
                $assignment->baseAllowance = (string) $record->Base_Allowance_Figure__c;
                $assignment->livingAllowance = (string) $record->Living_Allowance_Copy__c;
                $assignment->specialConditions = (string) $record->Special_Conditions_Copy__c;
                $assignment->sector = (string) $record->Sector__c;
                $assignment->country = (string) $record->Country__r?->Name ?? '';

                $this->processedRecords++;

                // Skipping items if country is empty
                if ($this->isCountryEmpty($assignment)) {
                    continue;
                }

                // Rename country if it has parentheses
                if (stripos($assignment->country, '(') !== false) {
                    $assignment->country = trim(explode('(', $assignment->country)[0]);
                    Logs::log("({$this->processedRecords}/{$this->totalRecords}) Renamed(Country): {$record->Country__r?->Name} to {$assignment->country} - {$assignment->salesforceId}", $this->logEntries, ['fgColor' => Console::FG_YELLOW]);
                }

                if (!$assignment->id) {
                    $assignment->slug = ElementHelper::generateSlug($record->Name . ' ' . $assignment->country . ' ' . rand(100000, 999999));
                }

                // Recruitment cycle
                $recruitmentCycle = $this->getRecruitmentCycle($record->Recruitment__r);
                $assignment->recruitmentStartDate = $recruitmentCycle->start;
                $assignment->recruitmentEndDate = $recruitmentCycle->end;

                // Set empty publish status to `Draft`
                $assignment->publish = empty((string) $recruitmentCycle->publish)
                    ? 'Draft'
                    : (string) $recruitmentCycle->publish;

                // Skipping items if invalid publish type
                if ($this->isInvalidPublishType($assignment)) {
                    continue;
                }

                // Json data dump
                $this->json['Position__c'] = $record;
                $assignment->jsonContent = json_encode($this->json);

                Salesforce::getInstance()->assignment->saveAssignment($assignment);
                Logs::log("({$this->processedRecords}/{$this->totalRecords}) Processed: {$assignment->title} - {$assignment->salesforceId}:{$assignment->positionId}", $this->logEntries, ['fgColor' => Console::FG_GREEN]);

                if (!empty($record->PD_Link__c)) {
                    Logs::log("({$this->processedRecords}/{$this->totalRecords}) Existing(PD_Link__c): {$record->PD_Link__c}", $this->logEntries, ['fgColor' => Console::FG_BLUE]);
                }

                if (!empty($record->Published_Status__c)) {
                    Logs::log("({$this->processedRecords}/{$this->totalRecords}) Existing(Published_Status__c): {$record->Published_Status__c}", $this->logEntries, ['fgColor' => Console::FG_BLUE]);
                }


                if ($assignment->publish !== 'Draft') {
                    $this->salesforcePublishUpdates[] = [
                        'id' => $assignment->salesforceId,
                        'url' => $assignment->url,
                    ];
                }

                $this->updatedRecords++;
            }

            // Commit the transaction
            $transaction->commit();

        } catch (\Throwable $e) {
            // Rollback on any error
            $transaction->rollBack();

            // Log the error
            Logs::log("Transaction failed: {$e->getMessage()}", $this->logEntries, ['fgColor' => Console::FG_RED]);

            $log = new Log();
            $log->title = date('jS M Y g:i:s a');
            $log->logErrors = json_encode([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Salesforce::getInstance()->log->saveLog($log);

            throw $e; // Re-throw to stop execution
        }

    }

    private function isCountryEmpty(Assignment $assignment): bool
    {
        if (empty($assignment->country)) {
            Logs::log("({$this->processedRecords}/{$this->totalRecords}) Skipped(Country is empty): {$assignment->title} - {$assignment->salesforceId}", $this->logEntries, ['fgColor' => Console::FG_PURPLE]);
            $this->skippedRecords++;

            return true;
        }

        return false;
    }

    private function isInvalidPublishType(Assignment $assignment): bool
    {
        if (!in_array($assignment->publish, ['AVP Portal (Public)', 'AVP Portal (Private)'])) {
            Logs::log("Publish status: {$assignment->publish}", $this->logEntries, ['fgColor' => Console::FG_PURPLE]);
            Logs::log("({$this->processedRecords}/{$this->totalRecords}) Skipped(Missing publish status): {$assignment->title} - {$assignment->salesforceId}", $this->logEntries, ['fgColor' => Console::FG_PURPLE]);
            $this->skippedRecords++;

            if (!$assignment->id || $assignment->enabled == false) {
                return true;
            }

            // Disable assignment on Craft CMS
            $assignment->enabled = false;
            $assignment->publish = 'Draft';
            Salesforce::getInstance()->assignment->saveAssignment($assignment);

            $this->deletedRecords++;
            return true;
        }

        return false;
    }

    protected function publishAssignments()
    {
        if (empty($this->salesforcePublishUpdates)) {
            return;
        }

        // TODO: Unpublish records on CMS that are not in the $salesforcePublishUpdates array
        $this->unpublishAssignments();

        // TODO: Batch publish records on Salesforce
        $this->batchPublishOnSalesforce($this->salesforcePublishUpdates);

        // TODO: Batch unpublish records on Salesforce
        $this->batchUnpublishOnSalesforce($this->salesforceUnpublishUpdates);
    }

    protected function unpublishAssignments()
    {
        // Get all Salesforce IDs that should be published
        $publishedSalesforceIds = array_column($this->salesforcePublishUpdates, 'id');

        // Find assignments NOT in the published list using NOT IN query
        $assignmentsToUnpublish = Assignment::find()
            ->status('enabled')
            ->where(['not in', 'salesforceId', $publishedSalesforceIds])
            ->all();

        $unpublishedCount = 0;
        $transaction = Craft::$app->db->beginTransaction();

        try {
            foreach ($assignmentsToUnpublish as $assignment) {
                // Unpublish in Craft
                $assignment->enabled = false;
                $assignment->publish = 'Draft';
                Salesforce::getInstance()->assignment->saveAssignment($assignment);

                $this->salesforceUnpublishUpdates[] = [
                    'id' => $assignment->salesforceId
                ];

                $unpublishedCount++;
                Logs::log("Unpublished: {$assignment->title} - {$assignment->salesforceId}", $this->logEntries, ['fgColor' => Console::FG_YELLOW]);
            }

            $transaction->commit();

            Logs::log("Assignments unpublished: {$unpublishedCount}", $this->logEntries, ['fgColor' => Console::FG_GREEN]);

        } catch (\Throwable $e) {
            $transaction->rollBack();
            Logs::log("Failed to unpublish: {$e->getMessage()}", $this->logEntries, ['fgColor' => Console::FG_RED]);
            throw $e;
        }
    }

    protected function publishAssignmentOnSalesforce(Assignment $assignment)
    {
        if (Craft::$app->env == 'dev') {
            return;
        }

        $this->setField('Position__c', $assignment->salesforceId, 'PD_Link__c', $assignment->url);
        $this->setField('Position__c', $assignment->salesforceId, 'Published_Status__c', 'Published');

        Logs::log("({$this->processedRecords}/{$this->totalRecords}) Published: {$assignment->salesforceId}", $this->logEntries, ['fgColor' => Console::FG_GREY]);
        Logs::log("({$this->processedRecords}/{$this->totalRecords}) Updated(PD_Link__c): {$assignment->url}", $this->logEntries, ['fgColor' => Console::FG_BLUE]);
        Logs::log("({$this->processedRecords}/{$this->totalRecords}) Updated(Published_Status__c): Published", $this->logEntries, ['fgColor' => Console::FG_BLUE]);
    }

    protected function unpublishAssignmentOnSalesforce(Assignment $assignment)
    {
        if (Craft::$app->env == 'dev') {
            return;
        }

        $this->setField('Position__c', $assignment->salesforceId, 'PD_Link__c', '');
        $this->setField('Position__c', $assignment->salesforceId, 'Published_Status__c', 'Unpublished');
        Logs::log("({$this->processedRecords}/{$this->totalRecords}) Unpublished: {$assignment->salesforceId}", $this->logEntries, ['fgColor' => Console::FG_GREY]);
    }

    protected function getRecruitmentCycle($recruitmentObj): ?object
    {
        $validCycle = (object) [
            'start' => '',
            'end' => '',
            'publish' => '',
        ];

        if (empty($recruitmentObj)) {
            return $validCycle;
        }

        if ($recruitmentObj->totalSize > 0) {
            foreach ($recruitmentObj->records as $record) {

                $currentDate = date('Ymd');
                $startDate = date_format(date_create_from_format('Y-m-d', $record->Start_Date__c), 'Ymd');
                $endDate = date_format(date_create_from_format('Y-m-d', $record->End_Date__c), 'Ymd');

                if (
                    $currentDate >= $startDate &&
                    $currentDate <= $endDate
                ) {
                    $validCycle->start = $record->Start_Date__c;
                    $validCycle->end = $record->End_Date__c;
                    $validCycle->publish = $record->Publish__c;
                }
            }
        }

        return $validCycle;
    }

    protected function getNextRecordQuery($nextRecordsUrl = null): ?string
    {
        if (empty($nextRecordsUrl)) {
            $this->done = true;
            return $this->nextRecordsQuery;
        }

        $reversedQuery = strrev($nextRecordsUrl);
        $query = explode('/', $reversedQuery)[0];

        return strrev($query);
    }

    protected function query(SalesforceQueryBuilder $query)
    {

        $curl = curl_init();

        $appendQParameter = $query->isTextQuery() ? '' : '?q=';
        $textQuery = $appendQParameter . $query->toString();

        curl_setopt_array($curl, array(
            CURLOPT_URL => rtrim($this->salesforceInstanceUrl, '/') . '/services/data/' . $this->salesforceApiVersion . '/query/' . $textQuery,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->salesforceToken,
            ),
        ));

        $response = curl_exec($curl);

        $this->totalRequests++;

        curl_close($curl);

        $jsonResponse = json_decode($response);

        /** Only log if we are retrieving one record */
        if ($query->getLimit() <= 1) {
            $this->json[$query->getTable()] = $jsonResponse;
        }

        try {
            $jsonResponse->totalSize;

            /** Set total records using Position__c */
            if ($query->getTable() == 'Position__c' || $query->isTextQuery()) {

                $this->totalRecords = $jsonResponse->totalSize;
                $this->done = $jsonResponse->done;
                $this->nextRecordsQuery = $this->getNextRecordQuery($jsonResponse->nextRecordsUrl ?? null);
            }

            return $jsonResponse;

        } catch (\Throwable $th) {

            $log = new Log();
            $log->title = date('jS M Y g:i:s a');
            $log->logErrors = json_encode(
                (object) [
                    'phpError' => $th->getMessage(),
                    'salesforceError' => $jsonResponse,
                    'salesforceQuery' => $textQuery
                ]
            );
            Salesforce::getInstance()->log->saveLog($log);

            $error = $jsonResponse[0] ?? (object) ['errorCode' => 'MISSING_CREDENTIALS'];

            if ($error->errorCode == 'INVALID_AUTH_HEADER') {
                $this->stderr("Error: " . $jsonResponse[0]->message . "\n", Console::FG_RED);
                return;
            }

            if ($error->errorCode == 'MISSING_CREDENTIALS') {
                $this->stderr("Error: Configure Salesforce plugin.\n", Console::FG_RED);
                return;
            }

            $this->stderr("Error: {$th}", Console::FG_RED);
            dd($jsonResponse);
            exit;
        }

    }

    /**
     * Set Salesforce field
     *
     * @param string $salesforceObject Salesforce object
     * @param string $field Salesforce field name
     * @param string $key Salesforce id of the record
     * @param string $value Any value
     * @return bool
     */
    protected function setField($salesforceObject, $field, $key, $value): bool
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => rtrim($this->salesforceInstanceUrl, '/') . '/services/data/' . $this->salesforceApiVersion . '/sobjects/' . $salesforceObject . '/' . $field,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => json_encode([$key => $value]),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->salesforceToken,
            ),
        ));

        $response = curl_exec($curl);

        // Check for cURL errors
        if (curl_errno($curl)) {
            $error = curl_error($curl);
            $errorCode = curl_errno($curl);
            curl_close($curl);

            // Log the error
            Logs::log("cURL Error ({$errorCode}): {$error}", $this->logEntries, ['fgColor' => Console::FG_RED]);
            return false;
        }

        // Get HTTP status code
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // Check for HTTP errors
        if ($httpCode >= 400) {
            Logs::log("HTTP Error ({$httpCode}): {$response}", $this->logEntries, ['fgColor' => Console::FG_RED]);
            return false;
        }

        // Log success response for debugging
        Logs::log("Salesforce field update response: {$response}", $this->logEntries, ['fgColor' => Console::FG_GREEN]);

        return true;
    }

    protected function batchPublishOnSalesforce(array $updates)
    {
        if (Craft::$app->env == 'dev' || empty($updates)) {
            return;
        }

        // Salesforce Composite API allows max 200 records per request
        $batches = array_chunk($updates, 200);

        foreach ($batches as $batch) {
            $compositeRequest = [
                'allOrNone' => false,
                'records' => array_map(function ($update) {
                    return [
                        'attributes' => ['type' => 'Position__c'],
                        'Id' => $update['id'],
                        'PD_Link__c' => $update['url'],
                        'Published_Status__c' => 'Published',
                    ];
                }, $batch)
            ];

            $this->compositeUpdate($compositeRequest);
        }

        Logs::log("Batch published " . count($updates) . " assignments on Salesforce", $this->logEntries, ['fgColor' => Console::FG_GREEN]);
    }

    protected function batchUnpublishOnSalesforce(array $updates)
    {
        if (Craft::$app->env == 'dev' || empty($updates)) {
            return;
        }

        // Salesforce Composite API allows max 200 records per request
        $batches = array_chunk($updates, 200);

        foreach ($batches as $batch) {
            $compositeRequest = [
                'allOrNone' => false,
                'records' => array_map(function ($update) {
                    return [
                        'attributes' => ['type' => 'Position__c'],
                        'Id' => $update['id'],
                        'PD_Link__c' => '',
                        'Published_Status__c' => 'Unpublished',
                    ];
                }, $batch)
            ];

            $this->compositeUpdate($compositeRequest);
        }

        Logs::log("Batch unpublished " . count($updates) . " assignments on Salesforce", $this->logEntries, ['fgColor' => Console::FG_GREEN]);
    }

    protected function compositeUpdate(array $compositeRequest)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => rtrim($this->salesforceInstanceUrl, '/') . '/services/data/' . $this->salesforceApiVersion . '/composite/sobjects',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => json_encode($compositeRequest),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->salesforceToken,
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode >= 400) {
            Logs::log("Batch update failed ({$httpCode}): {$response}", $this->logEntries, ['fgColor' => Console::FG_RED]);
            return false;
        }

        return true;
    }

    protected function logSuccess()
    {
        /** Remove old logs before savings new ones */
        Logs::prune(1);

        $logString = "";

        foreach ($this->logEntries as $entry) {
            $logString .= $entry;
        }

        $log = new Log();
        $log->title = date('jS M Y g:i:s a');
        $log->logDetails = $logString;
        Salesforce::getInstance()->log->saveLog($log);
    }
}