<?php

class ChatServer {
	const GROUP_UNIQUE = 'allcomment';

	use BaseServer;

	private $redis;

	public function __construct() {
		$this->redis = Cache::factory();
	}

	public function msg($frame) {
		$list = json_decode($frame->data);
		$accept_fd = $list->data->accept === self::GROUP_UNIQUE ? null : $this->_getFdByUnique($list->data->accept);
		$send_fd = $this->_getFdByUnique($list->data->send);
		$object = $this->_setObject(false, $send_fd, $accept_fd);
		unset($accept_fd);
		unset($send_fd);
		$object->list = ['type' => 'message', 'data' => ['accept' => $list->data->accept, 'send' => $list->data->send, 'content' => $list->data->content, 'type' => 0, 'time' => date('Y-m-d H:i:s')]];
		unset($list);
		$this->_push($object);
		unset($object);
	}
}