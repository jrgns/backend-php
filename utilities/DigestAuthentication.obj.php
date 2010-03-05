<?php
/**
 * Utility class to do Digest Authentication
 *
 * Mostly copied from the PHP Manual: http://php.net/manual/en/features.http-auth.php
 */
class DigestAuthentication {
	private static $instance = false;
	
	private $realm;
	private $message;
	private $callback;
	
	public static function getInstance($callback, $realm = 'Restricted Area', $message = 'No unauthorized access allowed') {
		if (!self::$instance) {
			self::$instance = new DigestAuthentication($callback, $realm, $message);
		}
		return self::$instance;
	}
	
	private function __construct($callback, $realm, $message) {
		if (!is_callable($callback)) {
			throw new Exception('Invalid callback');
		}
		$this->callback = $callback;
		$this->realm    = $realm;
		$this->message  = $message;
	}
	
	public function check() {
		if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
			$this->challenge();
		} else {
			return $this->process();
		}
		return false;
	}

	public function challenge() {
		$nonce = uniqid();
		header('WWW-Authenticate: Digest realm="' . $this->realm . '",qop="auth",nonce="' . $nonce . '",opauq="' . md5($this->realm) . '"');
		header('HTTP/1.0 401 Unauthorized');
		die($this->message);
	}
	
	/**
	 * Process the entered username and password
	 *
	 * The callback function should take the entered username, and return the users password.
	 */
	public function process() {

		if (!($data = $this->parseHTTPDigest($_SERVER['PHP_AUTH_DIGEST']))) {
			return false;
		}
		$password = call_user_func_array($this->callback, array(array($data['username'])));
		if (!$password) {
			return false;
		}

		// generate the valid response
		$A1 = md5($data['username'] . ':' . $this->realm . ':' . $password);
		$A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
		$valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);
		
		if ($data['response'] != $valid_response) {
			return false;
		}
		
		return $data['username'];
	}
	
	private static function parseHTTPDigest($text) {
		// protect against missing data
		$needed_parts = array('nonce'=> 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1);
		$data = array();
		$keys = implode('|', array_keys($needed_parts));

		preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $text, $matches, PREG_SET_ORDER);

		foreach ($matches as $m) {
			$data[$m[1]] = $m[3] ? $m[3] : $m[4];
			unset($needed_parts[$m[1]]);
		}

		return $needed_parts ? false : $data;
	}
}