<?php

use PgBabylon\PDO;

class PDOTest extends PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        if (skipTest()) {
            $this->markTestSkipped();
            return;
        }

        $this->assertInstanceOf('\\PgBabylon\\PDO', getDB());
    }

    /**
     * @expectedException \PgBabylon\Exceptions\InvalidDSN
     */
    public function testConstructorInvalidDSN()
    {
        $p = new PDO("mysql:user=mysql");
    }

    public function testForeach()
    {
        if (skipTest()) {
            $this->markTestSkipped();
            return;
        }

        if (getDB()->exec("CREATE TABLE test_foreach(int_field INTEGER NOT NULL, ts_field TIMESTAMP)") === false)
            $this->markTestSkipped("Create table for testing foreach on PDOStatement");


        $sampleData = [];
        for ($i = 0; $i < 3; $i++) {
            $sampleData[] = [
                ':int' => $i,
                ':datetime' => (new \DateTime("2015-09-01 00:00:00"))->add(new DateInterval("P{$i}D"))
            ];
        }

        $s = getDB()->prepare("INSERT INTO test_foreach(int_field, ts_field) VALUES(:int, :datetime)");
        for ($i = 0; $i < 3; $i++) {
            $s->bindParam(":int", $sampleData[$i][':int']);
            $s->bindParam(":datetime", $sampleData[$i][':datetime'], PDO::PARAM_DATETIME);
            $this->assertEquals(1, $s->execute());
        }

        $s = getDB()->prepare("SELECT *, 1 FROM test_foreach");
        $s->setColumnTypes([
            'ts_field' => PDO::PARAM_DATETIME
        ]);
        $s->execute();

        $idx = 0;
        foreach ($s as $rIdx => $r) {
            $this->assertSame($idx, $rIdx);
            $this->assertEquals($sampleData[$idx][':int'], $r['int_field']);
            $this->assertEquals($sampleData[$idx][':datetime'], $r['ts_field']);
            $idx++;
        }
        $this->assertEquals(3, $idx);

        // Check query restart
        $idx = 0;
        foreach ($s as $rIdx => $r) {
            $this->assertSame($idx, $rIdx);
            $this->assertEquals($sampleData[$idx][':int'], $r['int_field']);
            $this->assertEquals($sampleData[$idx][':datetime'], $r['ts_field']);
            $idx++;
        }
        $this->assertEquals(3, $idx);
    }

    public function testQuery()
    {
        if (skipTest()) {
            $this->markTestSkipped();
            return;
        }

        if (getDB()->exec("CREATE TABLE test_query(int_field INTEGER NOT NULL, ts_field TIMESTAMP)") === false)
            $this->markTestSkipped("Create table for testing query method of PDO");


        $sampleData = [];
        for ($i = 0; $i < 3; $i++) {
            $sampleData[] = [
                ':int' => $i,
                ':datetime' => (new \DateTime("2015-09-01 00:00:00"))->add(new DateInterval("P{$i}D"))
            ];
        }

        $s = getDB()->prepare("INSERT INTO test_query(int_field, ts_field) VALUES(:int, :datetime)");
        for ($i = 0; $i < 3; $i++) {
            $s->bindParam(":int", $sampleData[$i][':int']);
            $s->bindParam(":datetime", $sampleData[$i][':datetime'], PDO::PARAM_DATETIME);
            $this->assertEquals(1, $s->execute());
        }


        $s = getDB()->query("SELECT * FROM test_query");
        $s->setColumnTypes([
            'ts_field' => PDO::PARAM_DATETIME
        ]);

        $idx = 0;
        foreach ($s as $rIdx => $r) {
            $this->assertEquals($sampleData[$idx][':int'], $r['int_field']);
            $this->assertEquals($sampleData[$idx][':datetime'], $r['ts_field']);
            $idx++;
        }
        $this->assertEquals(3, $idx);

    }
}