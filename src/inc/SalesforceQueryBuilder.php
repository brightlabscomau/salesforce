<?php

namespace brightlabs\craftsalesforce\inc;

class SalesforceQueryBuilder
{
    protected $columns='';
    protected $table='';
    protected $where=[];
    protected $limit='';

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

    public function toString(): string
    {
        $queryString = 'SELECT '
        . $this->columns . ' FROM '
        . $this->table . ' '
        . implode('AND', $this->where)
        . ' LIMIT '
        . $this->limit;

        return str_replace(' ','+', $queryString);
    }

    public function getTable(): ?string
    {
        return $this->table;
    }
}