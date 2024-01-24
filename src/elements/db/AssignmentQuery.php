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
    public $salesforce_id;
    public $country;


    public function country($value): self
    {
        $this->country = $value;

        return $this;
    }

    public function salesforceId($value): self
    {
        $this->salesforce_id = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        // todo: join the `assignments` table
        $this->joinElementTable('salesforce_assignments');

        $this->query->select([
            'salesforce_assignments.salesforce_id',
            'salesforce_assignments.country',
        ]);

        // todo: apply any custom query params
        if ($this->salesforce_id) {
            $this->subQuery->andWhere(Db::parseParam('salesforce_assignments.salesforce_id', $this->salesforce_id));
        }

        if ($this->country) {
            $this->subQuery->andWhere(Db::parseParam('salesforce_assignments.country', $this->country));
        }

        return parent::beforePrepare();
    }
}
