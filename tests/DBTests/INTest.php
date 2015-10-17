<?php
use PgBabylon\PDO;
use PgBabylon\DataTypes;
use PgBabylon\Operators;

class INTest extends PHPUnit_Framework_TestCase
{
    public function testOperator()
    {
        if(skipTest()) {
            $this->markTestSkipped();
            return;
        }

        if(getDB()->exec("CREATE TABLE in_test(date_field DATE NOT NULL, int_field INTEGER )") === false)
            $this->markTestSkipped("Create table for in test failed");

        $s = getDB()->prepare("INSERT INTO in_test(date_field, int_field) VALUES(:date, :int)");
        for ($i = 0; $i < 3; $i++) {
            $r = $s->execute([
                ':int' => $i,
                ':date' => DataTypes\Date((new \DateTime("2015-09-01 00:00:00"))->add(new DateInterval("P{$i}D")))
            ]);
            $this->assertEquals(1, $r);
        }

        // Test in with native PDO datatypes
        $v = [1,2];
        $s = getDB()->prepare("SELECT * FROM in_test WHERE int_field IN :int");
        $s->bindParam(':int', $v, PDO::PARAM_IN);
        $s->execute();
        $s->setColumnTypes(['date_field' => PDO::PARAM_DATE]);

        $idx = 1;
        foreach($s as $r) {
            $this->assertEquals($idx, $r['int_field']);
            $this->assertEquals((new \DateTime("2015-09-01 00:00:00"))->add(new DateInterval("P{$idx}D")), $r['date_field']);
            $idx++;
        }

        // Test in with native PgBabylon
        $v = [DataTypes\Date(new \DateTime("2015-09-02 00:00:00"))];
        $s = getDB()->prepare("SELECT * FROM in_test WHERE date_field IN :date");
        $s->bindParam(':date', $v, PDO::PARAM_IN);
        $s->execute();
        $s->setColumnTypes(['date_field' => PDO::PARAM_DATE]);

        $r = $s->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(1, $r['int_field']);
        $this->assertEquals(new \DateTime("2015-09-02 00:00:00"), $r['date_field']);

        // Test using in directly as execute parameter
        $s = getDB()->prepare("SELECT * FROM in_test WHERE int_field IN :int");
        $s->bindParam(':int', $v, PDO::PARAM_IN);
        $s->execute([
            ':int' => Operators\IN([1,2])
        ]);
        $s->setColumnTypes(['date_field' => PDO::PARAM_DATE]);

        $idx = 1;
        foreach($s as $r) {
            $this->assertEquals($idx, $r['int_field']);
            $this->assertEquals((new \DateTime("2015-09-01 00:00:00"))->add(new DateInterval("P{$idx}D")), $r['date_field']);
            $idx++;
        }

        // Test using in with NULL values
        $s = getDB()->prepare("SELECT * FROM in_test WHERE int_field IN :int");
        $s->execute([':int' => Operators\IN(null)]);
        $this->assertSame(0, $s->rowCount());

    }
}