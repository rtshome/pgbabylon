<?php

use PgBabylon\DataTypes\PhpArray;

class PhpArrayTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $a = new PhpArray(":test");
        $this->assertEquals(":test", $a->getParameterName(), "Testing placeholder name");

        $a = new PhpArray(1);
        $this->assertSame(1, $a->getParameterName(), "Testing question mark index");
    }

    public function testGetPgsqlValue()
    {
        $a = new PhpArray(":test");
        $val = [1,2,3];

        $a->setUsingPhpValue($val);
        $this->assertSame('{"1", "2", "3"}', $a->getPgsqlValue(), "Testing array encoding to postgres value");
    }

    public function testSetUsingPgsqlValue()
    {
        $j = new PhpArray(":test");
        $j->setUsingPgsqlValue('{1,2,3}');
        $this->assertSame([1,2,3], $j->getParameterValue(), "Testing array decoding from postgres value");
    }

}