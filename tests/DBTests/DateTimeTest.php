<?php
use PgBabylon\PDO;

class DateTimeDbTest extends PHPUnit_Framework_TestCase
{
    public function testDataType()
    {
        if(skipTest()) {
            $this->markTestSkipped();
            return;
        }

        if(getDB()->exec("CREATE TABLE date_time(field TIMESTAMP NOT NULL, field2 TIMESTAMP)") === false)
            $this->markTestSkipped("Create table with timestamp data type failed");

        $s = getDB()->prepare("INSERT INTO date_time (field, field2) VALUES(:datetime, :second_datetime)");
        $this->assertInstanceOf('\\PgBabylon\\PDOStatement', $s, "Asserting pdo::prepare returns a pgbabylon statement");

        $d1 = \DateTime::createFromFormat('Y-m-d H:i:s.u', '2015-09-01 20:23:10.123456');
        $d2 = \DateTime::createFromFormat('Y-m-d H:i:s.u', '2015-09-05 19:15:10.123456');
        $n = null;

        $s->bindParam(":datetime", $d1, PDO::PARAM_DATETIME);
        $s->bindParam(":second_datetime", $n, PDO::PARAM_DATETIME);
        $r = $s->execute();
        $this->assertTrue($r, "Testing first datetime insert using PHP DateTime");

        $s->bindParam(":datetime", $d2, PDO::PARAM_DATETIME);
        $s->bindParam(":second_datetime", $d2, PDO::PARAM_DATETIME);
        $r = $s->execute();
        $this->assertTrue($r, "Testing second datetime insert using PHP DateTime");

        $s = getDB()->prepare("SELECT field, field2 FROM date_time WHERE field <= :ts");
        $ts = \DateTime::createFromFormat('Y-m-d H:i:s', '2015-09-02 00:00:00');
        $s->bindParam(":ts", $ts, PDO::PARAM_DATETIME);
        $s->bindColumn("field", $val, PDO::PARAM_DATETIME);
        $s->bindColumn("field2", $val2, PDO::PARAM_DATETIME);
        $r = $s->execute();
        $this->assertTrue($r, "Testing DateTime select");

        $r = $s->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($d1, $r['field'], "Asserting fetch returns a DateTime object");
        $this->assertEquals($d1, $val, "Asserting fetch returns a DateTime object");
        $this->assertEquals(null, $r['field2'], "Asserting fetch returns null");
        $this->assertEquals(null, $val2, "Asserting fetch returns null");

        $s = getDB()->prepare("SELECT field, field2 FROM date_time WHERE field <= :ts", PDO::AUTO_COLUMN_BINDING);
        $ts = \DateTime::createFromFormat('Y-m-d H:i:s', '2015-09-02 00:00:00');
        $s->bindParam(":ts", $ts, PDO::PARAM_DATETIME);
        $r = $s->execute();
        $this->assertTrue($r, "Testing DateTime select with auto column binding");
        $r = $s->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals($d1, $r['field'], "Asserting fetch return a DateTime object with auto column binding");

        $s = getDB()->prepare("INSERT INTO date_time(field, field2) VALUES(:datetime, :second_datetime)");
        $r = $s->execute([
            ':datetime' => PgBabylon\DataTypes\DateTime(new DateTime()),
            ':second_datetime' => null
        ]);
        $this->assertTrue($r, "Testing datetime insert using PHP DateTime directly in execute");

    }

}