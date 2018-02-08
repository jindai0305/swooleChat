<?php
namespace Lchat\Server;

class SwooleServer {
	private $prefix;

	private $basc_list = [];
	private $finalServers = [];

	private $server;
	private $application;

	public function __construct() {
		$this->prefix = __NAMESPACE__ . '\\';

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
		$this->basc_list = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php';
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
		$this->_searchServer('loginOut')->loginOut($fd);
	}

	/**
	 *@title 根据socket传递的type判断实际执行的类文件
	 *@param value 类型
	 *@return Object 某个类的实例
	 */
	private function _searchServer($func) {
		foreach ($this->basc_list as $key => $value) {
			if (in_array($func, $value)) {
				$class = $this->prefix . $key;
				if (!isset($this->finalServers[$key]) || $this->finalServers[$key] == '' || !$this->finalServers[$key] instanceof $class) {
					$this->finalServers[$key] = new $class();
				}
				unset($class);
				return $this->finalServers[$key];
			}
		}
		return false;
	}
}