<?php

namespace brightlabs\craftsalesforce\console\controllers;

use brightlabs\craftsalesforce\elements\Assignment;
use brightlabs\craftsalesforce\Salesforce;
use craft\console\Controller;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Console;
use yii\base\Exception as BaseException;
use yii\console\Exception;
use yii\console\ExitCode;
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

    protected $maxRequestRetries = 10;

    /** Log */
    protected $totalRequests = 0;

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

        curl_close($curl);

        return json_decode($response)->access_token ?? '';
    }

    /**
     * Sync salesforce data
     */
    public function actionAssignments(): int
    {
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
        ])
        ->from('Position__c')
        ->limit(2);

        $response = $this->query($query);

        $this->createAssignments($response);

        $this->stdout("Total requests: {$this->totalRequests} \n", Console::FG_GREEN);

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

            // Json data dump
            $assignment->jsonContent = json_encode($this->json);

            Salesforce::getInstance()->assignment->saveAssignment($assignment);

        }
    }

    protected function query(SalesforceQueryBuilder $query) {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => rtrim($this->salesforceInstanceUrl, '/') . '/services/data/'. $this->salesforceApiVersion .'/query/?q=' . $query->toString(),
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
        $this->json[$query->getTable()] = $jsonResponse;

        try {
            $jsonResponse->totalSize;

            return $jsonResponse;

        } catch (\Throwable $th) {
            $error = $jsonResponse[0] ?? (object)['errorCode' => 'MISSING_CREDENTIALS'];

            if ($error->errorCode == 'INVALID_AUTH_HEADER') {
                $this->stderr("Error: " . $jsonResponse[0]->message . "\n", Console::FG_RED);
            }

            if ($error->errorCode == 'MISSING_CREDENTIALS') {
                $this->stderr("Error: Configure Salesforce plugin.\n", Console::FG_RED);
            }

            exit;
        }

    }
}