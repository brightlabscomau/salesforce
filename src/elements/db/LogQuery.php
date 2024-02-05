<?php

namespace brightlabs\craftsalesforce\elements\db;

use Craft;
use craft\elements\db\ElementQuery;

/**
 * Log query
 */
class LogQuery extends ElementQuery
{
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
