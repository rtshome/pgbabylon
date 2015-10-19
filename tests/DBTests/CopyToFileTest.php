<?php
use PgBabylon\PDO;

class CopyToFileTest extends PHPUnit_Framework_TestCase
{
    public function testDataType()
    {
        if (skipTest()) {
            $this->markTestSkipped();
            return;
        }

        if(getDB()->exec("CREATE TABLE copy_to_test(field1 TEXT NOT NULL, field2 INTEGER, field3 DATE)") === false) {
            $this->markTestSkipped("Create table for copy_to_test failed");
            return;
        }

        $data = [];
        $s = getDB()->prepare("INSERT INTO copy_to_test(field1,field2) VALUES (:field1, :field2)");
        for($i=0;$i<100;$i++)
        {
            if($i != 50)
            {
                $data[] = "text_{$i}\t{$i}";
                $s->execute([
                    ":field1" => "text_{$i}",
                    ":field2" => $i
                ]);
            }
            else
            {
                $data[] = "text_{$i}\t\\N";
                $s->execute([
                    ":field1" => "text_{$i}",
                    ":field2" => null
                ]);
            }
        }

        $this->assertTrue(getDB()->copyToFile(
            "copy_from_test",
            __TESTS_TEMP_DIR__ . '/copy_to_test.csv',
            null,
            null,
            ['field1', 'field2']
        ));

        if(!($f = file_get_contents(__TESTS_TEMP_DIR__ . '/copy_to_test.csv'))) {
            $this->markTestSkipped("Unable to load the file needed for copy to test");
            return;
        }

        $this->assertSame(implode("\n", $data) . "\n", $f);
    }
}
