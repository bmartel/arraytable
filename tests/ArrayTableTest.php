<?php namespace Bmartel\ArrayTable\Tests;

use Bmartel\ArrayTable\ArrayTable;

class ArrayTableTest extends \PHPUnit_Framework_TestCase {


	public function testCanConstructArrayTable() {

		$arrayTable = new ArrayTable(['id', 'name']);

		$this->assertInstanceOf('Bmartel\ArrayTable\ArrayTable', $arrayTable);
	}

	public function testCanConstructArrayTableWithName() {

		$name = 'AwesomeTable';
		$arrayTable = new ArrayTable(['id', 'name'], $name);

		$this->assertEquals($name, $arrayTable->getTableName());
	}

	public function testCanAddEmptyRow() {

		$name = 'AwesomeTable';
		$row = ['id' => '', 'name' => ''];
		$rowKey = 'SomeKey';

		$arrayTable = new ArrayTable(['id', 'name'], $name);

		$arrayTable->newRow([], $rowKey);

		$this->assertEquals(1, $arrayTable->rowCount());
		$this->assertEquals($row, $arrayTable[$rowKey]);
	}

	public function testCanAddRowWithPartialData() {

		$name = 'AwesomeTable';
		$rowKey = 'SomeKey';

		$arrayTable = new ArrayTable(['id', 'name'], $name);
		$arrayTable->newRow(['name' => 'example'], $rowKey);

		$this->assertEquals(['id' => '', 'name' => 'example'], $arrayTable[$rowKey]);
	}

	public function testCanAddRowWithUnorderedPartialData() {

		$name = 'AwesomeTable';
		$rowKey = 'SomeKey';

		$arrayTable = new ArrayTable(['id', 'name', 'email'], $name);
		$arrayTable->newRow(['name' => 'example', 'id' => 23423], $rowKey);

		$this->assertEquals(['id' => 23423, 'name' => 'example', 'email' => ''], $arrayTable[$rowKey]);
	}

	public function testCanAddRowWithSequentialData() {

		$name = 'AwesomeTable';
		$rowKey = 'SomeKey';

		$arrayTable = new ArrayTable(['id', 'name', 'email'], $name);
		$arrayTable->newRow([23423, 'example'], $rowKey);

		$this->assertEquals(['id' => 23423, 'name' => 'example', 'email' => ''], $arrayTable[$rowKey]);
	}

	/**
	 * @expectedException \Bmartel\ArrayTable\Exceptions\RowDataException
	 */
	public function testExceptionWhenAttemptingToAddRowWithNonExistentDataOffset() {

		$name = 'AwesomeTable';

		$arrayTable = new ArrayTable(['id', 'name', 'email'], $name);
		$arrayTable->newRow([23423, 'example', 'more data', 'too much data', 'way too much data']);
	}

	public function testTableSerializesToJson() {

		$name = 'AwesomeTable';
		$rowKey = 'SomeKey';

		$arrayTable = new ArrayTable(['id', 'name', 'email'], $name);
		$arrayTable->newRow([23423, 'Jim Jones', 'j.jones@email.com'], $rowKey);

		$this->assertEquals(
			'{"metadata":{"name":"AwesomeTable"},' .
			'"columns":["id","name","email"],' .
			'"data":{"' . $rowKey . '":{"id":23423,"name":"Jim Jones","email":"j.jones@email.com"}}}',
			$arrayTable->toJson()
		);
	}

	public function testUniqueGeneratedRowKeysForTables() {

		$tableName = 'TableName';
		$arrayTable = new ArrayTable(['id'], $tableName);
		$arrayTable2 = new TestTable(['id'], $tableName);

		$sampleRowKeys = array_map(function () use ($arrayTable) {

			return $arrayTable->generateKey();
		}, range(0, 100));

		$sampleRowKeys2 = array_map(function () use ($arrayTable2) {

			return $arrayTable2->generateKey();
		}, range(0, 100));

		$sampleRowKeys = $sampleRowKeys + $sampleRowKeys2;

		$this->assertEquals(count(array_unique($sampleRowKeys)), count($sampleRowKeys));
	}

	public function testPopulateGetsDynamicArgumentsAndCombinesParallelArrays() {

		$columns = ['id', 'first_name', 'last_name'];
		$ids = [1, 2];
		$firstNames = ['Bob', 'Tim'];
		$lastNames = ['Dylan', 'Mcgraw'];

		$expectedResult = [
			['id' => 1, 'first_name' => 'Bob', 'last_name' => 'Dylan'],
			['id' => 2, 'first_name' => 'Tim', 'last_name' => 'Mcgraw']
		];
		$arrayTable = ArrayTable::make($columns)->populate($ids, $firstNames, $lastNames);

		$this->assertEquals($expectedResult, $arrayTable->getRows());
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testPopulateThrowsInvalidArgumentExceptionWithIncorrectArgumentCount() {

		$columns = ['id', 'first_name', 'last_name'];

		$firstNames = ['Bob', 'Tim'];
		$lastNames = ['Dylan', 'Mcgraw'];

		ArrayTable::make($columns)->populate($firstNames, $lastNames);
	}

    public function testCanSearchRowsByField()
    {

    }

    public function testCanDeleteRow()
    {

    }
}
 