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
    protected $salesforceUsername;
    protected $salesforcePassword;
    protected $salesforceClientId;
    protected $bearerToken;

    protected $maxRequestRetries = 10;

    public function beforeAction($action): bool
    {

        $this->salesforceApiVersion = Salesforce::getInstance()->settings->getSalesforceApiVersion();
        $this->salesforceInstanceUrl = Salesforce::getInstance()->settings->getSalesforceInstanceUrl();
        $this->salesforceUsername = Salesforce::getInstance()->settings->getSalesforceUsername();
        $this->salesforcePassword = Salesforce::getInstance()->settings->getSalesforcePassword();
        $this->salesforceClientId = Salesforce::getInstance()->settings->getSalesforceClientId();
        $this->bearerToken = Salesforce::getInstance()->settings->getBearerToken();

        return parent::beforeAction($action);
    }

    /**
     * Sync salesforce data
     */
    public function actionAssignments(): int
    {

        $query = new SalesforceQueryBuilder;
        $query->select([
            'Id',
            'Name'
        ])
        ->from('Position__c')
        ->limit(200);

        $response = $this->query($query);

        $this->createAssignments($response);

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
            ->where(['salesforce_id' => $record->Id])
            ->scalar();

            if (!empty($id)) {
                $assignment = Salesforce::getInstance()->assignment->getAssignmentById($id);
            } else {
                $assignment = new Assignment();
            }

            $assignment->title = $record->Name;
            $assignment->salesforce_id = (string) $record->Id;
            $assignment->country = (string) 'Australia';

            Salesforce::getInstance()->assignment->saveAssignment($assignment);

            if ($index > 4) {
                break;
            }
        }
    }

    protected function authorise() {
        // todo: implement authentication
        // $this->bearerToken = 'new token';
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
                'Authorization: Bearer ' . $this->bearerToken,
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $jsonResponse = json_decode($response);


        try {
            $jsonResponse->totalSize;

            return $jsonResponse;

        } catch (\Throwable $th) {
            $error = $jsonResponse[0];

            if ($error->errorCode == 'INVALID_AUTH_HEADER') {
                $this->stderr("Error: " . $jsonResponse[0]->message . "\n", Console::FG_RED);
                $this->stderr("Hint: Check if you've provided bearer token and it is valid.\n\n", Console::FG_YELLOW);
            }

            exit;
        }

    }
}