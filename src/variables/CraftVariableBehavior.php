<?php

namespace brightlabs\craftsalesforce\variables;

use brightlabs\craftsalesforce\elements\Assignment;
use brightlabs\craftsalesforce\elements\db\AssignmentQuery;
use Craft;
use yii\base\Behavior;

class CraftVariableBehavior extends Behavior
{
    public function assignments(array $criteria = []): AssignmentQuery
    {
        return Craft::configure(Assignment::find(), $criteria);
    }
}