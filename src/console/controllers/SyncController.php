<?php

namespace brightlabs\craftsalesforce\console\controllers;

use brightlabs\craftsalesforce\elements\Assignment;
use brightlabs\craftsalesforce\Salesforce;
use craft\console\Controller;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Console;
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
            ->select(['elementId'])
            ->from([Table::CONTENT])
            ->where(['title' => $record->Name])
            ->scalar();
            dd($id);
            $assignment = Salesforce::getInstance()->assignment->getAssignmentById($id);

            if (empty($assignment)) {

            }
            dd($assignment);

            // if (empty($assignment)) {
            //     $assignment = new Assignment();
            // }

            // $assignment->title = $record->Name;

            // Salesforce::getInstance()->assignment->saveAssignment($assignment);

            // if ($index > 4) {
            //     break;
            // }
        }
    }

    protected function fetchData($query=null) {
        $bearerToken = Salesforce::getInstance()->settings->getBearerToken();

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://australianvolunteers--uatpc2.sandbox.my.salesforce.com/services/data/v60.0/query/?q=SELECT+Name+FROM+Recruitment__c',
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

        return json_decode($response);
    }
}