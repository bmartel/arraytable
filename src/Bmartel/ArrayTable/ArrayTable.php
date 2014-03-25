<?php namespace Bmartel\ArrayTable;

use Bmartel\ArrayTable\Exceptions\RowDataException;
use Closure;
use Countable;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;

/**
 * Class ArrayTable
 *
 * @package Bmartel\ArrayTable
 */
class ArrayTable implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{

    protected static $keyspace;

    protected $tableclass;

    protected $rows;

    protected $columns;

    protected $metadata;

    protected $name;

    protected $searchResults;

    protected $searchCriteria;

    protected $searchFlag;

    /**
     * @param array $columns
     * @param null $name
     */
    public function __construct(array $columns, $name = null)
    {

        $this->name = $name ? : UUID::v4();
        $this->columns = $columns;
        $this->rows = [];
        $this->searchResults = [];
        $this->searchCriteria = [];
        $this->searchFlag = false;

        $this->updateTableClass();
        $this->updateKeyspace();
    }

    /**
     * Returns a new instance of ArrayTable
     *
     * @param array $columns
     * @param null $name
     * @return static
     */
    public static function make(array $columns, $name = null)
    {

        return new static($columns, $name);
    }

    /**
     * Populates array table with parallel arrays, overwrites current rows
     *
     * @param array $array1
     * @param array $array2
     * @param array [$array3...$arrayN]
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function populate(array $array1, array $array2, array $array3 = null)
    {

        $columns = $this->columns;
        $rowData = func_get_args();

        // Ensure argument count is satisfied
        if (count($columns) !== count($rowData)) {
            throw new \InvalidArgumentException('Array arguments specified should be equal to number of columns in the table.');
        }

        $rows = [];

        // Reorder the data to take parallel arrays into keyed table rows
        for ($i = 0; $i < count($rowData[0]); $i++) {
            $row = [];
            foreach ($rowData as $column) {
                $row[] = $column[$i];
            }
            $rows[] = array_combine($columns, $row);

        }

        //Overwrite the current table rows
        $this->setRows($rows);

        return $this;

    }

    /**
     * Updates the keyspace to the current table
     */
    protected function updateKeyspace()
    {

        if ($this->tableKeyspaceNeedsUpdate()) {
            $this->generateTableKey();
        }
    }

    /**
     * Updates the table class name to the current executed classname
     */
    protected function updateTableClass()
    {

        if ($this->tableClassNeedsUpdate()) {
            $this->tableclass = static::getTableClass();
        }
    }

    /**
     * Check the table class has been set
     *
     * @return bool
     */
    protected function tableClassNeedsUpdate()
    {

        return !isset($this->tableclass);
    }

    /**
     * Check if the keyspace for the table exists
     *
     * @return bool
     */
    protected function tableKeyspaceNeedsUpdate()
    {

        return !isset(static::$keyspace[$this->tableclass]);
    }

    /**
     * Regenerates the overall keyspace for the tables
     * Ensures no collisions between row keys
     */
    protected function generateTableKey()
    {

        static::$keyspace[$this->tableclass] = UUID::v4();
    }

    /**
     * Provide the Object classname
     */
    protected static function getTableClass()
    {

        return get_called_class();
    }

    /**
     * Generates a new key for the current table
     *
     * @return bool|string
     */
    public function generateKey()
    {

        $tableRow = $this->tableclass . '.' . $this->name . '_' . str_replace(' ', '', microtime());

        $key = UUID::v5(static::$keyspace[$this->tableclass], $tableRow);

        return $key;
    }

    /**
     * Set the table rows to a new matrix array
     *
     * @param array $rows
     */
    protected function setRows(array $rows)
    {

        $this->rows = $rows;
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
     * Count the current result set
     *
     * @return int
     */
    public function rowCount()
    {
        return count($this->get());
    }

//    protected function traverse($field, $row, $stack) {
//        foreach ($stack as $k => $v) {
//            if (is_array($v)) {
//                // If the current element of the array is an array, re-curse it and capture the return
//                $return = $this->traverse($row, $v);
//
//                // If the return is an array, stack it and return it
//                if (is_array($return)) {
//                    return array($k => $return);
//                }
//            } else {
//                // Since we are not on an array, compare directly
//                if ($v === $row) {
//                    // And if we match, stack it and return it
//                    return array($k => $row);
//                }
//            }
//        }
//
//        // Return false since there was nothing found
//        return false;
//    }
//

    /**
     * Resets the search results and search condition.
     */
    protected function resetSearchResults()
    {
        $this->searchResults = [];
        $this->searchFlag = true;
    }

    /**
     * Ensures a where condition has been met before attempting to
     * return a resultset.
     *
     * @return bool
     */
    protected function hasSearched()
    {
        if ($this->searchFlag) {
            $this->searchFlag = false;
            return true;
        }

        return false;
    }

    /**
     * Perform a query on the row data
     *
     * @param $array
     * @param callable $callback
     * @return $this
     */
    public function where(Closure $callback)
    {
        $this->resetSearchResults();

        foreach ($this->rows as $key => $value)
        {
            foreach ($value as $column => $field){
                if (call_user_func($callback, $column, $field)) $this->searchResults[$key] = $value;
            }

        }

        return $this;
    }

    /**
     * Retrieve resultset of query
     *
     * @return array
     */
    public function get()
    {
        $results = $this->hasSearched() ? $this->searchResults: $this->rows;

        $this->resetSearchResults();

        return $results;
    }

    /**
     * Update the resultset
     *
     * @return array
     */
    public function update(array $criteria)
    {
        $rowsToUpdate = $this->get();

        $rowsUpdated = [];

        foreach($rowsToUpdate as $key => $value) {

            $rowsUpdated[]= $this->rows[$key] = array_merge($value, array_intersect_key($criteria, $value));
        }

        return count($rowsUpdated);
    }

    /**
     * Get the first value from the resultset
     *
     * @return array
     */
    public function first()
    {
        return array_slice($this->get(),0,1,true);
    }

    /**
     * Get the last value from the resultset
     *
     * @return array
     */
    public function last()
    {
        return array_slice(array_reverse($this->get(),true),0,1,true);
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
        if (empty($rowData)) {
            return;
        }

        // Ensure the data array is able to fill the row
        $this->ableToFillRow($rowData);

        // Fill the data according to the fields specified
        if ($this->hasColumnKeys($rowData)) {
            $this->fillRow($newRow, $rowData);
        } // Treat the array as sequential values, populate data in the order it was declared
        else {
            $this->fillRowFromRaw($newRow, $rowData);
        }
    }

    /**
     * Creates a new empty row
     */
    protected function newEmptyRow($rowKey = null)
    {

        $rowKey = $rowKey ? : $this->generateKey();

        $this->rows[$rowKey] = array_fill_keys($this->columns, '');

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

        if(empty($row) === false) {
            $filled = array_intersect_key($data, $row);

            $this->rows[$rowKey] = array_merge($row, $filled);

            return $rowKey;
        }

        return null;
    }

    /**
     * Fill row data from a sequential array
     *
     * @param $rowKey
     * @param array $data
     */
    protected function fillRowFromRaw($rowKey, array $data)
    {

        $data = array_combine(array_slice($this->columns, 0, count($data)), array_values($data));

        $this->fillRow($rowKey, $data);
    }

    /**
     * Return only row data
     *
     * @return array
     */
    public function getRows()
    {

        return $this->rows;
    }

    /**
     * Retrieve the row by its key
     *
     * @param $key
     * @return null
     */
    public function getRowByKey($key)
    {

        if ($this->offsetExists($key)) {
            return $this->rows[$key];
        }

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

        if (!$result) {
            throw new RowDataException('Row offset does not exist.');
        }

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

        if (!isset($this->metadata['name'])) {
            $this->metadata['name'] = $this->name;
        }

        return $this->metadata;
    }

    /**
     * Get tabular data
     *
     * @return array
     */
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
     * @param  mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {

        return array_key_exists($key, $this->rows);
    }


    /**
     * Get a row at a given offset.
     *
     * @param  mixed $key
     * @return mixed
     */
    public function offsetGet($key)
    {

        return $this->rows[$key];
    }

    /**
     * Set the row at a given offset.
     *
     * @param  mixed $key
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {

        if (is_null($key)) {
            $this->rows[] = $value;
        } else {
            $this->rows[$key] = $value;
        }
    }

    /**
     * Unset the row at a given offset.
     *
     * @param  string $key
     * @return void
     */
    public function offsetUnset($key)
    {

        unset($this->rows[$key]);
    }

    /**
     * Get the table as JSON.
     *
     * @param  int $options
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