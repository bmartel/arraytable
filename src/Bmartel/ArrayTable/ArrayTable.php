<?php namespace Bmartel\ArrayTable;

use Bmartel\ArrayTable\Exceptions\RowDataException;
use Closure;
use Countable;
use ArrayAccess;
use ArrayIterator;
use CachingIterator;
use IteratorAggregate;
use Mockery\Generator\Parameter;
use Traversable;
use Serializable;
use JsonSerializable;

/**
 * Class ArrayTable
 * @package Bmartel\ArrayTable
 */
class ArrayTable implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    protected static $keyspace;

    protected $rows;

    protected $columns;

    protected $metadata;

    protected $name;

    public function __construct(array $columns, $name = null)
    {
        $this->name = $name ?: UUID::v4();
        $this->columns = $columns;
        $this->rows = [];

        if(!isset(static::$keyspace)) static::generateTableKey();
    }

    /**
     * Regenerates the overall keyspace for the tables
     */
    protected static function generateTableKey()
    {
        static::$keyspace = UUID::v4();
    }

    /**
     * Generates a new key for the current table
     *
     * @return bool|string
     */
    public function generateKey()
    {
        return UUID::v5(static::$keyspace,get_called_class());
    }
    /**
     * Chainable alias of fillRowWithData
     *
     * @param array $rowData
     * @param null $rowKey
     * @return $this
     */
    public function newRow(array $rowData = [], $rowKey = null)
    {
        $this->fillRowWithData($rowData, $rowKey);

        return $this;
    }

    /**
     * Alias for countable method count()
     *
     * @return int
     */
    public function rowCount()
    {
        return $this->count();
    }

    public function get()
    {

    }

    /**
     * Perform a callback on each row
     *
     * @param callable $callback
     * @return $this
     */
    public function each(Closure $callback)
    {
        array_map($callback, $this->rows);

        return $this;
    }

    /**
     * Retrieve the current name of the table
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->name;
    }

    /**
     * Fill a row with data
     *
     * @param array $rowData
     * @param null $rowKey
     */
    public function fillRowWithData(array $rowData = [], $rowKey = null)
    {
        // Cut a blank row
        $newRow = $this->newEmptyRow($rowKey);

        // Return if the row requires no data
        if(empty($rowData)) return;

        // Ensure the data array is able to fill the row
        $this->ableToFillRow($rowData);

        // Fill the data according to the fields specified
        if ($this->hasColumnKeys($rowData)) {
            $this->fillRow($newRow,$rowData);
        }

        // Treat the array as sequential values, populate data in the order it was declared
        else {
            $this->fillRowFromRaw($newRow,$rowData);
        }
    }

    /**
     * Creates a new empty row
     */
    protected function newEmptyRow($rowKey = null)
    {

        $rowKey = $rowKey ?: $this->generateKey();

        $this->rows[$rowKey] = array_fill_keys($this->columns,'');

        return $rowKey;
    }

    /**
     * Fill row data from a column-keyed associative array
     *
     * @param $rowKey
     * @param array $data
     */
    protected function fillRow($rowKey, array $data)
    {
        $row = $this->getRowByKey($rowKey);

        $filled = array_intersect_key($data,$row);

        $this->rows[$rowKey] = array_merge($row, $filled);

    }

    /**
     * Fill row data from a sequential array
     *
     * @param $rowKey
     * @param array $data
     */
    protected function fillRowFromRaw($rowKey, array $data)
    {
        $data = array_combine(array_slice($this->columns,0,count($data)),array_values($data));

        $this->fillRow($rowKey, $data);
    }

    /**
     * Retrieve the row by its key
     *
     * @param $key
     * @return null
     */
    public function getRowByKey($key)
    {
        if($this->offsetExists($key)) return $this->rows[$key];

        return null;
    }

    /**
     * Determine if the row can be filled by the data array
     *
     * @param array $data
     * @return bool
     * @throws Exceptions\RowDataException
     */
    protected function ableToFillRow(array $data)
    {
        $result = count($data) <= count($this->columns);

        if(!$result) throw new RowDataException('Row offset does not exist.');

        return $result;
    }

    /**
     * Determine if the data array keys exist as column names in the table
     *
     * @param array $data
     * @return bool
     */
    public function hasColumnKeys(array $data)
    {
        return count($data) === count(array_intersect(array_keys($data), $this->columns));
    }

    /**
     * Retrieve the table metadata
     *
     * @return array
     */
    public function getMetaData()
    {
        if(!isset($this->metadata['name'])) $this->metadata['name'] = $this->name;

        return $this->metadata;
    }

    public function exportTable()
    {
        return ['metadata' => $this->getMetaData()] + ['columns' => $this->columns] + ['data' => $this->rows];
    }

    /**
     * Get an iterator for the rows.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->rows);
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->rows);
    }

    /**
     * Determine if a row exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->rows);
    }


    /**
     * Get a row at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->rows[$key];
    }

    /**
     * Set the row at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key))
        {
            $this->rows[] = $value;
        }
        else
        {
            $this->rows[$key] = $value;
        }
    }

    /**
     * Unset the row at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->rows[$key]);
    }

    /**
     * Get the table as JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->exportTable(), $options);
    }

    /**
     * Set the table data to be serialized as JSON
     *
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return $this->exportTable();
    }


} 