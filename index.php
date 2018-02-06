<?php
require 'SwooleServer.php';
if (php_sapi_name() != 'cli') {
	die('请用cli模式启动');
}

define('ROOT_PATH', dirname(dirname(dirname(__DIR__))) . '/');
define('PORT', 8081);
$server = new SwooleServer();
