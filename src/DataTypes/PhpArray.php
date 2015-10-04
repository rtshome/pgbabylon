<?php
namespace PgBabylon\DataTypes;

use PgBabylon\PDO;
use PgBabylon\Exceptions\InvalidValue;

class PhpArray extends DataType
{
    public function getPgsqlValue()
    {
        if (is_null($this->_parameterValue))
            return null;

        $escapedValues = [];
        foreach($this->_parameterValue as $e)
        {
            $escapedValues[] = sprintf('"%s"', str_replace(
                                                  ["\\", "\""],
                                                  ["\\\\", "\\\""],
                                                  $e
                                               )
            );
        }

        return sprintf('{%s}', implode(', ', $escapedValues));
    }

    public function setUsingPgsqlValue($val)
    {
        if(is_null($val))
            return $val;

        if(!preg_match("/^\{(.*)\}$/", $val, $regs))
            throw new InvalidValue("Value {$val} received from PostgreSQL is not a valid pgsql array");

        $r = null;
        @eval("\$r = [{$regs[1]}];");
        $this->_parameterValue = $r;
    }

    public function setUsingPhpValue(&$var)
    {
        if($var !== null && !is_array($var))
            throw new InvalidValue("Invalid supplied PHP value for column/parameter {$this->_parameterName} of type Array");


        if (is_null($var)) {
            $this->_parameterValue = &$var;
            return null;
        }

        $isIndexedArray = true;
        foreach ($var as $key => $value) {
            if (!is_integer($key)) {
                $isIndexedArray = false;
            }
        }
        if(!$isIndexedArray)
        {
            throw new InvalidValue("Invalid supplied PHP value for column/parameter {$this->_parameterName} of type Array: array must be indexed");
        }
        $this->_parameterValue = &$var;
    }

    public static function type()
    {
        return PDO::PARAM_ARRAY;
    }
}