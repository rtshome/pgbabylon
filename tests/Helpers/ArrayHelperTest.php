<?php
use PgBabylon\Helpers\ArrayHelper;

class ArrayHelperTest extends PHPUnit_Framework_TestCase
{
    public function testIsAssociative()
    {
        $a = ["test1","test2","test3"];

        $this->assertFalse(ArrayHelper::isAssociative($a));

        $a = ["k1" => "test1", "k2" => "test2", "k3" => "test3"];

        $this->assertTrue(ArrayHelper::isAssociative($a));
    }
}