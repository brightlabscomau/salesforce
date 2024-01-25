<?php

namespace brightlabs\craftsalesforce\models;

use Craft;
use craft\base\Model;

/**
 * Salesforce settings
 */
class Assignment extends Model
{
    public $salesforceId = null;
    public $country = null;
    public $jsonContent = null;

    protected function defineRules(): array
    {
        return [
            [
                [
                    'title',
                    'salesforceId',
                    'country',
                    'jsonContent',
                ],
                'required'
            ]
        ];
    }
}