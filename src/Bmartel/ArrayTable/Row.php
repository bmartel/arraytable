<?php namespace Bmartel\ArrayTable;


class Row
{
    /**
     * @var \Bmartel\ArrayTable\Column[]
     */
    protected $columns;

    public function __construct($columns)
    {
        if (!is_null($columns)) {
            $this->columns = is_array($columns) ? $columns : [$columns];
        }

    }


} 