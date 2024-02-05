<?php

namespace brightlabs\craftsalesforce\elements\conditions;

use Craft;
use craft\elements\conditions\ElementCondition;

/**
 * Log condition
 */
class LogCondition extends ElementCondition
{
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            // ...
        ]);
    }
}
