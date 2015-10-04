<?php

use PgBabylon\DataTypes\JSON;

class JSONTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $j = new JSON(":test");
        $this->assertEquals(":test", $j->getParameterName(), "Testing placeholder name");

        $j = new JSON(1);
        $this->assertSame(1, $j->getParameterName(), "Testing question mark index");
    }

    public function testGetPgsqlValue()
    {
        $j = new JSON(":test");
        $val = ['key_1' => 'value_1', 'key_2' => 2];

        $j->setUsingPhpValue($val);
        $this->assertSame('{"key_1":"value_1","key_2":2}', $j->getPgsqlValue(), "Testing json encoding to postgres value");
    }

    public function testSetUsingPgsqlValue()
    {
        $j = new JSON(":test");
        $j->setUsingPgsqlValue('{"key_1":"value_1","key_2":2}');
        $this->assertSame(['key_1' => 'value_1', 'key_2' => 2], $j->getParameterValue(), "Testing json decoding from postgres value");
    }

}