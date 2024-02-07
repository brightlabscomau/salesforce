<?php

namespace brightlabs\craftsalesforce\elements\db;

use Craft;
use craft\elements\db\ElementQuery;

/**
 * Log query
 */
class LogQuery extends ElementQuery
{
    protected function statusCondition(string $status): mixed
    {
        switch($status) {
            case 'success':
                return ['salesforce_logs.logDetails' => null];
            case  'failed':
                return ['salesforce_logs.logErrors' => null];
            default:
                return parent::statusCondition($status);
        }
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('salesforce_logs');

        $this->query->select([
            'salesforce_logs.logDetails',
            'salesforce_logs.logErrors',
        ]);

        return parent::beforePrepare();
    }
}
