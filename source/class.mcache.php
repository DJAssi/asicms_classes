<?php

class mcache {

	private static $conn = null;
	public static $hit = 0;
	public static $miss = 0;
	
	public static function get($keys) {
		if (defined("debug")) $keys .= "|#debug|";
		if (self::$conn == null) self::init();
		$a = memcache_get(self::$conn, $keys);
		if ($a != "") self::$hit++; else self::$miss++;
		return $a;
	}
	
	public static function set($key, $value, $seconds = 0, $compress = false) {
		if (defined("debug")) $key .= "|#debug|";
		if (self::$conn == null) self::init();
		if ($compress) return memcache_set(self::$conn, $key, $value, MEMCACHE_COMPRESSED, $seconds);
		return memcache_set(self::$conn, $key, $value, 0, $seconds);
	}
	
	public static function opt($key, $CreationHandler, $seconds = 0, $compress = false) {
		if (defined("debug")) $key .= "|#debug|";
		if (self::$conn == null) self::init();
		$a = memcache_get(self::$conn, $keys);
		if ($a != "") {
			self::$hit++; 
			return $a;
		}
		self::$miss++;
		$a = call_user_func($CreationHandler);
		if ($compress) return memcache_set(self::$conn, $key, $a, MEMCACHE_COMPRESSED, $seconds); else memcache_set(self::$conn, $key, $a, 0, $seconds);
		return $a;
	}
	
	public static function optecho($key, $CreationHandler, $seconds = 0, $compress = false) {
		if (defined("debug")) $key .= "|#debug|";
		if (self::$conn == null) self::init();
		$a = memcache_get(self::$conn, $keys);
		if ($a != "") {
			self::$hit++; 
			return $a;
		}
		ob_start();
		call_user_func($CreationHandler);
		$buffer = ob_get_flush();
		if ($compress) return memcache_set(self::$conn, $key, $buffer, MEMCACHE_COMPRESSED, $seconds); else memcache_set(self::$conn, $key, $buffer, 0, $seconds);
	}
	
	public static function getStatistic() {
	if (self::$conn == null) self::init();
	return self::$conn->getExtendedStats();
	}
	
	public static function flush() {
		if (self::$conn == null) self::init();
		return memcache_flush(self::$conn);
	}
	
	public static function init() {
		self::$conn = memcache_connect("127.0.0.1", 11211);
		return true;
	}
	
	



}