<?php
use PgBabylon\PDO;
use PgBabylon\DataTypes;

class PhpArrayDbTest extends PHPUnit_Framework_TestCase
{

    public function testDataType()
    {
        if (skipTest('9.0')) {
            $this->markTestSkipped();
            return;
        }

        if (getDB()->exec("CREATE TABLE array_test(txt_arr TEXT[] NOT NULL, int_arr INT[], idx INTEGER)") === false)
            $this->markTestSkipped("Create table for array testing failed");

        // Test Inserts
        $s = getDB()->prepare("INSERT INTO array_test VALUES(:txt_arr, :int_arr)");
        $this->assertInstanceOf('\\PgBabylon\\PDOStatement', $s, "Asserting pdo::prepare returns a pgbabylon statement");

        $testTxtArr = ["val_1", "val_2"];
        $testIntArr = [1, 2, 3];

        $s->bindParam(":txt_arr", $testTxtArr, PDO::PARAM_ARRAY);
        $s->bindParam(":int_arr", $testIntArr, PDO::PARAM_ARRAY);

        $r = $s->execute();
        $this->assertTrue($r, "Testing array insert using PHP text and int array");

        // Test Select
        $s = getDB()->prepare("SELECT * FROM array_test");
        $s->bindColumn("txt_arr", $txt_val, PDO::PARAM_ARRAY);
        $s->bindColumn("int_arr", $int_val, PDO::PARAM_ARRAY);
        $r = $s->execute();
        $this->assertTrue($r, "Testing Array select");
        $r = $s->fetch(PDO::FETCH_ASSOC);
        $this->assertSame($testTxtArr, $r['txt_arr']);
        $this->assertSame($testIntArr, $r['int_arr']);

        $this->assertSame($txt_val, $r['txt_arr']);
        $this->assertSame($int_val, $r['int_arr']);

        $s = getDB()->prepare("SELECT * FROM array_test", PDO::AUTO_COLUMN_BINDING);
        $r = $s->execute();
        $this->assertTrue($r, "Testing array select with auto column binding");
        $r = $s->fetch(PDO::FETCH_ASSOC);
        $this->assertSame($testTxtArr, $r['txt_arr']);
        $this->assertSame($testIntArr, $r['int_arr']);

        // Test Nulls
        $testTxtArr = ["val_3", "val_4"];
        $s = getDB()->prepare("INSERT INTO array_test VALUES(:txt_arr, :int_arr)");
        $r = $s->execute([
            ':txt_arr' => PgBabylon\DataTypes\PhpArray($testTxtArr),
            ':int_arr' => PgBabylon\DataTypes\PhpArray(null),
        ]);
        $this->assertTrue($r, "Testing array insert using PHP array directly in execute and with null value");

        $s = getDB()->prepare("SELECT * FROM array_test WHERE int_arr IS NULL", PDO::AUTO_COLUMN_BINDING);
        $r = $s->execute();
        $this->assertTrue($r, "Testing array select with auto column binding");
        $r = $s->fetch(PDO::FETCH_ASSOC);
        $this->assertSame($testTxtArr, $r['txt_arr']);
        $this->assertSame(null, $r['int_arr']);

        // Test Escape strings
        $testTxtArr = ["\"double quoted\"", "with commas, inside"];
        $s = getDB()->prepare("INSERT INTO array_test VALUES(:txt_arr, :int_arr, :idx)");
        $r = $s->execute([
            ':txt_arr' => PgBabylon\DataTypes\PhpArray($testTxtArr),
            ':int_arr' => null,
            ':idx' => 1
        ]);
        $this->assertTrue($r, "Testing array insert using PHP array directly in execute and with null value");

        $s = getDB()->prepare("SELECT * FROM array_test WHERE idx=1");
        $s->setColumnTypes(['txt_arr' => PDO::PARAM_ARRAY]);
        $r = $s->execute();
        $this->assertTrue($r, "Testing Array select");
        $r = $s->fetch(PDO::FETCH_ASSOC);
        $this->assertSame($testTxtArr, $r['txt_arr']);
    }

}