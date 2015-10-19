<?php
use PgBabylon\PDO;

class CopyFromFileTest extends PHPUnit_Framework_TestCase
{
    public function testDataType()
    {
        if (skipTest()) {
            $this->markTestSkipped();
            return;
        }

        if(getDB()->exec("CREATE TABLE copy_from_test(field1 TEXT NOT NULL, field2 INTEGER, field3 DATE)") === false) {
            $this->markTestSkipped("Create table for copy_from_test failed");
            return;
        }

        $data = [];
        for($i=0;$i<100;$i++)
        {
            if($i != 50)
                $data[] = "text_{$i}\t{$i}";
            else
                $data[] = "text_{$i}\t\\N";
        }

        if(!file_put_contents(__TESTS_TEMP_DIR__ . '/copy_from_test.csv', implode("\n", $data))) {
            $this->markTestSkipped("Unable to save the file needed for copy from test");
            return;
        }

        $this->assertTrue(getDB()->copyFromFile(
            "copy_from_test",
            __TESTS_TEMP_DIR__ . '/copy_from_test.csv',
            null,
            null,
            ['field1', 'field2']
        ));

        $r = getDB()->query("SELECT count(field1) AS f1, count(field2) AS f2 FROM copy_from_test")
                    ->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals($r['f1'], 100);
        $this->assertEquals($r['f2'], 99);
    }
}
