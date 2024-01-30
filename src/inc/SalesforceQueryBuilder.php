<?php

namespace brightlabs\craftsalesforce\inc;

class SalesforceQueryBuilder
{
    protected $columns='';
    protected $table='';
    protected $where=[];
    protected $limit=null;
    protected $textQuery=null;

    public function select($columns=[]): SalesforceQueryBuilder
    {
        $this->columns = implode(',', $columns);
        return $this;
    }

    public function from($table=''): SalesforceQueryBuilder
    {
        $this->table = $table;
        return $this;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function where($column='', $comparator='', $value): SalesforceQueryBuilder
    {
        $this->where[] = "WHERE {$column} {$comparator} '{$value}'";
        return $this;
    }

    public function limit($limit=1): SalesforceQueryBuilder
    {
        $this->limit = $limit;
        return $this;
    }

    protected function limitToString(): ?string
    {
        if (empty($this->limit)) {
            return '';
        }

        return "LIMIT {$this->limit}";
    }

    public function getLimit(): ?string
    {
        return $this->limit;
    }

    public function setTextQuery($query): SalesforceQueryBuilder
    {
        $this->textQuery = $query;
        return $this;
    }

    public function isTextQuery(): bool
    {
        return !empty($this->textQuery);
    }

    public function toString(): string
    {
        if(!empty($this->textQuery)) {
            return $this->textQuery;
        }

        $queryString = 'SELECT '
        . $this->columns . ' FROM '
        . $this->table . ' '
        . implode('AND', $this->where)
        . $this->limitToString();

        return trim(str_replace(' ','+', $queryString), '+');
    }
}