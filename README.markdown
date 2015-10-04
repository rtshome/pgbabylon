Description
============

PgBabylon is a user-land extension to PHP PDO and PDOStatement native classes that helps to deal with PostgreSQL types
like JSON, Arrays, etc.

It provides conversion between PHP types and PostgreSQL types and simplifies usage of SQL clauses like IN.

```php
// Example: insert a PHP array in a PostgreSQL JSON field

$arr = [
    "name" => "Mark",
    "age" => 39,
    "address" => "White street, 23",
    "town" => "London"
];

$s = $pdo->prepare("INSERT INTO my_table(json) VALUES (:json)");
$s->execute([
    ":json" => new \PgBabylon\DataTypes\JSON($arr)
]);
```

Getting Started
===============

PgBabylon can be installed using composer:

```
composer require rtshome/pgbabylon
```

PgBabylon currently supports the following PostgreSQL datatypes:

* Date (PHP DateTime)
* Timestamp (PHP DateTime)
* JSON (PHP Array)
* text, int and float single dimension arrays (PHP Array)

These types can also be used with the IN operator (see example below).

```SQL
-- Sample table
CREATE TABLE person(
    id SERIAL NOT NULL PRIMARY KEY,
    data JSON NOT NULL,
    insertion_date DATE NOT NULL
);
```

```
use PgBabylon\PDO;
use PgBabylon\DataTypes;
use PgBabylon\Operators;

$pdo = new PDO("pgsql:dbname=testdb;host=127.0.0.1;user=myuser;pass=mypasswd");
$s = $pdo->prepare("INSERT INTO person(data, insertion_date) VALUES (:person, :ins_date) RETURNING *");

$person = [
    "name" => "Mark",
    "age" => 39,
    "address" => "White street, 23",
    "town" => "London"
];
$s->execute([
    ':person' => DataTypes\JSON($person),
    ':ins_date' => DataTypes\Date(new DateTime())
]);

// PgBabylon\PDOStatement::setColumnTypes() is the method that makes PgBabylon to recognize and convert from Pgsql types  
$s->setColumnTypes([
    'data' => PDO::PARAM_JSON,
    'insertion_date' => PDO::PARAM_DATE
]);
$r = $s->fetch(PDO::FETCH_ASSOC);

var_dump($r);

/* var_dump output

array(3) {
  'id' =>
  int(1)
  'data' =>
  array(4) {
    'name' =>
    string(4) "Mark"
    'age' =>
    int(39)
    'address' =>
    string(16) "White street, 23"
    'town' =>
    string(6) "London"
  }
  'insertion_date' =>
  class DateTime#4 (3) {
    public $date =>
    string(26) "2015-10-04 00:00:00.000000"
    public $timezone_type =>
    int(3)
    public $timezone =>
    string(11) "Europe/Rome"
  }
}

*/

$s = $pdo->prepare("SELECT data FROM person WHERE id IN :person_ids");
$s->execute([
    ':person_ids' => Operators\IN([1,2])
]);
$s->setColumnTypes([
    'data' => PDO::PARAM_JSON
]);

var_dump($r);

/* var_dump output
 
array(1) {
  'data' =>
  array(4) {
    'name' =>
    string(4) "Mark"
    'age' =>
    int(39)
    'address' =>
    string(16) "White street, 23"
    'town' =>
    string(6) "London"
  }
}

*/

```


Compatibility with original PDO class
===============

PgBabylon\PDO is fully backward compatible with the original PDO class.

You can switch from original PDO class to PgBabylon\PDO adding a `use` statement:
 
```php
<?php
// PDO usage with original class
/* Connect to a PgSQL database using driver invocation */
$dsn = 'pgsql:dbname=testdb;host=127.0.0.1;user=postgres;pass=mypasswd';
$dbh = new PDO($dsn);

$dbh->prepare("SELECT id FROM person WHERE id=:id");
$r = $dbh->execute([':id' => 1]);
?>
```

```php
<?php
// Switch to PgBabylon\PDO!
use PgBabylon\PDO;

/* Connect to a PgSQL database using driver invocation */
$dsn = 'pgsql:dbname=testdb;host=127.0.0.1;user=postgres;pass=mypasswd';
$dbh = new PDO($dsn);

$dbh->prepare("SELECT id FROM person WHERE id=:id");
$r = $dbh->execute([':id' => 1]);
?>
```
