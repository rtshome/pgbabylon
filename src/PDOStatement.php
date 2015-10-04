<?php
namespace PgBabylon;

use PgBabylon\Exceptions\InvalidPlaceholder;
use PgBabylon\Exceptions\InvalidValue;
use PgBabylon\Exceptions\MissingPlaceholder;
use PgBabylon\Exceptions\UnsupportedFetchMode;
use PgBabylon\Exceptions\UnsupportedMethod;
use PgBabylon\Operators\Operator;

class PDOStatement extends \PDOStatement implements \IteratorAggregate
{
    /**
     * @var \PDOStatement
     */
    protected $_statement;

    /**
     * @var string
     */
    protected $_queryString;

    /**
     * @var null
     */
    protected $_driverOptions = null;

    /**
     * @var DataTypes\DataType[]
     */
    protected $_parameters = [];

    /**
     * @var Operators\Operator[]
     */
    protected $_operators = [];

    /**
     * @var DataTypes\DataType[]
     */
    protected $_columns = [];

    /**
     * Array with the references to bindColumn params
     *
     * @var array
     */
    protected $_columnsVars = [];

    /**
     * Array with specific pgbabylon options
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Original parameters are stored here to allow debugDumpParams work
     *
     * @var array
     */
    protected $_pdoOriginalParameters = [];

    /**
     * Used by Iterator interface
     *
     * @var mixed
     */
    protected $_currentFetchResult = false;

    /**
     * Used by Iterator interface
     *
     * @var int
     */
    protected $_currentFetchRow = -1;

    /**
     * @var bool
     */
    protected $_executed = false;

    /**
     * @var PDO
     */
    protected $_parent;

    /**
     * Constructor for PgBabylon\PDOStatement.
     *
     */
    public function __construct($queryString, $driver_options, $options, PDO $parent)
    {
        $this->_queryString = $queryString;
        $this->_driverOptions = $driver_options;
        $this->_options = $options;
        $this->_parent = $parent;
    }

    public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null)
    {
        switch ($type)
        {
            case PDO::PARAM_JSON:
            case PDO::PARAM_DATETIME:
            case PDO::PARAM_DATE:
            case PDO::PARAM_ARRAY:
                $this->setColumnType($column, $type);
                $this->_columnsVars[$column] = &$param;
                break;

            default:
                return $this->_statement->bindColumn($column, $param, $type, $maxlen, $driverdata);
        }

        return true;
    }

    /**
     * Set the column type
     * Call this method before fetching data to make pgbabylon translate native pgsql data types to php types.
     *
     * @param string $column Column name (case insensitive, no check is made on column existance)
     * @param string $type One of PgBabylon\PDO::PARAM_* constants
     * @return void
     */
    public function setColumnType($column, $type)
    {
        switch($type)
        {
            case PDO::PARAM_JSON:
                $this->_columns[$column] = new DataTypes\JSON($column);
                break;

            case PDO::PARAM_DATETIME:
                $this->_columns[$column] = new DataTypes\DateTime($column);
                break;

            case PDO::PARAM_DATE:
                $this->_columns[$column] = new DataTypes\Date($column);
                break;

            case PDO::PARAM_ARRAY:
                $this->_columns[$column] = new DataTypes\PhpArray($column);
                break;

            default:
        }
    }

    /**
     * Set the columns types
     *
     * @see PgBabylon\PDOStatement::setcolumnType()
     * @param array $columns Associative array [column name => column type]
     */
    public function setColumnTypes(array $columns)
    {
        foreach($columns as $c => $t)
        {
            $this->setColumnType($c, $t);
        }
    }

    /**
     * Binds a parameter to the specified variable name
     *
     * @param mixed $parameter Parameter identifier. It can be a named placeholder (:name) or, when using question mark placeholders, the 1-indexed position of the parameter.
     * @param mixed $variable PHP variable to bind to the SQL statement parameter
     * @param int $data_type Explicit data type for the parameter using the  PgBabylon\PDO::PARAM_* constants
     * @param null $length Length of the data type
     * @param null $driver_options
     * @return bool
     */
    public function bindParam($parameter, &$variable, $data_type = PDO::PARAM_STR, $length = null, $driver_options = null)
    {
        try
        {
            switch ($data_type) {
                case PDO::PARAM_JSON:
                    $this->_parameters[$parameter] = new DataTypes\JSON($parameter);
                    $this->_parameters[$parameter]->setUsingPhpValue($variable);
                    break;

                case PDO::PARAM_DATETIME:
                    $this->_parameters[$parameter] = new DataTypes\DateTime($parameter);
                    $this->_parameters[$parameter]->setUsingPhpValue($variable);
                    break;

                case PDO::PARAM_DATE:
                    $this->_parameters[$parameter] = new DataTypes\Date($parameter);
                    $this->_parameters[$parameter]->setUsingPhpValue($variable);
                    break;

                case PDO::PARAM_ARRAY:
                    $this->_parameters[$parameter] = new DataTypes\PhpArray($parameter);
                    $this->_parameters[$parameter]->setUsingPhpValue($variable);
                    break;

                case PDO::PARAM_IN:
                    $this->_operators[$parameter] = new Operators\IN($parameter);
                    $this->_operators[$parameter]->setRight($variable);
                    break;

                default:
                    $this->_pdoOriginalParameters[$parameter] = [$parameter, &$variable, $data_type, $length, $driver_options];
                    //return $this->_statement->bindParam($parameter, $variable, $data_type, $length, $driver_options);
            }
        } catch(InvalidValue $e)
        {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }

        return true;
    }

    /**
     * Binds a value to a corresponding named or question mark placeholder in the SQL statement that was used to prepare the statement.
     *
     * @param mixed $parameter Parameter identifier. For a prepared statement using named placeholders, this will be a parameter name of the form :name. For a prepared statement using question mark placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed $value The value to bind to the parameter.
     * @param int $data_type Explicit data type for the parameter using the PDO::PARAM_* constants.
     */
    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR)
    {
        return $this->bindParam($parameter, $value, $data_type);
    }

    /**
     * Executes a prepared statement
     *
     * @param array|null $input_parameters An array of values with as many elements as there are bound parameters in the SQL statement being executed. All values are treated as PDO::PARAM_STR.
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function execute($input_parameters = null)
    {
        if($this->_statement === null)
        {
            $queryString = $this->_queryString;

            // Extract Operators from input parameters and add to operators (removing from input parameters as well)
            if(is_array($input_parameters))
            {
                $input_parameters_without_operators = [];
                foreach($input_parameters as $pName => $pVal)
                {
                    if($pVal instanceof Operator) {
                        $pVal->setParameterName($pName);
                        $this->_operators[$pName] = $pVal;
                    }
                    else {
                        $input_parameters_without_operators[$pName] = $pVal;
                    }
                }
                $input_parameters = $input_parameters_without_operators;
            }

            // queryString parsing for setting operators parameters
            foreach($this->_operators as $oName => $oVal)
            {
                if(substr_count($queryString, $oName) == 0)
                    throw new MissingPlaceholder("Missing placeholder for operator {$oName} in query string {$queryString}");

                if(substr_count($queryString, $oName) > 1)
                    throw new InvalidPlaceholder("Invalid placeholder for operator {$oName} in query string {$queryString}. It's referenced more thant once.");

                $queryString = str_replace($oName, $oVal->getQueryStringPart(), $queryString);

            }

            // Prepare original PDOStatement
            $driver_options = is_null($this->_driverOptions) ? [] : $this->_driverOptions;
            $driver_options[] = PDO::USE_ORIGINAL_PDO_STATEMENT;
            $s = $this->_parent->prepare($queryString, $driver_options);
            if($s === false)
                return false;
            $this->_statement = $s;

            // Setting parameters returned by operators
            foreach($this->_operators as $oName => $oVal) {
                foreach($oVal->getParameters() as $pName => $pVal)
                {
                    if($pVal instanceof DataTypes\DataType)
                        $this->_statement->bindValue($pName, $pVal->getPgsqlValue());
                    else
                        $this->_statement->bindValue($pName, $pVal);
                }
            }
        }


        if (is_null($input_parameters))
        {
            foreach ($this->_parameters as $p) {
                $this->_statement->bindValue($p->getParameterName(), $p->getPgsqlValue());
            }

            foreach($this->_pdoOriginalParameters as $p => $pVal)
            {
                $this->_statement->bindParam($pVal[0], $pVal[1], $pVal[2], $pVal[3], $pVal[4]);
            }
        }
        else
        {
            foreach($input_parameters as $pName => $pVal)
            {
                if($pVal instanceof DataTypes\DataType)
                    $this->_statement->bindValue($pName, $pVal->getPgsqlValue());
                else
                    $this->_statement->bindValue($pName, $pVal);
            }
        }

        $r = $this->_statement->execute();
        $this->_executed = $r;
        return $r;
    }

    /**
     * @param null $fetch_style
     * @param int $cursor_orientation
     * @param int $cursor_offset
     */
    public function fetch($fetch_style = PDO::ATTR_DEFAULT_FETCH_MODE, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        if($fetch_style == PDO::ATTR_DEFAULT_FETCH_MODE)
            $fetch_style = $this->_parent->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);

        $r = $this->_statement->fetch($fetch_style, $cursor_orientation, $cursor_offset);
        if($r === false)
        {
            $this->_executed = false;
            return $r;
        }

        switch($fetch_style)
        {
            case PDO::FETCH_ASSOC:
            case PDO::FETCH_BOTH:
                $colIdx = 0;
                foreach($r as $c => &$v)
                {
                    if(!isset($this->_columns[$c]) && in_array(PDO::AUTO_COLUMN_BINDING, $this->_options))
                        $this->setColumnType($c, $this->_getColumnDataType($colIdx));

                    if(isset($this->_columns[$c]))
                    {
                        $this->_columns[$c]->setUsingPgsqlValue($v);
                        $v = $this->_columns[$c]->getParameterValue();

                        // Handle bindColumn vars
                        if(array_key_exists($c, $this->_columnsVars))
                            $this->_columnsVars[$c] = $v;
                    }
                    $colIdx++;
                }
                break;
        }

        return $r;
    }

    public function closeCursor()
    {
        if($this->_statement instanceof \PDOStatement)
            return $this->_statement->closeCursor();

        return false;
    }

    public function columnCount()
    {
        if($this->_statement instanceof \PDOStatement)
            return $this->_statement->columnCount();
        return 0;
    }

    public function debugDumpParams()
    {
        if($this->_statement instanceof \PDOStatement)
        {
            echo $this->_statement->queryString . '\r\n';
            echo "Params: " . (count($this->_parameters) + count($this->_pdoOriginalParameters)) . "\r\n";
            foreach($this->_parameters as $pName => $pClass)
            {
                echo "ParamName: {$pName}\r\n";
                echo "  ParamType: " . $pClass::type() . "\r\n";
                echo "  ParamClass: " . get_class($pClass) . "\r\n\r\n";
            }

            foreach($this->_pdoOriginalParameters as $pName => $pType)
            {
                echo "ParamName: {$pName}\r\n";
                echo "  ParamType: {$pType}\r\n\r\n";
            }
        }
    }

    public function errorCode()
    {
        if($this->_statement instanceof \PDOStatement)
            return $this->_statement->errorCode();
        return null;
    }

    public function errorInfo()
    {
        if($this->_statement instanceof \PDOStatement)
            return $this->_statement->errorInfo();
        return null;
    }

    public function fetchAll($how = NULL, $class_name = NULL, $ctor_args = NULL)
    {
        if($how === null)
            $how = $this->_parent->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);

        switch($how)
        {
            case PDO::FETCH_ASSOC:
            case PDO::FETCH_BOTH:
                break;
            default:
                throw new UnsupportedFetchMode("FetchMode {$how} is not supported by PgBabylon");
        }

        $result = [];
        while(($r = $this->fetch($how)))
        {
            $result[] = $r;
        }
        return $result;
    }

    public function fetchColumn($column_number = 0)
    {
        throw new UnsupportedFetchMode("FetchColumn is not supported by PgBabylon");
    }

    public function fetchObject($class_name = NULL, $ctor_args = NULL)
    {
        throw new UnsupportedFetchMode("FetchObject is not supported by PgBabylon");
    }

    public function getAttribute($attribute)
    {
        if($this->_statement instanceof \PDOStatement)
            return $this->_statement->getAttribute($attribute);

        return null;
    }

    public function getColumnMeta($column)
    {
        if($this->_statement instanceof \PDOStatement)
            return $this->_statement->getColumnMeta($column);

        return null;
    }

    public function nextRowset()
    {
        throw new UnsupportedMethod("nextRowset() is not supported by PgBabylon");
    }

    public function rowCount()
    {
        if($this->_statement instanceof \PDOStatement)
            return $this->_statement->rowCount();

        return null;
    }

    public function setAttribute($attribute , $value)
    {
        if($this->_statement instanceof \PDOStatement)
            return $this->_statement->setAttribute($attribute, $value);

        return false;
    }

    public function setFetchMode($mode, $params = NULL)
    {
        if($this->_statement instanceof \PDOStatement)
            switch($mode)
            {
                case PDO::FETCH_ASSOC:
                case PDO::FETCH_BOTH:
                    $this->_statement->setFetchMode($mode);
                    break;
                default:
                    throw new UnsupportedFetchMode("FetchMode {$mode} is not supported by PgBabylon");
            }
    }

    function getIterator()
    {
        if(!$this->_executed)
            $this->execute();

        return new \ArrayIterator($this->fetchAll());
    }

    /**
     * Translate native pgtype (json for example) to PgBabylon\PDO::PARAM_*
     *
     * @param $columnIndex
     * @return int|null
     */
    protected function _getColumnDataType($columnIndex)
    {
        $meta = $this->_statement->getColumnMeta($columnIndex);

        switch($meta['native_type'])
        {
            case 'json':
                return PDO::PARAM_JSON;

            case 'timestamp':
                return PDO::PARAM_DATETIME;

            case 'date':
                return PDO::PARAM_DATE;

            case '_text':
                return PDO::PARAM_ARRAY;
        }

        return null;
    }

}