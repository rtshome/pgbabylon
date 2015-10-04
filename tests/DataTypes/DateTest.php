<?php

use PgBabylon\DataTypes\Date;

class DateTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $j = new Date(":test");
        $this->assertEquals(":test", $j->getParameterName(), "Testing placeholder name");

        $j = new Date(1);
        $this->assertSame(1, $j->getParameterName(), "Testing question mark index");
    }

    public function testGetPgsqlValue()
    {
        $j = new Date(":test");
        $val = new \DateTime("2015-09-03 00:00:00");

        $j->setUsingPhpValue($val);
        $this->assertSame('2015-09-03', $j->getPgsqlValue(), "Testing iso datetime encoding to postgres value");
    }

    public function testSetUsingPgsqlValue()
    {
        $j = new Date
        (":test");
        $j->setUsingPgsqlValue('2015-09-03');
        $this->assertEquals(new \DateTime("2015-09-03 00:00:00"), $j->getParameterValue(), "Testing datetime decoding from postgres value");
    }

}