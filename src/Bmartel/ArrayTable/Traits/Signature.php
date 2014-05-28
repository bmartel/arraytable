<?php

namespace Bmartel\ArrayTable\Traits;

use Bmartel\ArrayTable\Exceptions\SignatureDefinitionNotFound;

trait Signature{

    /**
     * Performs the signature generation based on the json string
     * definition.
     *
     * @return string
     * @throws \Bmartel\ArrayTable\Exceptions\SignatureDefinitionNotFound
     */
    public function generateSignature()
    {
        $callable = method_exists($this,'definition');

        $signature = ($callable) ? $this->definition() : null;

        if(empty($signature)) {
            throw new SignatureDefinitionNotFound('Definition must be a defined function and return a value');
        }

        $this->sortData($signature);

        return md5(json_encode($signature));
    }

    /**
     * Sort array data
     *
     * @param $data
     */
    protected function sortData(&$data) {
        if(is_array($data)) {
            ksort($data);
        }
    }
} 