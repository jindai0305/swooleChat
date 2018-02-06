<?php
class SwooleServer {

	private $basc_list = [
		'LoginServer' => ['login', 'loginOut'],
		'ChatServer' => [],
	];

	private $server;
	private $application;
	private $ChatServer;
	private $LoginServer;

	public function __construct() {
		// 创建swoole_table,用于进程间数据共享
		$table = new swoole_table(1024);
		// $table->column('fd', swoole_table::TYPE_INT);
		$table->column('unique', swoole_table::TYPE_STRING, 256);
		// $table->column('type', swoole_table::TYPE_INT);
		// $table->column('data', swoole_table::TYPE_STRING, 256);
		$table->create();

		$this->server = new swoole_websocket_server('0.0.0.0', PORT);
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
		require './PushObject.php';
		require './BaseServer.php';
		require './LoginServer.php';
		require './ChatServer.php';
		require './RedisServer.php';
		// $redis = new \Redis();
		// $redis->connect('127.0.0.1', 6379);
		// $server->redis = $redis;
	}

	public function onOpen($server, $request) {

	}

	public function onMessage(swoole_websocket_server $server, $frame) {
		$received_list = json_decode($frame->data);
		$this->application = $this->searchServer($received_list->type);
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
	private function searchServer($value) {
		if (in_array($value, $this->basc_list['LoginServer'])) {
			if ($this->LoginServer == null || !$this->LoginServer instanceof LoginServer) {
				$this->LoginServer = new LoginServer();
			}
			return $this->LoginServer;
		}
		if (in_array($value, $this->basc_list['ChatServer'])) {
			if ($this->ChatServer == null || !$this->ChatServer instanceof ChatServer) {
				$this->ChatServer = new ChatServer();
			}
			return $this->ChatServer;
		}
		return false;
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