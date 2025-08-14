<?php

namespace brightlabs\craftsalesforce\services;

use brightlabs\craftsalesforce\elements\Assignment as ElementsAssignment;
use Craft;
use yii\base\Component;
use Algolia\AlgoliaSearch\SearchClient;
use craft\helpers\App;

/**
 * Assignment service
 */
class Assignment extends Component
{
    public function getAssignmentById($assignmentId)
    {
        return Craft::$app->getElements()->getElementById($assignmentId, ElementsAssignment::class);
    }

    public function saveAssignment(ElementsAssignment $assignment)
    {

        $result = Craft::$app->elements->saveElement($assignment, true);
        $assignmentUri = $this->getAssignmentById($assignment->id)->uri;
        $assignment->uri = $assignmentUri;

        if (!empty(App::env('ALGOLIA_APPLICATION_ID'))) {

            switch ($assignment->publish) {
                case 'AVP Portal (Public)':
                    $this->createOnAlgolia($assignment);
                    break;

                case 'Draft':
                    $this->deleteOnAlgolia($assignment);
                    break;

                default:
                    $this->deleteOnAlgolia($assignment);
                    break;
            }

        }

        return $result;
    }

    public function deleteAssignment(ElementsAssignment $assignment)
    {
        if ($assignment->publish == 'AVP Portal (Public)') {
            $this->deleteOnAlgolia($assignment);
        }

        return Craft::$app->elements->deleteElement($assignment, true);
    }

    protected function createOnAlgolia(ElementsAssignment $assignment)
    {
        if (empty(App::env('ALGOLIA_APPLICATION_ID'))) {
            return;
        }

        $client = SearchClient::create(App::env('ALGOLIA_APPLICATION_ID'), App::env('ALGOLIA_ADMIN_API_KEY'));
        $index = $client->initIndex(App::env('ALGOLIA_INDEX_ASSIGNMENTS'));

        $assignmentArray = [
            'objectID' => $assignment->salesforceId,
            'title' => $assignment->title ?? '',
            'country' => $assignment->country ?? '',
            'duration' => $assignment->duration ?? '0',
            'uri' => $assignment->uri ?? '',
        ];

        $index->saveObject($assignmentArray);
    }

    protected function deleteOnAlgolia(ElementsAssignment $assignment)
    {
        if (empty(App::env('ALGOLIA_APPLICATION_ID'))) {
            return;
        }

        $client = SearchClient::create(App::env('ALGOLIA_APPLICATION_ID'), App::env('ALGOLIA_ADMIN_API_KEY'));
        $index = $client->initIndex(App::env('ALGOLIA_INDEX_ASSIGNMENTS'));
        $index->deleteObject($assignment->salesforceId);
    }
}
