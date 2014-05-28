<?php
namespace Bmartel\ArrayTable\Tests;


use Bmartel\ArrayTable\Row;

class RowTest extends \PHPUnit_Framework_TestCase{

    /**
     * @var \Bmartel\ArrayTable\Row
     */
    protected $row;

    protected $columns;

    public function setUp()
    {
        $this->columns = ['id', 'first_name', 'last_name'];

        $this->row = new Row($this->columns);
    }

    public function tearDown()
    {

    }

    public function testCanConstructRow() {
        $this->assertInstanceOf('Bmartel\ArrayTable\Row', $this->row);
    }

    public function testCanGenerateSignature(){
        $this->assertAttributeNotEmpty('signature', $this->row);
    }

    public function testThrowsExceptionIfRowHasNoColumns() {
        $this->setExpectedException('Bmartel\ArrayTable\Exceptions\ColumnsNotDefinedException');

        $newRow = new Row();
    }

    public function testSignaturesGeneratedAreAlwaysTheSameForSameColumnData(){
        $newRow = new Row($this->columns);

        $this->assertEquals($this->row->signature(), $newRow->signature());
    }

    public function testCanAccessColumnsLikeClassAttributes() {

        $this->assertEquals('', $this->row->id);
    }

    public function testCanPopulateRow() {

        $newRow = new Row([
            'id' => 1,
            'name' => 'test'
        ]);

        $this->assertEquals(1, $newRow->id);
        $this->assertEquals('test', $newRow->name);
    }

    public function testCanUpdateSingleColumn() {

        $this->row->id = 1;
        $columns = $this->row->toArray();

        $this->assertEquals(1, $this->row->id);
        $this->assertEquals(1, $columns['id']);
    }
} 