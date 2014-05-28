<?php
namespace Bmartel\ArrayTable\Contracts;


interface SignatureInterface {

    /**
     * This method must return some form of data, collection, or object
     * for which the signature can be generated from.
     *
     * @return mixed
     */
    public function definition();

    /**
     * Performs the signature generation based on the json string
     * definition.
     *
     * @return mixed
     */
    public function generateSignature();

    /**
     * Returns the current signature
     *
     * @return mixed
     */
    public function signature();
} 