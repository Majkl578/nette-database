<?php

/**
 * Test: Nette\Database\Table: Fetch field.
 * @dataProvider? ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/../connect.inc.php'; // create $connection

Nette\Database\Helpers::loadFromFile($connection, __DIR__ . "/../files/{$driverName}-nette_test1.sql");


test(function () use ($context) {
	$title = $context->table('book')->where('id', 1)->fetchField('title');  // SELECT `title` FROM `book` WHERE `id` = 1
	Assert::same('1001 tipu a triku pro PHP', $title);
});


test(function () use ($context) {
	$title = $context->table('book')->where('id', 1)->select('title')->fetchField();  // SELECT `title` FROM `book` WHERE `id` = 1
	Assert::same('1001 tipu a triku pro PHP', $title);
});
