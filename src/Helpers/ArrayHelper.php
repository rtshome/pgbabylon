<?php
namespace PgBabylon\Helpers;

class ArrayHelper
{
    static public function isAssociative(&$arr)
    {
        $result = true;
        $keys = array_keys($arr);
        foreach($keys as $k)
            $result = $result && !is_int($k);

        return $result;
    }
}