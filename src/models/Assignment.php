<?php

namespace brightlabs\craftsalesforce\models;

use Craft;
use craft\base\Model;

/**
 * Salesforce settings
 */
class Assignment extends Model
{
    public $country = null;
    public $salesforce_id = null;

    protected function defineRules(): array
    {
        return [
            [['title', 'country', 'salesforce_id'], 'required']
        ];
    }
}