<?php

namespace brightlabs\craftsalesforce\models;

use craft\base\Model;

/**
 * Salesforce settings
 */
class Assignment extends Model
{
    public $salesforceId = null;
    public $hybridVolunteeringNature = null;
    public $workplace = null;
    public $duration = null;
    public $startDate = null;
    public $positionDescriptionUrl = null;
    public $applicationCloseDate = null;
    public $positionSummary = null;
    public $sector = null;
    public $country = null;
    public $jsonContent = null;

    protected function defineRules(): array
    {
        return [
            [
                [
                    'title',
                    'salesforceId',
                    'hybridVolunteeringNature',
                    'workplace',
                    'duration',
                    'startDate',
                    'positionDescriptionUrl',
                    'applicationCloseDate',
                    'positionSummary',
                    'sector',
                    'country',
                    'jsonContent',
                ],
                'required'
            ]
        ];
    }
}