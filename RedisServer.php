<?php
/**
 *自定义Redis类
 */
class RedisServer {

	private $redis_host;
	private $redis_port;
	private $auth;
	private static $conn_link = null;
	private static $_instance = null;

	private function __construct($host = "", $port = "", $auth = "") {
		$this->redis_host = $host == "" ? '127.0.0.1' : $host;
		$this->redis_port = $port == "" ? 6379 : $port;
		$this->auth = $auth == "" ? '' : $auth;
		if (!self::$conn_link) {
			$this->connect();
		}
	}

	private function __clone() {}

	public static function get_instance($host = "", $port = "", $auth = "") {
		if (!(self::$_instance instanceof self)) {
			self::$_instance = new self($host, $port, $auth);
		}
		return self::$_instance;
	}

	private function connect() {
		self::$conn_link = new \Redis();
		self::$conn_link->connect($this->redis_host, $this->redis_port);
		if ($this->auth !== '') {
			self::$conn_link->auth($this->auth);
		}
	}

	/**
	 *同redis手册 对字符串进行存储 自定义加上数组/对象判断
	 *@param key 存储的键名
	 *@param value 存储的键值
	 *@param time 生存时间/单位为毫秒|默认为0
	 */
	public function set($key, $value, $time = 0) {
		if (is_array($value) || is_object($value)) {
			$value = json_encode($value);
		}
		self::$conn_link->set($key, $value);
		if ($time > 0) {
			self::$conn_link->expire($key, $time);
		}
	}

	/**
	 *同redis手册 对字符串进行取值
	 *@param key 要取出值的键名
	 *@return 键对应的键值 不存在返回false
	 */
	public function get($key) {
		$value = self::$conn_link->get($key);
		$json_data = json_decode($value, true);
		return $json_data == "" ? $value : $json_data;
	}

	/**
	 *同redis手册 删除对应键
	 *@param key 需删除的键名
	 */
	public function del($key) {
		return self::$conn_link->del($key);
	}

	/**
	 *同redis手册 判断键是否存在
	 *@param key 需查询的键名
	 *@return 存在返回1 不存在返回false
	 */
	public function exists($key) {
		return self::$conn_link->exists($key);
	}

	/**
	 *同redis手册 查询键的剩余时间
	 *@param key 需查询的键名
	 *@return 存在返回剩余时间 失败返回false
	 */
	public function ttl($key) {
		return self::$conn_link->ttl($key);
	}

	/**
	 *同redis手册 对字符串进行存储/如果不存在直接存储，存在则不做操作
	 *@param key 存储的键名
	 *@param value 存储的键值
	 *@param time 生存时间/单位为毫秒|默认为0
	 */
	public function setnx($key, $value, $time = 0) {
		if (is_array($value) || is_object($value)) {
			$value = json_encode($value);
		}
		self::$conn_link->setnx($key, $value);
		if ($time > 0) {
			self::$conn_link->expire($key, $time);
		}
	}

	/**
	 *同redis手册 对键值进行自增
	 *@param 需自增的键
	 *@return 如果键存在且为数字 返回自增后的值 否则返回false
	 */
	public function incr($key) {
		$value = $this->get($key);
		if ($value && is_numeric($value)) {
			return self::$conn_link->incr($key);
		} else {
			return false;
		}
	}

	/**
	 *同redis手册 对键值进行自减
	 *@param 需自增的键
	 *@return 如果键存在且为数字 返回自减后的值 否则返回false
	 */
	public function decr($key) {
		$value = $this->get($key);
		if ($value && is_numeric($value)) {
			return self::$conn_link->decr($key);
		} else {
			return false;
		}
	}

	/**
	 *同redis手册 取得所有指定键的值。如果一个或多个键不存在，该数组中该键的值为假
	 *@param key_array 需取值键的数组集合 例array('key1','key2','key3')
	 *@return 键存在返回对应键值 否则对应键值为false 例array('key1',false,'key3')
	 */
	public function getMultiple($key_array) {
		if (!empty($key_array) && $key_array !== 0) {
			return self::$conn_link->getMultiple($key_array);
		} else {
			return array();
		}
	}

	/**
	 *同redis手册 像列表头部添加字符串值 若键不存在创建该键
	 *@param key 需添加的键名
	 *@param value 需添加的键值
	 *@return 若键为列表返回列表长度  否则返回false
	 */
	public function lpush($key, $value) {
		return self::$conn_link->lpush($key, $value);
	}

	/**
	 *同redis手册 像列表尾部添加字符串值 若键不存在创建该键
	 *@param key 需添加的键名
	 *@param value 需添加的键值
	 *@return 若键为列表返回列表长度  否则返回false
	 */
	public function rpush($key, $value) {
		return self::$conn_link->rpush($key, $value);
	}

	/**
	 *同redis手册 返回并移除列表的第一个元素
	 *@param key 需移除的键名
	 *@return 成功返回键值 否则返回false
	 */
	public function lpop($key) {
		return self::$conn_link->lpop($key, $value);
	}

	/**
	 *同redis手册 返回并移除列表的最后一个元素
	 *@param key 需移除的键名
	 *@return 成功返回键值 否则返回false
	 */
	public function rpop($key) {
		return self::$conn_link->rpop($key, $value);
	}

	/**
	 *同redis手册 返回列表的长度
	 *@param key 需查找的键值
	 *@return 成功返回长度 否则返回false
	 */
	public function lsize($key) {
		return self::$conn_link->lsize($key);
	}

	/**
	 *同redis手册 返回列表中对应键的值 0为第一个 1第二个 ... -1倒数第一 -2倒数第二
	 *@param key 列表的键名
	 *@param num 第几个
	 *@return 若键存在 返回键值 不存在或者键不是指向列表返回false
	 */
	public function lget($key, $num) {
		return self::$conn_link->lget($key, $num);
	}

	/**
	 *同redis手册 修改列表中对应键的值 0为第一个 1第二个 ... -1倒数第一 -2倒数第二
	 *@param key 列表的键名
	 *@param num 第几个
	 *@param value 需更改成的键值
	 *@return 成功返回true 失败返回false
	 */
	public function lset($key, $num, $value) {
		return self::$conn_link->lset($key, $num, $value);
	}

	/**
	 *同redis手册 返回在该区域中的指定键列表中开始到结束存储的指定元素 0为第一个 1第二个 ... -1倒数第一 -2倒数第二
	 *@param key 列表的键名
	 *@param start 开始
	 *@param end 结束
	 *@return 成功返回数组 失败返回false
	 */
	public function lrange($key, $start, $end) {
		return self::$conn_link->lrange($key, $start, $end);
	}

	/**
	 *从列表中从头部开始移除count个匹配的值。如果count为零，所有匹配的元素都被删除。如果count是负数，内容从尾部开始删除。
	 *0为第一个 1第二个 ... -1倒数第一 -2倒数第二
	 *@param key 列表的键名
	 *@param num 需删除键的数量
	 *@param start 从哪个键开始
	 *@return 成功返回删除个数 失败返回false
	 */
	public function lremove($key, $num, $start) {
		return self::$conn_link->lremove($key, $num, $start);
	}

	/**
	 *同redis手册 为key添加值
	 *@param key 键名
	 *@param value 值
	 *@return 若键存在且无该键值返回true 否则返回false
	 */
	public function sadd($key, $value) {
		return self::$conn_link->sadd($key, $value);
	}

	/**
	 *同redis手册 删除key中指定的键值
	 *@param key 键名
	 *@param value 值
	 *@return 成功返回true 失败返回false
	 */
	public function sremove($key, $value) {
		return self::$conn_link->sremove($key, $value);
	}

	/**
	 *同redis手册 将key1中的value移到key2
	 *@param key1 移除
	 *@param key2 移入
	 *@param value 被移除的值
	 *@return 成功返回true 失败返回false
	 */
	public function smove($key1, $key2, $value) {
		return self::$conn_link->smove($key1, $key2, $value);
	}

	/**
	 *同redis手册 查找集合中是否存在某值
	 *@param key 集合的键名
	 *@param value 查找的值
	 *@return 存在返回true 不存在返回false
	 */
	public function scontains($key, $value) {
		return self::$conn_link->scontains($key, $value);
	}

	/**
	 *同redis手册 查找集合中存储键的数量
	 *@param key 查找的集合的键名
	 *@return 成功返回数量 失败返回false
	 */
	public function ssize($key) {
		return self::$conn_link->ssize($key);
	}

	/**
	 *同redis手册 随机移除集合中一个值并返回该值
	 *@param key 需处理的键名
	 *@return 成功返回键值 失败返回false
	 */
	public function spop($key) {
		return self::$conn_link->spop($key);
	}

	/**
	 *同redis手册 随机返回集合中的一个值
	 *@param key 需处理的集合名
	 *@return 返回键值
	 */
	public function srandmember($key) {
		return self::$conn_link->srandmember($key);
	}

	/**
	 *同redis手册 返回两个集合的交集
	 *@param key1 集合1
	 *@param key2 集合2
	 *@return 成功返回交集 某个键不存在返回false
	 */
	public function sinter($key1, $key2) {
		return self::$conn_link->sinter($key1, $key2);
	}

	/**
	 *同redis手册 执行sinter命令并把结果存至新键
	 *@param key1 集合1
	 *@param key2 集合2
	 *@return 成功返回交集 某个键不存在返回false
	 */
	public function sinterstore($key, $key1, $key2) {
		return self::$conn_link->sinterstore($key, $key1, $key2);
	}

	/**
	 *同redis手册 返回指定集合的并集
	 *@param key 结果存至的键名
	 *@param key1 集合1
	 *@param key2 集合2
	 *@return 成功返回true 某个键不存在返回false
	 */
	public function sunion($key1, $key2) {
		return self::$conn_link->sunion($key1, $key2);
	}

	/**
	 *同redis手册 执行sunion命令并把结果存至新键
	 *@param key 结果存至的键名
	 *@param key1 集合1
	 *@param key2 集合2
	 *@return 成功返回true 某个键不存在返回false
	 */
	public function sunionstore($key, $key1, $key2) {
		return self::$conn_link->sunionstore($key, $key1, $key2);
	}

	/**
	 *同redis手册 返回第一个集合存在且其他集合不存在的集
	 *@param key1 集合1
	 *@param key2 集合2
	 *@return 成功返回集合 某个键不存在返回false
	 */
	public function sdiff($key1, $key2) {
		return self::$conn_link->sdiff($key1, $key2);
	}

	/**
	 *同redis手册 执行sdiff命令并把结果存至新键
	 *@param key 结果存至的键名
	 *@param key1 集合1
	 *@param key2 集合2
	 *@return 成功返回true 某个键不存在返回false
	 */
	public function sdiffstore($key, $key1, $key2) {
		return self::$conn_link->sdiffstore($key, $key1, $key2);
	}

	/**
	 *同reids手册 返回集合中的内容
	 *@param key 集合的键名
	 *@return 集合中的内容 array
	 */
	public function smembers($key) {
		return self::$conn_link->smembers($key);
	}
	/**
	 *同reids手册 判断value是否在集合中
	 *@param key 集合的键名
	 *@param value 需查找的值
	 *@return 存在返回1 不存在返回0
	 */
	public function sismember($key, $value) {
		return self::$conn_link->sismember($key, $value);
	}

	/**
	 *同redis手册 多键赋值
	 *@param array 格式 array('key'=>'value','key1'=>'value1'......)
	 *@return 成功返回true 失败返回false
	 */
	public function mset($array) {
		return self::$conn_link->mset($array);
	}
	/**
	 *同redis手册 有序集合赋值
	 *@param key 键名
	 *@param value 键值
	 *@param score 分数
	 *@return 成功返回true 失败返回false
	 */
	public function zadd($key, $value, $score) {
		return self::$conn_link->zadd($key, $score, $value);
	}
	/**
	 *同redis手册 查找有序集合成员个数
	 *@param key 键名
	 *@return 成功返回成员个数 键不存在或不是有序集合返回0
	 */
	public function zcard($key) {
		return self::$conn_link->zcard($key);
	}
	/**
	 *同redis手册 查找分数在区间min-max间成员个数
	 *@param key 键名
	 *@param min 最低分
	 *@param max 最高分
	 *@return 成功返回成员个数 键不存在或不是有序集合返回0
	 */
	public function zcount($key, $min, $max) {
		return self::$conn_link->zcount($key, $min, $max);
	}
	/**
	 *同redis手册 将value的分数+score
	 *@param key 键名
	 *@param value 键值
	 *@param score 需+的分数 -数则为减
	 *@return 若键值不存在则插入 若键不为有序集合返回false
	 */
	public function zincrby($key, $value, $score) {
		return self::$conn_link->zincrby($key, $score, $value);
	}
	/**
	 *同redis手册 查找value在集合中的分数排名
	 *@param key 键名
	 *@param value 键值
	 *@return 返回对应排名 若键不为有序集合返回false
	 */
	public function zrank($key, $value) {
		return self::$conn_link->zrank($key, $value);
	}

	/**
	 *同redis手册 移除集合中对应的value
	 *@param key 键名
	 *@param value 键值数组 array('member1','member2'.....);
	 *@return 返回删除个数 若键不为有序集合返回false
	 */
	public function zrem($key, $value) {
		if (is_array($value)) {
			$value = explode(' ', $value);
		}
		return self::$conn_link->zrem($key, $value);
	}
	/**
	 *同redis手册 查找集合中对应的value的分数
	 *@param key 键名
	 *@param value 键值
	 *@return 返回键值的分数 若键不为有序集合返回false
	 */
	public function zscore($key, $value) {
		return self::$conn_link->zscore($key, $value);
	}
	/**
	 *同redis手册 查找集合中start至stop间的元素
	 *@param key 键名
	 *@param start 开始坐标
	 *@param stop  终止坐标
	 *@return 返回对应元素 若键不为有序集合返回false
	 */
	public function zrange($key, $start, $stop, $withscores = "WITHSCORES") {
		return self::$conn_link->zrang($key, $start, $stop, $withscores);
	}

	public function hset($hash_key, $key, $value) {
		return self::$conn_link->hset($hash_key, $key, $value);
	}

	public function hmset($key, $array) {
		return self::$conn_link->hmset($key, $array);
	}

	public function hmget($key, $array) {
		$list = [];
		$data = self::$conn_link->hmget($key, $array);
		foreach ($data as $k => $v) {
			$list[$array[$k]] = $v;
		}
		return $list;
	}
	public function hgetall($key) {
		return self::$conn_link->hgetall($key);
	}
}

class Cache {
	public static function factory() {
		return RedisServer::get_instance();
	}
}