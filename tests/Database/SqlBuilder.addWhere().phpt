<?php

/**
 * Test: Nette\Database\Table\SqlBuilder: addWhere() and placeholders.
 *
 * @author     Jan Skrasek
 * @package    Nette\Database
 * @dataProvider? databases.ini
 */

require __DIR__ . '/connect.inc.php'; // create $connection

Nette\Database\Helpers::loadFromFile($connection, __DIR__ . "/{$driverName}-nette_test1.sql");

use Tester\Assert;
use Nette\Database\SqlLiteral;
use Nette\Database\Reflection\ConventionalReflection;
use Nette\Database\Table\SqlBuilder;



$reflection = new ConventionalReflection;
$sqlBuilder = array();

// test paramateres with NULL
$sqlBuilder[0] = new SqlBuilder('book', $connection, $reflection);
$sqlBuilder[0]->addWhere('id ? OR id ?', array(1, NULL));
$sqlBuilder[0]->addWhere('id ? OR id ?', array(1, NULL)); // duplicit condition

// test Selection as a parameter
$sqlBuilder[1] = new SqlBuilder('book', $connection, $reflection);
$sqlBuilder[1]->addWhere('id', $connection->table('book'));

// test Selection with column as a parameter
$sqlBuilder[2] = new SqlBuilder('book', $connection, $reflection);
$sqlBuilder[2]->addWhere('id', $connection->table('book')->select('id'));

// test multiple placeholder parameter
$sqlBuilder[3] = new SqlBuilder('book', $connection, $reflection);
$sqlBuilder[3]->addWhere('id ? OR id ?', NULL, $connection->table('book'));

// test SqlLiteral
$sqlBuilder[4] = new SqlBuilder('book', $connection, $reflection);
$sqlBuilder[4]->addWhere('id IN (?)', new SqlLiteral('1, 2, 3'));

// test auto type detection
$sqlBuilder[5] = new SqlBuilder('book', $connection, $reflection);
$sqlBuilder[5]->addWhere('id ? OR id ? OR id ?', 1, "test", array(1, 2));

// test empty array
$sqlBuilder[6] = new SqlBuilder('book', $connection, $reflection);
$sqlBuilder[6]->addWhere('id', array());

// backward compatibility
$sqlBuilder[7] = new SqlBuilder('book', $connection, $reflection);
$sqlBuilder[7]->addWhere('id = ? OR id ? OR id IN ? OR id LIKE ?', 1, 2, array(1, 2), "%test");
$sqlBuilder[7]->addWhere('name', "var");

switch ($driverName) {
	case 'mysql':
		Assert::equal('SELECT * FROM `book` WHERE (`id` = ? OR `id` IS NULL)', $sqlBuilder[0]->buildSelectQuery());
		Assert::equal('SELECT * FROM `book` WHERE (`id` IN (?))', $sqlBuilder[1]->buildSelectQuery());
		Assert::equal('SELECT * FROM `book` WHERE (`id` IN (?))', $sqlBuilder[2]->buildSelectQuery());
		Assert::equal('SELECT * FROM `book` WHERE (`id` IS NULL OR `id` IN (?))', $sqlBuilder[3]->buildSelectQuery());
		Assert::equal('SELECT * FROM `book` WHERE (`id` IN (?))', $sqlBuilder[4]->buildSelectQuery());
		Assert::equal('SELECT * FROM `book` WHERE (`id` = ? OR `id` = ? OR `id` IN (?))', $sqlBuilder[5]->buildSelectQuery());
		Assert::equal('SELECT * FROM `book` WHERE (`id` IN (NULL))', $sqlBuilder[6]->buildSelectQuery());
		Assert::equal('SELECT * FROM `book` WHERE (`id` = ? OR `id` = ? OR `id` IN (?) OR `id` LIKE ?) AND (`name` = ?)', $sqlBuilder[7]->buildSelectQuery());
		break;

	case 'pgsql':
		Assert::equal('SELECT * FROM "book" WHERE ("id" = ? OR "id" IS NULL)', $sqlBuilder[0]->buildSelectQuery());
		Assert::equal('SELECT * FROM "book" WHERE ("id" IN (SELECT "id" FROM "book"))', $sqlBuilder[1]->buildSelectQuery());
		Assert::equal('SELECT * FROM "book" WHERE ("id" IN (SELECT "id" FROM "book"))', $sqlBuilder[2]->buildSelectQuery());
		Assert::equal('SELECT * FROM "book" WHERE ("id" IS NULL OR "id" IN (SELECT "id" FROM "book"))', $sqlBuilder[3]->buildSelectQuery());
		Assert::equal('SELECT * FROM "book" WHERE ("id" IN (?))', $sqlBuilder[4]->buildSelectQuery());
		Assert::equal('SELECT * FROM "book" WHERE ("id" = ? OR "id" = ? OR "id" IN (?))', $sqlBuilder[5]->buildSelectQuery());
		Assert::equal('SELECT * FROM "book" WHERE ("id" IN (NULL))', $sqlBuilder[6]->buildSelectQuery());
		Assert::equal('SELECT * FROM "book" WHERE ("id" = ? OR "id" = ? OR "id" IN (?) OR "id" LIKE ?) AND ("name" = ?)', $sqlBuilder[7]->buildSelectQuery());
		break;
}



$books = $connection->table('book')->where('id',
	$connection->table('book_tag')->select('book_id')->where('tag_id', 21)
);
Assert::equal(3, $books->count());



Assert::throws(function() use ($connection, $reflection) {
	$sqlBuilder = new SqlBuilder('book', $connection, $reflection);
	$sqlBuilder->addWhere('id ?');
}, 'Nette\InvalidArgumentException', 'Argument count does not match placeholder count.');



Assert::throws(function() use ($connection, $reflection) {
	$sqlBuilder = new SqlBuilder('book', $connection, $reflection);
	$sqlBuilder->addWhere('id = ?', NULL);
}, 'Nette\InvalidArgumentException', 'Column operator does not accept NULL argument.');



Assert::throws(function() use ($connection, $reflection) {
	$sqlBuilder = new SqlBuilder('book', $connection, $reflection);
	$sqlBuilder->addWhere('id = ?', array(1, 2));
}, 'Nette\InvalidArgumentException', 'Column operator does not accept array argument.');