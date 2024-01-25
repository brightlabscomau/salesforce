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

    protected function beforePrepare(): bool
    {
        // todo: join the `assignments` table
        $this->joinElementTable('salesforce_assignments');

        $this->query->select([
            'salesforce_assignments.salesforceId',
            'salesforce_assignments.country',
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
