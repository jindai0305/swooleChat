<?php
require __DIR__ . '/src/autoload.php';

if (php_sapi_name() != 'cli') {
	die('请用cli模式启动');
}
define('PORT', 8081);
$server = new Lchat\autoload();
$server->start();
