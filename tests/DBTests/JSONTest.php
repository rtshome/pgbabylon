<?php
use PgBabylon\PDO;

class JsonDbTest extends PHPUnit_Framework_TestCase
{

    public function testDataType()
    {
        if(skipTest('9.3')) {
            $this->markTestSkipped();
            return;
        }

        if(getDB()->exec("CREATE TABLE json(field JSON NOT NULL)") === false)
            $this->markTestSkipped("Create table with json data type failed");

        $s = getDB()->prepare("INSERT INTO json VALUES(:json)");
        $this->assertInstanceOf('\\PgBabylon\\PDOStatement', $s, "Asserting pdo::prepare returns a pgbabylon statement");

        $p = ["key_1" => "val_1", "key_2" => 2];
        $s->bindParam(":json", $p, PDO::PARAM_JSON);
        $r = $s->execute();
        $this->assertTrue($r, "Testing json insert using PHP array");

        $s = getDB()->prepare("SELECT field AS json_col FROM json");
        $s->bindColumn("json_col", $val, PDO::PARAM_JSON);
        $r = $s->execute();
        $this->assertTrue($r, "Testing json select");
        $r = $s->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(['json_col' => $p], $r, "Asserting fetch return an array after deserializing the pgsql json");
        $this->assertSame($p, $val, "Asserting fetch sets the previously bound variable as json");

        $s = getDB()->prepare("SELECT field AS json_col FROM json", PDO::AUTO_COLUMN_BINDING);
        $r = $s->execute();
        $this->assertTrue($r, "Testing json select with auto column binding");
        $r = $s->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(['json_col' => $p], $r, "Asserting fetch return an array after deserializing the pgsql json with auto column binding");

        $p = ["key_2" => "val_2", "key_3" => 3];
        $s = getDB()->prepare("INSERT INTO json VALUES(:json)");
        $r = $s->execute([':json' => PgBabylon\DataTypes\JSON($p)]);
        $this->assertTrue($r, "Testing json insert using PHP array directly in execute");
    }

}