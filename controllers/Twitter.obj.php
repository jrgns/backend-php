<?php
class Twitter extends TableCtl {
	private $oauth = false;

	public function __construct() {
		$this->oauth = OAuth::getInstance('twitter');
	}
	
	public function action_request_auth() {
		$token = $this->oauth->getAuthToken();
		if ($token) {
			$_SESSION['OAuthAuthToken'] = $token;
			$url = Backend::getConfig('twitter.oauth.authorize.url');
			$url .= '?oauth_token=' . OAuth::encode($token['oauth_token']);
			Controller::redirect($url);
		} else if (array_key_exists('OAuthAuthToken', $_SESSION)) {
			unset($_SESSION['OAuthAuthToken']);
		}
		return $token;
	}

	public function action_authorized() {
		$auth_t = array_key_exists('OAuthAuthToken', $_SESSION) ? $_SESSION['OAuthAuthToken'] : false;
		if ($auth_t) {
			$access_t = $this->oauth->getAccessToken($auth_t);
			if ($access_t) {
				Backend::addSuccess('Sucessfully logged into Twitter');
				$data = array(
					'screen_name'  => $access_t['screen_name'],
					'twitter_id'   => $access_t['user_id'],
					'oauth_token'  => $access_t['oauth_token'],
					'oauth_secret' => $access_t['oauth_token_secret'],
					'active'       => 1,
				);
				$twit = new TwitterObj();
				if ($twit->replace($data)) {
				} else {
					Backend::addError('Could not record Twitter Auth information');
				}
				if (!empty($_SESSION['TwitterRedirect'])) {
					$url = $_SESSION['TwitterRedirect'];
					unset($_SESSION['TwitterRedirect']);
					Controller::redirect($url);
				}
			} else {
				Backend::addError('Could not get Access Token');
			}
		} else {
			Backend::addError('No Authentication Token');
		}
		return true;
	}

	public function action_tweet($msg) {
		return TwitterAPI::tweet($msg);
	}

	public function html_tweet($result) {
		if ($result) {
			Backend::addSuccess('Tweeted!');
			Backend::addContent(var_export($result, true));
		}
	}
}

