<?php
namespace PgBabylon\DataTypes;

use PgBabylon\PDO;
use PgBabylon\Exceptions\InvalidValue;

class PhpArray extends DataType
{
    const ASCAN_ERROR = -1;
    const ASCAN_EOF =    0;
    const ASCAN_BEGIN=   1;
    const ASCAN_END =    2;
    const ASCAN_TOKEN =  3;
    const ASCAN_QUOTED = 4;

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

    private function _arrayTokenize($str, $str_length, &$pos, &$token, &$quotes)
    {

        //echo sprintf("array_tokenize: '%s' %d %s\n", isset($str[$pos]) ? $str[$pos] : '', $pos, $str);

        /* we always get called with pos pointing at the start of a token, so a
           fast check is enough for ASCAN_EOF, ASCAN_BEGIN and ASCAN_END */
        if ($pos == $str_length) {
            return self::ASCAN_EOF;
        }
        else if ($str[$pos] == '{') {
            $pos += 1;
            return self::ASCAN_BEGIN;
        }
        else if ($str[$pos] == '}') {
            $pos += 1;
            if (isset($str[$pos]) && $str[$pos] == ',')
                $pos += 1;
            return self::ASCAN_END;
        }

        /* now we start looking for the first unquoted ',' or '}', the only two
           tokens that can limit an array element */
        $q = 0; /* if q is odd we're inside quotes */
        $b = 0; /* if b is 1 we just encountered a backslash */
        $res = self::ASCAN_TOKEN;

        for ($i = $pos ; $i < $str_length ; $i++) {
            switch ($str[$i]) {
                case '"':
                    if ($b == 0)
                        $q += 1;
                    else
                        $b = 0;
                    break;

                case '\\':
                    $res = self::ASCAN_QUOTED;
                    if ($b == 0)
                        $b = 1;
                    else
                        /* we're backslashing a backslash */
                        $b = 0;
                    break;

                case '}':
                case ',':
                    if ($b == 0 && (($q&1) == 0))
                        break 2; // exit for loop
                    break;

                default:
                    /* reset the backslash counter */
                    $b = 0;
                    break;
            }
        }

        /* remove initial quoting character and calculate raw length */
        $quotes = 0;
        $l = $i - $pos;
        if ($str[$pos] == '"') {
            $pos += 1;
            $l -= 2;
            $quotes = 1;
        }

        if ($res == self::ASCAN_QUOTED) {

            $token = "";
            for ($j=$pos, $jj=$j+$l; $j<$jj; ++$j)
            {
                if($str[$j] == '\\') { ++$j;}
                $token .= $str[$j];
            }

        }
        else {
            $token = substr($str, $pos, $l);
        }

        if(defined('FILTER_VALIDATE_INT')) {
            if(filter_var($token, FILTER_VALIDATE_INT)) {
                $token = (int) $token;
            }
            else if(filter_var($token, FILTER_VALIDATE_FLOAT)) {
                $token = (float) $token;
            }
        }

        $pos = $i;

        /* skip the comma and set position to the start of next token */
        if ($str[$i] == ',') $pos += 1;

        return $res;
    }

    private function _pgsqlArrayParse($arr)
    {
        $state = null;
        $quotes = 0;
        $pos = 0;
        $stack = [];
        $stack_index = 0;

        while (1) {
            $token = null;

            $state = $this->_arrayTokenize($arr, strlen($arr), $pos, $token, $quotes);
            //echo sprintf("array_scan: state = %d, t = %s\n", $state, $token);

            if ($state == self::ASCAN_TOKEN || $state == self::ASCAN_QUOTED) {

                $obj = null;
                if (!(!$quotes && strtolower($token) == 'null')) {
                    $obj = $token;
                }
                $stack[$stack_index-1][] = $obj;

                if ($obj == NULL) return -1;
            }
            else if($state == self::ASCAN_BEGIN) {
                $stack[$stack_index++] = [];
            }

            else if ($state == self::ASCAN_ERROR) {
                throw new InvalidValue("Unable to parse array value");
            }

            else if ($state == self::ASCAN_END) {
                if ($stack_index == 0) {
                    throw new InvalidValue("Unbalanced braces in array");
                }
                $stack[$stack_index-2][] = $stack[$stack_index-1];
                $stack_index--;
            }
            else if ($state ==  self::ASCAN_EOF)
                break;
        }

        return $stack[0];
    }

}