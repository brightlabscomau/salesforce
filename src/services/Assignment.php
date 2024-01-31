<?php

namespace brightlabs\craftsalesforce\services;

use brightlabs\craftsalesforce\elements\Assignment as ElementsAssignment;
use Craft;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use Dompdf\FrameDecorator\Table;
use yii\base\Component;

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
        return Craft::$app->elements->saveElement($assignment, true);
    }

    public function deleteAssignment(ElementsAssignment $assignment)
    {
        return Craft::$app->elements->deleteElement($assignment, true);
    }
}
