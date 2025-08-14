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
    public $baseAllowance = null;
    public $livingAllowance = null;
    public $sector = null;
    public $country = null;
    public $publish = null;
    public $recruitmentStartDate = null;
    public $recruitmentEndDate = null;
    public $jsonContent = null;
    public $filterCountry = null;
    public $filterSector = null;
    public $filterTheme = null;
    public $filterStories = null;

    protected function defineRules(): array
    {
        return [
            [
                [
                    'title',
                    'salesforceId',
                    'positionId',
                    'hybridVolunteeringNature',
                    'workplace',
                    'duration',
                    'startDate',
                    'positionDescriptionUrl',
                    'applicationCloseDate',
                    'positionSummary',
                    'baseAllowance',
                    'livingAllowance',
                    'sector',
                    'country',
                    'publish',
                    'recruitmentStartDate',
                    'recruitmentEndDate',
                    'jsonContent',
                    'filterCountry',
                    'filterSector',
                    'filterTheme',
                    'filterStories'
                ],
                'required'
            ]
        ];
    }
}