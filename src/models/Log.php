<?php

namespace brightlabs\craftsalesforce\models;

use craft\base\Model;

/**
 * Salesforce settings
 */
class Log extends Model
{
    public $logDetails = null;
    public $logErrors = null;

    protected function defineRules(): array
    {
        return [
            [
                ['logDetails', 'logErrors'],
                'required'
            ]
        ];
    }
}