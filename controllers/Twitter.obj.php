<?php
class Twitter extends AreaCtl {
	public function action_request_auth() {
		$token = OAuth::getAuthToken();
		if ($token) {
			$_SESSION['OAuthAuthToken'] = $token;
			$url = Backend::getConfig('oauth.authorize.url');
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
			$access_t = OAuth::getAccessToken($auth_t);
			if ($access_t) {
				$_SESSION['TwitterInfo'] = array('user_id' => $access_t['user_id'], 'screen_name' => $access_t['screen_name']);
				$_SESSION['OAuthAccessToken'] = array('oauth_token' => $access_t['oauth_token'], 'oauth_token_secret' => $access_t['oauth_token_secret']);
				Controller::addSuccess('Twitter Username is ' . $access_t['screen_name']);
				if (!empty($_SESSION['TwitterRedirect'])) {
					$url = $_SESSION['TwitterRedirect'];
					unset($_SESSION['TwitterRedirect']);
					Controller::redirect($url);
				}
			} else {
				die('Could not get access token');
				Controller::addError('Could not get Access Token');
			}
		} else {
			Controller::addError('No Authentication Token');
		}
		return true;
	}

	public function action_tweet($msg) {
		return TwitterAPI::tweet($msg);
	}

	public function html_tweet($result) {
		if ($result) {
			Controller::addSuccess('Tweeted!');
			Controller::addContent(var_export($result, true));
		}
	}
}

