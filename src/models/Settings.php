<?php

namespace brightlabs\craftsalesforce\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;

/**
 * Salesforce settings
 */
class Settings extends Model
{
    public $salesforceApiVersion;
    public $salesforceInstanceUrl;
    public $salesforceClientId;
    public $salesforceClientSecret;

    public function getSalesforceApiVersion(): ?string
    {
        return App::parseEnv($this->salesforceApiVersion);
    }

    public function getSalesforceInstanceUrl(): ?string
    {
        return App::parseEnv($this->salesforceInstanceUrl);
    }

    public function getSalesforceClientId(): ?string
    {
        return App::parseEnv($this->salesforceClientId);
    }

    public function getSalesforceClientSecret(): ?string
    {
        return App::parseEnv($this->salesforceClientSecret);
    }

    public function behaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => [
                    'salesforceInstanceUrl',
                    'salesforceClientId',
                    'salesforceClientSecret',
                ],
            ],
        ];
    }

    public function defineRules(): array
    {
        return [
            [
                [
                    'salesforceInstanceUrl',
                    'salesforceClientId',
                    'salesforceClientSecret',
                ],
                'required'
            ]
        ];
    }
}
