<?php
namespace PgBabylon;

use PgBabylon\Exceptions\InvalidDSN;

require_once __DIR__ . '/DataTypes/DataType.php';
require_once __DIR__ . '/Operators/Operator.php';

class PDO extends \PDO
{
    const PARAM_IN = 99;

    const PARAM_JSON = 100;
    const PARAM_DATETIME = 101;
    const PARAM_DATE = 102;
    const PARAM_ARRAY = 103;

    const AUTO_COLUMN_BINDING = "auto_column_binding";

    const USE_ORIGINAL_PDO_STATEMENT = "use_original_pdo_statement";

    /**
     * Creates a PDO instance to represent a connection to the requested PostgreSQL database.
     * Throws an exception if the DSN does not begin with pgsql:
     *
     * @param $dsn
     * @param string $username
     * @param string $password
     * @param array|null $options
     */
    public function __construct( $dsn, $username = null, $password = null, array $options = null )
    {
        if(stripos($dsn, "pgsql:") !== 0)
        {
            throw new InvalidDSN("PgBabylon accepts only valid pgsql PDO DSN. Given: {$dsn}");
        }
        parent::__construct($dsn, $username, $password, $options);
    }

    /**
     * Prepares a statement for execution and returns a statement object
     *
     * @link http://php.net/manual/en/pdo.prepare.php
     * @param string $statement This must be a valid SQL statement for the target database server.
     * @param array $driver_options [optional] <p>
     * This array holds one or more key=&gt;value pairs to set
     * attribute values for the <b>PDOStatement</b> object that this method
     * returns.
     * You would use to set PgBabylon\PDO::AUTO_COLUMN_BINDING to request PgBabylon autobinding query columns
     * to native PHP types.<br>
     * Warning 1: This is expensive because, for each column (not row) of the statement, a query
     * to pgsql server is made to get native type information.<br>
     *
     * You would most commonly use this to set the
     * <b>PDO::ATTR_CURSOR</b> value to
     * <b>PDO::CURSOR_SCROLL</b> to request a scrollable cursor.
     * Some drivers have driver specific options that may be set at
     * prepare-time.
     * </p>
     *
     * @return PDOStatement If the database server successfully prepares the statement,
     * <b>PDO::prepare</b> returns a
     * <b>PDOStatement</b> object.
     * If the database server cannot successfully prepare the statement,
     * <b>PDO::prepare</b> returns <b>FALSE</b> or emits
     * <b>PDOException</b> (depending on error handling).
     * </p>
     * <p>
     *
     * @throws \PDOException
     */
    public function prepare($statement, $driver_options = null)
    {
        // Specific pgbabylon options
        $pgbabylonOptions = [];
        if( $driver_options === self::AUTO_COLUMN_BINDING ||
            (is_array($driver_options) && in_array(self::AUTO_COLUMN_BINDING, $driver_options))
        ) {
            $pgbabylonOptions[] = self::AUTO_COLUMN_BINDING;
            $driver_options = is_array($driver_options) ?
                array_filter($driver_options, function($v) { return !$v === self::AUTO_COLUMN_BINDING;}) :
                null;
        }

        if( $driver_options === self::USE_ORIGINAL_PDO_STATEMENT ||
            (is_array($driver_options) && in_array(self::USE_ORIGINAL_PDO_STATEMENT, $driver_options))
        ) {
            $driver_options = is_array($driver_options) ?
                array_filter($driver_options, function($v) { return !$v === self::AUTO_COLUMN_BINDING;}) :
                null;
            return parent::prepare($statement, is_null($driver_options) ? [] : $driver_options);
        }

        /*
        $sth = parent::prepare($statement, is_null($driver_options) ? [] : $driver_options);
        if($sth === false)
            return false;
        */

        return new PDOStatement($statement, $driver_options, $pgbabylonOptions, $this);
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object
     *
     * @param string $statement The SQL statement to prepare and execute. Data inside the query should be properly escaped.
     * @param null $fetch_class
     * @param null $classname
     * @return bool|PDOStatement
     */
    public function query($statement ,$fetch_class=null , $classname=null)
    {
        $s = $this->prepare($statement);
        if($s->execute() === false)
            return false;

        return $s;
    }
}