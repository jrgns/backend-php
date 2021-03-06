<?php
class reCAPTCHA {
	public static $error_msg = false;

	public static function show() {
		$key = ConfigValue::get('recaptcha.PublicKey');
		if (!empty(self::$error_msg)) {
			$key .= '&error=' . self::$error_msg;
		}
		echo <<< END
<script>
RecaptchaOptions = {
	theme : 'clean'
};
</script>
<script type="text/javascript"
   src="http://api.recaptcha.net/challenge?k=$key">
</script>

<noscript>
   <iframe src="http://api.recaptcha.net/noscript?k=$key"
       height="300" width="500" frameborder="0"></iframe><br>
   <textarea name="recaptcha_challenge_field" rows="3" cols="40">
   </textarea>
   <input type="hidden" name="recaptcha_response_field"
       value="manual_challenge">
</noscript>

END;
	}

	public static function check($challenge, $response) {
		self::$error_msg = false;
		if (empty($challenge) || empty($response)) {
			self::$error_msg = 'Invalid challenge or response';
			return false;
		}
		$params = array(
			'privatekey' => ConfigValue::get('recaptcha.PrivateKey'),
			'remoteip'   => $_SERVER['REMOTE_ADDR'],
			'challenge'  => $challenge,
			'response'   => $response,
		);
		$result = curl_request('http://api-verify.recaptcha.net/verify', $params, array('method' => 'post'));
		if (!$result) {
			self::$error_msg = 'Could not contact reCAPTCHA server';
			return false;
		}
		$result = explode("\n", $result);
		if ($result[0] != 'true') {
			self::$error_msg = $result[1];
			return false;
		}
		return true;
	}

	public static function translate($error) {
		switch ($error) {
		case 'incorrect-captcha-sol':
			return 'Invalid CAPTCHA response.';
			break;
		default:
			return $error;
			break;
		}
	}
}
