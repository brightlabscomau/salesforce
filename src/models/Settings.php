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
    public $bearerToken;

    public function getBearerToken(): ?string
    {
        return App::parseEnv($this->bearerToken);
    }

    public function behaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => ['bearerToken'],
            ],
        ];
    }

    public function defineRules(): array
    {
        return [
            [['bearerToken'], 'required']
        ];
    }
}
