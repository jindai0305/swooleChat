<?php
namespace Lchat;

class SwooleServer {
	private $basc_list = [];

	private $server;
	private $application;
	private $ChatServer;
	private $LoginServer;

	public function __construct() {
		// 创建swoole_table,用于进程间数据共享
		$table = new \swoole_table(1024);
		// $table->column('fd', swoole_table::TYPE_INT);
		$table->column('unique', \swoole_table::TYPE_STRING, 256);
		// $table->column('type', swoole_table::TYPE_INT);
		// $table->column('data', swoole_table::TYPE_STRING, 256);
		$table->create();

		$this->server = new \swoole_websocket_server('0.0.0.0', PORT);
		$this->server->table = $table;
		// 注册回调事件
		// $this->server->on('handShake', array($this, 'onHandShake'));
		$this->server->on('workerStart', array($this, 'onWorkerStart'));
		$this->server->on('open', array($this, 'onOpen'));
		$this->server->on('message', array($this, 'onMessage'));
		$this->server->on('close', array($this, 'onClose'));
		$this->server->start();
	}

	public function start() {
		// 创建swoole_table,用于进程间数据共享
		$table = new \swoole_table(1024);
		// $table->column('fd', swoole_table::TYPE_INT);
		$table->column('unique', \swoole_table::TYPE_STRING, 256);
		// $table->column('type', swoole_table::TYPE_INT);
		// $table->column('data', swoole_table::TYPE_STRING, 256);
		$table->create();

		$this->server = new \swoole_websocket_server('0.0.0.0', PORT);
		$this->server->table = $table;
		// 注册回调事件
		// $this->server->on('handShake', array($this, 'onHandShake'));
		$this->server->on('workerStart', array($this, 'onWorkerStart'));
		$this->server->on('open', array($this, 'onOpen'));
		$this->server->on('message', array($this, 'onMessage'));
		$this->server->on('close', array($this, 'onClose'));
		$this->server->start();
	}

	public function onHandShake(\swoole_http_request $request, \swoole_http_response $response) {

	}

	public function onWorkerStart($server, $request) {
		$this->basc_list = require __DIR__ . '/config.php';
		// require $this->directory . '/PushObject.php';
		// require $this->directory . '/BaseServer.php';
		// require $this->directory . '/LoginServer.php';
		// require $this->directory . '/ChatServer.php';
		// require $this->directory . '/Cache.php';
	}

	public function onOpen($server, $request) {

	}

	public function onMessage(\swoole_websocket_server $server, $frame) {
		$received_list = json_decode($frame->data);
		$this->application = $this->_searchServer($received_list->type);
		if ($this->application === false) {
			$this->server->close($frame->fd);
			return false;
		}
		$this->application->setServer($server);
		$func = $received_list->type;
		$this->application->$func($frame);
	}

	public function onClose($server, $fd) {
		$this->LoginServer->loginOut($fd);
	}

	/**
	 *@title 根据socket传递的type判断实际执行的类文件
	 *@param value 类型
	 *@return Object 某个类的实例
	 */
	private function _searchServer($func) {
		foreach ($this->basc_list as $key => $value) {
			if (in_array($func, $value)) {
				if ($this->$key == null || !$this->$key instanceof $key) {
					$this->$key = new $key();
				}
				return $this->$key;
			}
		}
		return false;
	}

	public function autoload($className) {
		if (strpos($className, DIRECTORY_SEPARATOR) === -1) {
			$filepath = $this->directory . DIRECTORY_SEPARATOR . $className . 'php';
		} else {
			if (strpos($className, $this->prefix) === 0) {
				$parts = explode('\\', substr($className, $this->prefixLength));
			} else {
				$parts = explode('\\', $className);
			}
			$filepath = $this->directory . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . '.php';
		}
		if (is_file($filepath)) {
			require $filepath;
		}
	}

	protected function formatDir($dir) {
		$replacements = [
			'{Y}' => date('Y'),
			'{m}' => date('m'),
			'{d}' => date('d'),
		];
		return str_replace(array_keys($replacements), $replacements, $dir);
	}
}