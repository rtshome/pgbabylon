<?php
namespace PgBabylon\DataTypes;

use PgBabylon\Exceptions\InvalidValue;
use PgBabylon\PDO;

/**
 * Class DateTime
 * @package PgBabylon\DataTypes
 *
 * @property \DateTime _parameterValue
 */
class Date extends DataType
{
    public function getPgsqlValue()
    {
        return $this->_parameterValue instanceof \DateTime ? $this->_parameterValue->format("Y-m-d") : null;
    }

    public function setUsingPgsqlValue($val)
    {
        if(is_null($val))
            return null;

        $this->_parameterValue = \DateTime::createFromFormat("Y-m-d|+", "{$val} "); // Add a space to the date in order to satisfy + char
    }

    public function setUsingPhpValue(&$var)
    {
        if($var !== null && !$var instanceof \DateTime)
            throw new InvalidValue("Invalid supplied PHP value for column/parameter {$this->_parameterName} of type DateTime");

        $this->_parameterValue = $var;
    }

    public static function type()
    {
        return PDO::PARAM_DATE;
    }
}