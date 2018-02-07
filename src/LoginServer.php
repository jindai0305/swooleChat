<?php
class LoginServer {
	use BaseServer;

	private $redis;

	public function __construct() {
		$this->redis = Cache::factory();
	}

	public function login($frame) {
		$list = json_decode($frame->data);
		$this->_replaceUser($frame, $list->data);
		$object = $this->_setObject(false, $frame->fd);
		$object->list = ['type' => 'newClient', 'data' => ['unique' => $list->data->unique, 'name' => $list->data->name, 'face' => $list->data->face]];
		$this->_push($object);
		$this->_replyUsers($frame->fd);
		unset($object);
	}

	public function loginOut($fd) {
		if (!is_numeric($fd)) {
			$fd = $fd->fd;
		}
		$key = $this->_createRedisKey($this->server->table->get($fd, 'unique'));
		$data = $this->redis->hgetall($key);
		$this->redis->del($key);
		$this->server->table->del($fd);
		$object = $this->_setObject(false, $fd);
		unset($data['fd']);
		$object->list = ['type' => 'loginOut', 'data' => $data];
		unset($data);
		$this->_push($object);
		unset($object);
	}

	public function connection($frame) {
		$object = $this->_setObject(false, $frame->fd, $frame->fd);
		$object->list = ['type' => 'connection', 'data' => ['msg' => 'connection']];
		$this->_push($object);
	}

	private function _replyUsers($fd) {
		$object = $this->_setObject(false, $fd, $fd);
		$object->list = ['type' => 'alluser', 'data' => $this->_getOnlineUser()];
		$this->_push($object);
	}

	private function _getOnlineUser() {
		$list = [];
		foreach ($this->server->table as $value) {
			$key = $this->_createRedisKey($value['unique']);
			$info = $this->redis->hgetall($key);
			unset($info['fd']);
			array_push($list, $info);
		}
		unset($info);
		return $list;
	}

	private function _replaceUser($frame, $list) {
		$key = $this->_createRedisKey($list->unique);
		$old_info = $this->redis->hgetall($key);
		if (!$old_info) {
			$this->redis->hmset($key, ['fd' => $frame->fd, 'unique' => $list->unique, 'name' => $list->name, 'face' => $list->face]);
		} else {
			$this->_noticeConflict($frame->fd, $old_info['fd']);
			$this->redis->hset($key, 'fd', $frame->fd);
			if ($this->server->table->exist($old_info['fd'])) {
				$this->server->table->del($old_info['fd']);
			}
		}
		$this->server->table->set($frame->fd, ['unique' => $list->unique]);
	}

	private function _noticeConflict($new_fd, $old_fd) {
		$object = $this->_setObject(false, $new_fd, $old_fd);
		$object->list = ['type' => 'newLogin', 'data' => ['msg' => 'your account is login in other place']];
		$this->_push($object, '_accept_fd');
	}
}