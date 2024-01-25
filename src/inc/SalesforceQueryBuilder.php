<?php

namespace brightlabs\craftsalesforce\inc;

class SalesforceQueryBuilder
{
    protected $columns='';
    protected $table='';
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

    public function where(): SalesforceQueryBuilder
    {
        // todo: implement where
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
        . $this->table . ' LIMIT '
        . $this->limit;

        return str_replace(' ','+', $queryString);
    }
}