<?php

use PgBabylon\DataTypes\DateTime;

class DateTimeTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $j = new DateTime(":test");
        $this->assertEquals(":test", $j->getParameterName(), "Testing placeholder name");

        $j = new DateTime(1);
        $this->assertSame(1, $j->getParameterName(), "Testing question mark index");
    }

    public function testGetPgsqlValue()
    {
        $j = new DateTime(":test");
        $val = new \DateTime("2015-09-03 23:12:35");

        $j->setUsingPhpValue($val);
        $this->assertSame('2015-09-03 23:12:35.000000', $j->getPgsqlValue(), "Testing iso datetime encoding to postgres value");
    }

    public function testSetUsingPgsqlValue()
    {
        $j = new DateTime(":test");
        $j->setUsingPgsqlValue('2015-09-03 23:12:35');
        $this->assertEquals(new \DateTime("2015-09-03 23:12:35"), $j->getParameterValue(), "Testing datetime decoding from postgres value");
    }

}