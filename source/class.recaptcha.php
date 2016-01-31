<?php 



class recaptcha {

	public static function check($recaptcha_data = null, $secret = null, $ip = null) {
		if ($secret == null) $secret = $_ENV["google"]["recaptcha"]["secret"];
		if ($ip == null) $ip = $_SERVER["REMOTE_ADDR"];
		if ($recaptcha_data == null) $recaptcha_data = $_POST['g-recaptcha-response'];
		$resp = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".urlencode($secret)."&response=".urlencode($recaptcha_data)."&remoteip=".urlencode($ip));
		$json = json_decode($resp,true);
		return $json["success"];
	}	
	
	public static function html($sitekey = null) {
		echo('<script src="https://www.google.com/recaptcha/api.js"></script><div class="g-recaptcha" data-sitekey="'.$_ENV["google"]["recaptcha"]["sitekey"].'"></div>');
	}
	
}