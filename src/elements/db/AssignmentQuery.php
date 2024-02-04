<?php

namespace brightlabs\craftsalesforce\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

/**
 * Assignment query
 */
class AssignmentQuery extends ElementQuery
{
    public $salesforceId;
    public $country;


    public function country($value): self
    {
        $this->country = $value;

        return $this;
    }

    public function salesforceId($value): self
    {
        $this->salesforceId = $value;

        return $this;
    }

    public function types(): self
    {
        $this->groupBy = ['hybridVolunteeringNature'];

        return $this;
    }

    public function sectors(): self
    {
        $this->groupBy = ['sector'];

        return $this;
    }

    public function countries(): self
    {
        $this->groupBy = ['country'];

        return $this;
    }

    public function filterByTypes($types=[]): self
    {
        $this->andWhere(['hybridVolunteeringNature' => $types]);
        return $this;
    }

    public function filterBySectors($sectors=[]): self
    {
        $this->andWhere(['sector' => $sectors]);
        return $this;
    }

    public function filterByCountries($countries=[]): self
    {
        $this->andWhere(['country' => $countries]);
        return $this;
    }

    public function isPublic(): self
    {
        $this->andWhere(['publish' => 'AVP Portal (Public)']);
        return $this;
    }

    public function isInRecruitmentCycle(): self
    {
        return $this;
    }

    public function sortByClosingDateSoon(): self
    {
        $this->orderBy('applicationCloseDate ASC');
        return $this;
    }

    public function sortByClosingDateFurthest(): self
    {
        $this->orderBy('applicationCloseDate DESC');
        return $this;
    }

    public function sortByDurationShortest(): self
    {
        $this->orderBy('duration ASC');
        return $this;
    }

    public function sortByDurationLongest(): self
    {
        $this->orderBy('duration DESC');
        return $this;
    }

    public function sortByCountry(): self
    {
        $this->orderBy('country ASC');
        return $this;
    }

    public function sortBySector(): self
    {
        $this->orderBy('sector ASC');
        return $this;
    }

    protected function beforePrepare(): bool
    {
        // todo: join the `assignments` table
        $this->joinElementTable('salesforce_assignments');

        $this->query->select([
            'salesforce_assignments.salesforceId',
            'salesforce_assignments.hybridVolunteeringNature',
            'salesforce_assignments.workplace',
            'salesforce_assignments.duration',
            'salesforce_assignments.startDate',
            'salesforce_assignments.positionDescriptionUrl',
            'salesforce_assignments.applicationCloseDate',
            'salesforce_assignments.positionSummary',
            'salesforce_assignments.baseAllowance',
            'salesforce_assignments.livingAllowance',
            'salesforce_assignments.specialConditions',
            'salesforce_assignments.sector',
            'salesforce_assignments.country',
            'salesforce_assignments.publish',
            'salesforce_assignments.recruitmentStartDate',
            'salesforce_assignments.recruitmentEndDate',
            'salesforce_assignments.jsonContent',
        ]);

        // todo: apply any custom query params
        if ($this->salesforceId) {
            $this->subQuery->andWhere(Db::parseParam('salesforce_assignments.salesforceId', $this->salesforceId));
        }

        if ($this->country) {
            $this->subQuery->andWhere(Db::parseParam('salesforce_assignments.country', $this->country));
        }

        return parent::beforePrepare();
    }
}
