<?php
trait BaseServer {
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
			return true;
		}
		$notice = $object->_notice_self;
		foreach ($this->server->connections as $fd) {
			if (!$notice && $fd === $notice) {
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
}