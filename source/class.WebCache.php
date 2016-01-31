<?php

/**
 * 
 * @author Andreas Kasper <djassi@users.sourceforge.net>
 * @category lucenzo
 * @copyright 2012 by Andreas Kasper
 * @name WebCache
 * @link http://www.plabsi.com Plabsi Weblabor
 * @license FastFoodLicense
 * @version 0.1.121031
 */

class WebCache {
	
	/**
	 * Zähler für die Webanfragen
	 * @static
	 * @var integer
	 */
	private static $WebRequestCounter = 0;
	
	public static $_log = array();

	/**
	 * Macht eine Webanfrage und gibt den Wert zurück. Wenn keine Daten geladen werden können, kommt NULL.
	 * @param string $url Webadresse
	 * @param integer $sec Cachelaufzeit in Sekunden
	 * @param string|mixed $needle Array oder String der Werte, die in der Antwort vorkommen müssen
	 * @return string|null Quellcode der Webseite oder NULL
	 * @static
	 */
	public static function get($url, $sec = 86400, $needle = "") {
		$key = md5($url).".webcache";
		$a = dbcache::read($key, $sec);
		if ($a != null) return $a;
		self::$WebRequestCounter++;
		$start=microtime(true);
		$html = @file_get_contents($url);
		self::$_log[] = array("url" => $url, "time" => microtime(true)-$start);
		$j = true;
		if (is_string($needle) and ($needle != "")) $j = (strpos($html, $needle) !== FALSE);
		if (is_array($needle)) foreach ($needle as $a) if ($j AND (strpos($html, $a) === FALSE)) $j = false;;
		if ($j) { dbcache::write($key, $html); return $html; }
		return null;
	}
	
	public static function getQuick($url) {
		$key = md5($url).".webcache";
		return dbcache::read($key, 36500*86400);
	}
	
	public static function getObject($url, $sec = 86400, $needle = "", $timeout = 0) {
		$key = md5($url).".webcache";
		$a = dbcache::getObject($key, $sec);
		//print_r($a);
		if (isset($a["value"])) return array("created" =>$a["dt_created"], "data" => $a["value"], "from_cache" => true); 
		self::$WebRequestCounter++;
		$start=microtime(true);
		//$html = @file_get_contents($url);
		/*HTML via CURL*/
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)');
			curl_setopt($ch, CURLOPT_URL,$url);
			if ($timeout != 0) {
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $timeout); 
				curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); //timeout in seconds
			}
			$html=curl_exec($ch);
		self::$_log[] = array("url" => $url, "time" => microtime(true)-$start);
		$j = true;
		if (is_string($needle) and ($needle != "")) $j = (strpos($html, $needle) !== FALSE);
		elseif (is_array($needle)) foreach ($needle as $a) if ($j AND (strpos($html, $a) === FALSE)) $j = false;;
		if ($j) { dbcache::write($key, $html); return array("created" =>time(), "data" => $html, "from_cache" => false); }
		/*gibt es vielleicht noch einen veralteten Cache?*/
		$a = dbcache::getObject($key, 9999999999999);
		if (isset($a["value"])) return array("created" =>$a["dt_created"], "data" => $a["value"], "from_cache" => true, "from_oldcache" => true); 
		return null;
	}
	
	public static function getObjectQuick($url) {
		$key = md5($url).".webcache";
		$a = dbcache::getObject($key, 36500*86400);
		if (isset($a["value"])) return array("created" =>$a["dt_created"], "data" => $a["value"], "from_cache" => true); 
		return null;
	}
	
	public static function getBrowser($url, $sec = 86400, $needle = "") {
		$local = $_ENV["basepath"]."/app/cache/".md5($url).".webcache";
		if (!file_exists($local) OR (filemtime($local)+rand($sec/2, $sec*(self::$WebRequestCounter+1)) < time())) {
			self::$WebRequestCounter++;
			$agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT, $agent);
			curl_setopt($ch, CURLOPT_URL,$url);
			$html=curl_exec($ch);

			$j = true;
			if (is_string($needle) and ($needle != "")) $j = (strpos($html, $needle) !== FALSE);
			if (is_array($needle)) foreach ($needle as $a) if ($j AND (strpos($html, $a) === FALSE)) $j = false;;
			if ($j) file_put_contents($local, $html);
		}
		if (!file_exists($local)) return null;
		return file_get_contents($local);
	}
	
	function url_exists($url) { 
		$hdrs = @get_headers($url); 
		return is_array($hdrs) ? preg_match('/^HTTP\\/\\d+\\.\\d+\\s+2\\d\\d\\s+.*$/',$hdrs[0]) : false; 
	} 
	
	public static function get_numrequests() {
		return self::$WebRequestCounter+0;
	}
}