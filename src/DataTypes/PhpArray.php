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

        return $this->_encodeAsPgSqlArrayString($this->_parameterValue);
    }

    public function setUsingPgsqlValue($val)
    {
        if(is_null($val))
            return $val;

        if(!preg_match("/^\{(.*)\}$/", $val, $regs))
            throw new InvalidValue("Value {$val} received from PostgreSQL is not a valid pgsql array");

        $this->_parameterValue = $this->_pgsqlArrayParse($val);;
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

    private function _encodeAsPgSqlArrayString($val)
    {
        $escapedValues = [];
        foreach($val as $e)
        {
            if(is_array($e))
                $escapedValues[] = $this->_encodeAsPgSqlArrayString($e);
            else
                $escapedValues[] = sprintf('"%s"', str_replace(
                        ["\\", "\""],
                        ["\\\\", "\\\""],
                        $e
                    )
                );
        }

        return sprintf('{%s}', implode(', ', $escapedValues));

    }
}