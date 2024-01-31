<?php

namespace brightlabs\craftsalesforce\console\controllers;

use craft\db\Query;
use yii\console\ExitCode;
use craft\helpers\Console;
use craft\console\Controller;
use brightlabs\craftsalesforce\Salesforce;
use brightlabs\craftsalesforce\elements\Assignment;
use brightlabs\craftsalesforce\inc\SalesforceQueryBuilder;

class SyncController extends Controller
{
    public $defaultAction = 'assignments';
    protected $salesforceApiVersion;
    protected $salesforceInstanceUrl;
    protected $salesforceClientId;
    protected $salesforceClientSecret;
    protected $salesforceToken;

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

    public function beforeAction($action): bool
    {

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

        $displayToken = substr($jsonResponse->access_token, 0, 7);
        $this->stdout("Retrieved access token: {$displayToken}... \n", Console::FG_BLUE);

        return $jsonResponse->access_token ?? '';
    }

    /**
     * Sync salesforce data
     */
    public function actionAssignments($nextQuery=null): int
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
                'Sector__c',
                'Country__r.Name',
                'LastModifiedDate',
                '(SELECT Recruitment__c.Id,Recruitment__c.Name,Recruitment__c.Start_Date__c,Recruitment__c.End_Date__c,Recruitment__c.Publish__c FROM Recruitment__r)'
            ])
            ->from('Position__c');


            $response = $this->query($query);
        }

        if (!empty($nextQuery) && !$this->done)
        {
            $this->stdout("Recursion query: {$this->nextRecordsQuery} \n", Console::FG_BLUE);

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

        $this->stdout("Total requests: {$this->totalRequests} \n", Console::FG_GREEN);
        $this->stdout("Created/updated records: {$this->updatedRecords} \n", Console::FG_GREEN);
        $this->stdout("Deleted records: {$this->deletedRecords} \n", Console::FG_GREEN);
        $this->stdout("Skipped records: {$this->skippedRecords} \n", Console::FG_GREEN);

        return ExitCode::OK;
    }


    protected function createAssignments($response)
    {
        if (!$response->records ?? false) {
            return;
        }

        foreach ($response->records as $index => $record) {

            $id = (new Query())
            ->select(['id'])
            ->from(['salesforce_assignments'])
            ->where(['salesforceId' => $record->Id])
            ->scalar();

            if (!empty($id)) {
                $assignment = Salesforce::getInstance()->assignment->getAssignmentById($id);
            } else {
                $assignment = new Assignment();
            }

            $assignment->title = $record->Name;
            $assignment->salesforceId = (string) $record->Id;
            $assignment->hybridVolunteeringNature = (string) $record->Hybrid_Volunteering_Nature__c;
            $assignment->workplace = (string) $record->Workplace__c;
            $assignment->duration = (string) $record->Duration__c;
            $assignment->startDate = (string) $record->Start_Date__c;
            $assignment->positionDescriptionUrl = (string) $record->Position_Description_URL__c;
            $assignment->applicationCloseDate = (string) $record->Application_Close_Date__c;
            $assignment->positionSummary = (string) $record->Position_Summary__c;
            $assignment->sector = (string) $record->Sector__c;
            $assignment->country = (string) $record->Country__r?->Name ?? '';

            $this->processedRecords++;

            // Skipping items if country has parenthesis
            if (
                stripos($assignment->country, '(') !== false ||
                empty($assignment->country)
            ) {
                $this->stdout("({$this->processedRecords}/{$this->totalRecords}) Skipped(Country has parenthesis): {$assignment->title} - {$assignment->salesforceId} \n", Console::FG_PURPLE);
                $this->skippedRecords++;

                if (empty($id)) {
                    continue;
                }

                Salesforce::getInstance()->assignment->deleteAssignment($assignment);
                $this->deletedRecords++;
                continue;
            }

            // Recruitment cycle
            $recruitmentCycle = $this->getRecruitmentCycle($record->Recruitment__r);
            $assignment->recruitmentStartDate = $recruitmentCycle->start;
            $assignment->recruitmentEndDate = $recruitmentCycle->end;

            // Skipping items if invalid recruitment cycle
            if (empty($assignment->recruitmentStartDate) || empty($assignment->recruitmentEndDate)) {
                $this->stdout("({$this->processedRecords}/{$this->totalRecords}) Skipped(Invalid recruitment cycle): {$assignment->title} - {$assignment->salesforceId} \n", Console::FG_PURPLE);
                $this->skippedRecords++;

                if (empty($id)) {
                    continue;
                }

                Salesforce::getInstance()->assignment->deleteAssignment($assignment);
                $this->deletedRecords++;
                continue;
            }

            // Publish status
            $assignment->publish = (string) $recruitmentCycle->publish;

            // Skipping items if invalid publish type
            if (!in_array($assignment->publish, ['AVP Portal (Public)', 'AVP Portal (Private)'])) {
                $this->stdout("({$this->processedRecords}/{$this->totalRecords}) Skipped(Missing publish status): {$assignment->title} - {$assignment->salesforceId} \n", Console::FG_PURPLE);
                $this->skippedRecords++;

                if (empty($id)) {
                    continue;
                }

                Salesforce::getInstance()->assignment->deleteAssignment($assignment);
                $this->deletedRecords++;
                continue;
            }

            // Json data dump
            $this->json['Position__c'] = $record;
            $assignment->jsonContent = json_encode($this->json);

            Salesforce::getInstance()->assignment->saveAssignment($assignment);
            $this->stdout("({$this->processedRecords}/{$this->totalRecords}) Processed: {$assignment->title} - {$assignment->salesforceId} \n", Console::FG_GREEN);
            $this->updatedRecords++;
        }


    }

    protected function getRecruitmentCycle($recruitmentObj): ?object
    {
        $validCycle = (object)[
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
                $startDate = date_format(date_create_from_format('Y-m-d',  $record->Start_Date__c), 'Ymd');
                $endDate = date_format(date_create_from_format('Y-m-d',  $record->End_Date__c), 'Ymd');

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

    protected function getNextRecordQuery($nextRecordsUrl=null): ?string
    {
        if (empty($nextRecordsUrl)) {
            $this->done = true;
            return $this->nextRecordsQuery;
        }

        $reversedQuery = strrev($nextRecordsUrl);
        $query = explode('/', $reversedQuery)[0];

        return strrev($query);
    }

    protected function query(SalesforceQueryBuilder $query) {

        $curl = curl_init();

        $appendQParameter = $query->isTextQuery()? '' : '?q=';
        $textQuery = $appendQParameter . $query->toString();

        curl_setopt_array($curl, array(
            CURLOPT_URL => rtrim($this->salesforceInstanceUrl, '/') . '/services/data/'. $this->salesforceApiVersion .'/query/' . $textQuery,
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

            $error = $jsonResponse[0] ?? (object)['errorCode' => 'MISSING_CREDENTIALS'];

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
}