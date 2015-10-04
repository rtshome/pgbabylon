<?php
require "vendor/autoload.php";

use PgBabylon\PDO;

exec("pg_ctl --help", $output, $result);
define('__TESTS_TEMP_DIR__', __DIR__ . '/tmp');
$pdo = null;

register_shutdown_function(function() {
    if(defined('__DB_RUNNING__') && __DB_RUNNING__ == true) {
        exec(
            sprintf(
                "pg_ctl -o \"-k '%s'\" stop -D \"%s\" -m fast",
                __TESTS_TEMP_DIR__,
                __TESTS_TEMP_DIR__
            ),
            $output,
            $result
        );

        echo "\n\n------- PostgreSQL instance LOG ------\n";
        echo file_get_contents(sprintf("%s/postgres.log", __TESTS_TEMP_DIR__));
        echo "\n\n";
    }


    if(is_dir(__TESTS_TEMP_DIR__)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__TESTS_TEMP_DIR__, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileInfo) {
            $todo = ($fileInfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileInfo->getRealPath());
        }

        rmdir(__TESTS_TEMP_DIR__);
    }

});

if($result === 0 && mkdir(__TESTS_TEMP_DIR__))
{
    try
    {
        @exec(sprintf("pg_ctl init -D \"%s\" >/dev/null 2>&1",__TESTS_TEMP_DIR__), $output, $result);
        if($result !== 0) {
            throw new \Exception(sprintf("Unable to init db in temp dir %s", __TESTS_TEMP_DIR__));
        }

        @exec(
            sprintf("pg_ctl -w -l \"%s/postgres.log\" -o \"-k '%s'\" start -D \"%s\" >/dev/null 2>&1",
                    __TESTS_TEMP_DIR__, __TESTS_TEMP_DIR__, __TESTS_TEMP_DIR__
            ),
            $output, $result
        );
        if($result !== 0) {
            throw new \Exception("Unable to start test db");
        }

        define('__DB_RUNNING__', true);

        $pdo = new PDO(sprintf("pgsql:host=%s dbname=template1", __TESTS_TEMP_DIR__));
        $r = $pdo->query("SELECT version()");
        if($r === false) {
            throw new \Exception("Unable to check version of test postgresql");
        }
        if($r->rowCount() !== 1) {
            throw new \Exception("SELECT version() returned 0 rows");
        }
        if(!preg_match("/^PostgreSQL ([0-9.]{3})/", $r->fetchAll(PDO::FETCH_ASSOC)[0]['version'], $regs)) {
            throw new \Exception("Unable to get version from PostgreSQL version() stored procedure");
        }

        define('__PGSQL_VERSION__', $regs[1]);
        echo sprintf("INFO: PostgreSQL %s server running with datadir %s\r\n", __PGSQL_VERSION__, __TESTS_TEMP_DIR__);

    } catch(Exception $e) {
        if(!defined('__DB_RUNNING__'))
            define('__DB_RUNNING__', false);
        echo "WARNING: Test postgresql instance not running. Error: {$e->getMessage()}\r\n";
    }
}

/**
 * @return null|\PgBabylon\PDO
 */
function getDB()
{
    global $pdo;
    if($pdo instanceof PDO)
        return $pdo;

    return null;
}

/**
 * @param string $requiredPgSqlVersion
 * @return bool
 */
function skipTest($requiredPgSqlVersion = '0.0')
{
    if(is_null(getDB()))
        return true;

    return defined('__PGSQL_VERSION__') && __PGSQL_VERSION__ < $requiredPgSqlVersion;
}
