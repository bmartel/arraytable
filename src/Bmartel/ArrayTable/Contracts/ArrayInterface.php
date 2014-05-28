<?php
namespace Bmartel\ArrayTable\Contracts;


interface ArrayInterface {

    /**
     * Method for retrieving data objects as arrays
     *
     * @return array
     */
    public function toArray();
} 