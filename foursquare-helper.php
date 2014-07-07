<?php

if (!class_exists ('FoursquareHelperException_v1_0')) {
	class FoursquareHelperException_v1_0 extends Exception {
	}
}

if (!class_exists ('FoursquareHelper_v1_0')) {
	class FoursquareHelper_v1_0 {
		const DATEVERIFIED = '20140701';

		private $base_url = 'https://api.foursquare.com/';
		private $auth_url = 'https://foursquare.com/oauth2/authenticate';
		private $token_url = 'https://foursquare.com/oauth2/access_token';

		private $client_id;
		private $client_secret;
		private $auth_token;

		protected $redirect_url;

		public function __construct($id=NULL, $secret=NULL, $url='', $version='v2') {
			$this->base_url .= $version . '/';
			$this->client_id = $id;
			$this->client_secret = $secret;
			$this->redirect_url = $url;
		}

		public function set_redirect_url($url) {
			$this->redirect_url = $url;
		}

		public function get_public($endpoint, $params=NULL) {
			$url = $this->base_url . trim ($endpoint, '/');
			$params['client_id'] = $this->client_id;
			$params['client_secret'] = $this->client_secret;
			return $this->get ($url, $params);
		}

		public function get_private($endpoint, $params=NULL, $post=NULL) {
			$url = $this->base_url . trim ($endpoint, '/');
			$params['oauth_token'] = $this->auth_token;

			if (!$post) {
				return $this->get ($url, $params);
			}
			else {
				return $this->post ($url, $params);
			}
		}

		public function get_response($json_string) {
			$json = json_decode ($json_string);
			if (!isset ($json->response)) {
				throw new FoursquareHelperException ('Invalid response');
			}

			// TODO: check status code ($json->meta->code) and check HTTP status code
			return $json->response;
		}

		public function set_access_token($token) {
			$this->auth_token = $token;
		}

		public function authentication_link($redirect=NULL) {
			if (0 === strlen ($redirect)) {
				$redirect = $this->redirect_url;
			}

			$params = array (
				'client_id' => $this->client_id,
				'response_type' => 'code',
				'redirect_uri' => $redirect
				);

			return $this->make_url ($this->auth_url, $params);
		}

		public function get_token($code, $redirect=NULL) {
			if (0 === strlen ($redirect)) {
				$redirect = $this->redirect_url;
			}

			$params = array (
				'client_id' => $this->client_id,
				'client_secret' => $this->client_secret,
				'grant_type' => 'authorization_code',
				'redirect_uri' => $redirect,
				'code' => $code
				);

			$result = $this->get ($this->token_url, $params);
			$json = json_decode ($result);
			$this->set_access_token ($json->access_token);
			return $json->access_token;
		}

		private function get($url, $params=NULL) {
			$params['v'] = self::DATEVERIFIED;
			return $this->request ($url, $params, 'GET');
		}

		private function post($url, $params=NULL) {
			$params['v'] = self::DATEVERIFIED;
			return $this->request ($url, $params, 'POST');
		}

		private function make_url($url, $params) {
			if (!empty ($params) && $params) {
				foreach ($params as $key => $value) {
					$key_value[] = $key . '=' . $value;
				}
				$url_params = str_replace (' ', '+', implode ('&', $key_value));
				$url = trim ($url) . '?' . $url_params;
			}

			return $url;
		}

		private function request($url, $params=NULL, $type='GET') {
			if ('GET' == $type) {
				$url = $this->make_url ($url, $params);
			}

			$handle = curl_init ();
			curl_setopt ($handle, CURLOPT_URL, $url);
			curl_setopt ($handle, CURLOPT_RETURNTRANSFER, 1);
			if (isset ($_SERVER['HTTP_USER_AGENT'])) {
				$user_agent = $_SERVER['HTTP_USER_AGENT'];
			}
			else {
				$user_agent = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1';
			}
			curl_setopt ($handle, CURLOPT_USERAGENT, $user_agent);
			curl_setopt ($handle, CURLOPT_TIMEOUT, 30);
			curl_setopt ($handle, CURLOPT_SSL_VERIFYPEER, false);

			if ('POST' == $type) {
				curl_setopt ($handle, CURLOPT_POST, 1);
				if ($params) {
					curl_setopt ($handle, CURLOPT_POSTFIELDS, $params);
				}
			}

			$result = curl_exec ($handle);
			$info = curl_getinfo ($handle);
			curl_close ($handle);

			return $result;
		}

	}	// end-class FoursquareHelper_v1_0
}	// end-if (!class_exists ('FoursquareHelper_v1_0'))

?>
