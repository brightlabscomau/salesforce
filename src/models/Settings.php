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
    public $salesforceUsername;
    public $salesforcePassword;
    public $salesforceClientId;
    public $bearerToken;

    public function getSalesforceApiVersion(): ?string
    {
        return App::parseEnv($this->salesforceApiVersion);
    }

    public function getSalesforceInstanceUrl(): ?string
    {
        return App::parseEnv($this->salesforceInstanceUrl);
    }

    public function getSalesforceUsername(): ?string
    {
        return App::parseEnv($this->salesforceUsername);
    }

    public function getSalesforcePassword(): ?string
    {
        return App::parseEnv($this->salesforcePassword);
    }

    public function getSalesforceClientId(): ?string
    {
        return App::parseEnv($this->salesforceClientId);
    }

    public function getBearerToken(): ?string
    {
        return App::parseEnv($this->bearerToken);
    }

    public function behaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => [
                    'salesforceInstanceUrl',
                    'salesforceUsername',
                    'salesforcePassword',
                    'salesforceClientId',
                    'bearerToken',
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
                    'salesforceUsername',
                    'salesforcePassword',
                    'salesforceClientId',
                    'bearerToken'
                ],
                'required'
            ]
        ];
    }
}
