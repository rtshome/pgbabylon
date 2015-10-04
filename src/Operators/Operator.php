<?php
namespace PgBabylon\Operators;

use PgBabylon\PDOStatement;

/**
 * @param \PgBabylon\DataTypes\DataType[] $parts
 * @return IN
 */
function IN($parts)
{
    $o = new IN(null);
    $o->setRight($parts);
    return $o;
}

abstract class Operator
{
    /**
     * Name of the parameter placeholder or index
     *
     * @var mixed
     */
    protected $_parameterName;

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

    /**
     * @return mixed
     */
    public function setParameterName($pName)
    {
        $this->_parameterName = $pName;
    }

    abstract public function setRight($val);

    abstract public function getQueryStringPart();

    abstract public function getParameters();

    /**
     * Override this to return the PDO::PARAM_* type handled by the class
     *
     * @return null
     */
    static public function type() {
        return null;
    }

}