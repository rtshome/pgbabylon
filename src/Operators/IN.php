<?php
namespace PgBabylon\Operators;


use PgBabylon\DataTypes\DataType;
use PgBabylon\Exceptions\InvalidValue;
use PgBabylon\PDO;
use PgBabylon\PDOStatement;

class IN extends Operator
{

    protected $_values;

    public function setRight($val)
    {
        if(!is_null($val) && !is_array($val))
            throw new InvalidValue("Invalid value for IN operator. Must provide an array as argument");

        if(is_null($val))
        {
            $this->_values = null;
            return;
        }

        foreach($val as $p)
        {
            if(is_object($p) && !$p instanceof DataType)
                throw new InvalidValue("Invalid value for IN operator. Array elements must me scalar types or one of PgBabylon supported DataTypes");

            if(is_array($p))
                throw new InvalidValue("Invalid value for IN operator. Array elements must me scalar types or one of PgBabylon supported DataTypes");
        }

        $this->_values = $val;
    }

    public function getQueryStringPart()
    {

        if($this->_values === null || count($this->_values) === 0)
            return "(null)";

        $parameters = [];
        $idx = 0;
        foreach($this->_values as $pVal)
        {
            $parameters[] = "{$this->_parameterName}_{$idx}";
            $idx++;
        }

        return "(" . implode(", ", $parameters) . ")";
    }

    public function getParameters()
    {
        if(is_null($this->_values))
            return [];

        $parameters = [];
        $idx = 0;
        foreach($this->_values as $pVal)
        {
            $parameters["{$this->_parameterName}_{$idx}"] = $pVal;
            $idx++;
        }

        return $parameters;
    }

}