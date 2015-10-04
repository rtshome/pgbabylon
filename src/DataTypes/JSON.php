<?php
namespace PgBabylon\DataTypes;

use PgBabylon\PDO;

class JSON extends DataType
{
    public function getPgsqlValue()
    {
        return json_encode($this->_parameterValue);
    }

    public function setUsingPgsqlValue($val)
    {
        $this->_parameterValue = json_decode($val, true);
    }

    public function setUsingPhpValue(&$var)
    {
        $this->_parameterValue = &$var;
    }

    public static function type()
    {
        return PDO::PARAM_JSON;
    }
}