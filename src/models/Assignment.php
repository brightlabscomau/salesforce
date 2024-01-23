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

    protected function defineRules(): array
    {
        return [
            [['country', 'title'], 'required']
        ];
    }
}