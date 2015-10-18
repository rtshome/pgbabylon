<?php
use PgBabylon\PDO;
use PgBabylon\DataTypes;

class Issue5Test extends PHPUnit_Framework_TestCase
{
    public function testIssue5()
    {
        if (skipTest()) {
            $this->markTestSkipped();
            return;
        }

        if(getDB()->exec("CREATE TABLE test_issue5(field1 DATE NOT NULL, field2 TIMESTAMP)") === false) {
            $this->markTestSkipped("Create table for testing issue 5");
            return;
        }

        $s = getDB()->prepare("INSERT INTO test_issue5(field1, field2) VALUES(?, ?)");
        $this->assertInstanceOf('\\PgBabylon\\PDOStatement', $s, "Asserting pdo::prepare returns a pgbabylon statement");

        $d1 = \DateTime::createFromFormat('Y-m-d', '2015-09-01')->setTime(0,0,0);
        $d2 = \DateTime::createFromFormat('Y-m-d H:i:s.u', '2015-09-05 19:15:10.123456');
        $n = null;

        $this->assertTrue($s->execute([DataTypes\Date($d1), DataTypes\DateTime($d2)]));

        $this->assertEquals(1, $s->rowCount());

        $s = getDb()->prepare("SELECT field1 FROM test_issue5 WHERE field1 = ?");
        $this->assertTrue($s->execute([DataTypes\Date($d1)]));

        $s->setColumnType('field1', PDO::PARAM_DATE);
        $r = $s->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($d1, $r['field1']);

    }
}