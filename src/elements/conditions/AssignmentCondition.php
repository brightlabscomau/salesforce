<?php

namespace brightlabs\craftsalesforce\elements\conditions;

use Craft;
use craft\elements\conditions\ElementCondition;

/**
 * Assignment condition
 */
class AssignmentCondition extends ElementCondition
{
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            // ...
        ]);
    }
}
