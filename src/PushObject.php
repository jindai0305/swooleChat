<?php

class PushObject {
	private $_send_fd = null;
	private $_accept_fd = null;
	private $_notice_self = false;
	private $_group = null;
	private $list = [];
	private $json_list = '';

	public function __construct($send_fd, $accept_fd, $notice_self) {
		$this->_send_fd = $send_fd;
		$this->_accept_fd = $accept_fd;
		$this->_notice_self = $notice_self;
	}

	public function __get($name) {
		return $this->$name;
	}

	public function __set($name, $value) {
		$this->$name = $value;
	}

	public function emptyList() {
		return empty($this->list) && $this->json_list == '';
	}

	public function toJson() {
		if ($this->json_list == '') {
			$this->json_list = json_encode($this->list);
			$this->list = [];
		}
		return $this->json_list;
	}
}