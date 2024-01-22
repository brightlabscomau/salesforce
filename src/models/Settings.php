<?php

namespace brightlabs\craftsalesforce\models;

use Craft;
use craft\base\Model;

/**
 * Salesforce settings
 */
class Settings extends Model
{
    public $bearerToken = '';

    public function defineRules(): array
    {
        return [
            [['bearerToken'], 'required']
        ];
    }
}
