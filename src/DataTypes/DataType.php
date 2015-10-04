<?php
namespace PgBabylon\DataTypes;

/**
 * @param $json
 * @return JSON
 */
function JSON($json)
{
    $j = new JSON(null);
    $j->setUsingPhpValue($json);
    return $j;
}


/**
 * @param \DateTime|null $date
 * @return Date
 * @throws \PgBabylon\Exceptions\InvalidValue
 */
function Date(\DateTime $date = null)
{
    $d = new Date(null);
    $d->setUsingPhpValue($date);
    return $d;
}

/**
 * @param \DateTime|null $datetime
 * @return DateTime
 * @throws \PgBabylon\Exceptions\InvalidValue
 */
function DateTime(\DateTime $datetime = null)
{
    $d = new DateTime(null);
    $d->setUsingPhpValue($datetime);
    return $d;
}

/**
 * @param $arr
 * @return PhpArray
 * @throws \PgBabylon\Exceptions\InvalidValue
 */
function PhpArray($arr)
{
    $a = new PhpArray(null);
    $a->setUsingPhpValue($arr);
    return $a;
}


abstract class DataType
{
    /**
     * Name of the parameter placeholder or index
     *
     * @var mixed
     */
    protected $_parameterName;

    /**
     * PHP parameter value
     *
     * @var mixed
     */
    protected $_parameterValue;

    public function __construct($parameterName)
    {
        $this->_parameterName = $parameterName;
    }

    /**
     * @return mixed
     */
    public function getParameterName()
    {
        return $this->_parameterName;
    }


    public function getParameterValue()
    {
        return $this->_parameterValue;
    }

    abstract public function getPgsqlValue();

    abstract public function setUsingPgsqlValue($val);

    abstract public function setUsingPhpValue(&$var);

    /**
     * Override this to return the PDO::PARAM_* type handled by the class
     *
     * @return null
     */
    static public function type() {
        return null;
    }
}