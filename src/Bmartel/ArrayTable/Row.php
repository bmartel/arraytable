<?php
namespace Bmartel\ArrayTable;


use Bmartel\ArrayTable\Contracts\ArrayInterface;
use Bmartel\ArrayTable\Contracts\SignatureInterface;
use Bmartel\ArrayTable\Exceptions\ColumnsNotDefinedException;
use Bmartel\ArrayTable\Traits\Signature;

class Row implements SignatureInterface, ArrayInterface
{

    use Signature;

    protected $columns = [];
    protected $attributes = [];

    protected $signature;

    function __construct(array $columns = null)
    {
        $this->setColumns($columns);
    }

    public function setColumns($columns)
    {
        if(is_array($columns) && count($columns) > 0){

            // Are the array keys valid column keys?
            $keys = array_keys($columns);
            $isColumnsWithData = true;

            foreach($keys as $key) {
                if(is_numeric($key)) {
                    $isColumnsWithData = false;
                    break;
                }
            }

            // Add columns with column data
            if($isColumnsWithData){
                $this->columns = $columns;
            }
            // Check if the array values are valid column keys
            else {
                $isColumnsWithData = true;
                $keys = array_values($columns);

                foreach($keys as $key) {
                    if(is_numeric($key)) {
                        $isColumnsWithData = false;
                        break;
                    }
                }

                // Assign the array values as column keys, with empty data
                if($isColumnsWithData) {
                    $this->columns = array_fill_keys($keys, '');
                }
            }
        }

        // Generate a signature for this rows columns
        $this->signature = $this->generateSignature();
    }

    /**
     * This method must return some form of data, collection, or object
     * for which the signature can be generated from.
     *
     * @return mixed
     */
    public function definition()
    {
        return array_keys($this->columns);
    }

    /**
     * Returns the current signature
     *
     * @return mixed
     */
    public function signature()
    {
        return $this->signature;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the Row.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    public function __isset($name)
    {
        return isset($this->columns[$name]) || isset($this->attributes[$name]);
    }

    public function __unset($name)
    {
        unset($this->columns[$name]);
        unset($this->attributes[$name]);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this, $name), $arguments);
    }

    private function getAttribute($key)
    {
        $attribute = null;

        if (array_key_exists($key, $this->columns)) {
            $attribute = $this->columns[$key];
        } elseif (array_key_exists($key, $this->attributes)) {
            $attribute = $this->attributes[$key];
        }

        return $attribute;
    }

    private function setAttribute($key, $value)
    {
        if (array_key_exists($key, $this->columns)) {
            $this->columns[$key] = $value;
        } else {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * Method for retrieving data objects as arrays
     *
     * @return array
     */
    public function toArray()
    {
        return $this->columns;
    }


} 