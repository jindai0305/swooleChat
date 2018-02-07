<?php
trait BaseServer {
	private $server;

	public function setServer($server) {
		$this->server = $server;
	}

	private function _createRedisKey($unique) {
		return 'chat.{' . $unique . '}.info';
	}

	private function _setObject($notice = false, $send_fd = null, $accept_fd = null) {
		return new PushObject($send_fd, $accept_fd, $notice);
	}

	private function _push(PushObject $object, $close = false) {
		if ($object->emptyList()) {
			return false;
		}
		if ($object->_accept_fd) {
			$this->server->push($object->_accept_fd, $object->toJson());
			unset($object);
			return true;
		}
		if ($object->_group) {
			$this->_pushGroup($object);
			unset($object);
			return true;
		}
		$send_fd = $object->_send_fd;
		foreach ($this->server->connections as $fd) {
			if (!$object->_notice_self && $fd == $send_fd) {
				continue;
			}
			$this->server->push($fd, $object->toJson());
		}
		if ($close) {
			$this->server->close($object->$close);
		}
		unset($object);
		return true;
	}

	private function _pushGroup(PushObject $object) {
		//redis取group中所有的用户
		//遍历发送数据
	}

	private function _getFdByUnique($unique) {
		$list = $this->redis->hmget($this->_createRedisKey($unique), ['fd']);
		return $list['fd'];
	}
}