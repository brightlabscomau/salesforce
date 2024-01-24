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

class SyncController extends Controller
{
    public $defaultAction = 'assignments';

    /**
     * Sync salesforce data
     */
    public function actionAssignments(): int
    {
        $response = $this->fetchData();

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

    // protected function

    protected function fetchData($query=null) {
        $bearerToken = Salesforce::getInstance()->settings->getBearerToken();

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://australianvolunteers--uatpc2.sandbox.my.salesforce.com/services/data/v60.0/query/?q=SELECT+Id,Name+FROM+Recruitment__c',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $bearerToken,
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $jsonResponse = json_decode($response);

        if ($jsonResponse->totalSize ?? false) {
            return $jsonResponse;
        }


        if (
            ($jsonResponse[0]->errorCode ?? false) &&
            $jsonResponse[0]->errorCode == 'INVALID_AUTH_HEADER'
        ) {
            $this->stderr("Error: " . $jsonResponse[0]->message . "\n", Console::FG_RED);

            if ($jsonResponse[0]->errorCode == 'INVALID_AUTH_HEADER') {
                $this->stderr("Hint: Check if you've provided bearer token and it is valid.\n\n", Console::FG_YELLOW);
            }


            throw new BaseException($jsonResponse[0]->message);
        }

    }
}