<?php
/**
 * Simple Yourls PHP-API
 * @author: Alexandre Alouit <alexandre.alouit@gmail.com>
 */

class yourls {

	/**
	 * Timeout for complete connection in seconds
	 * @type: int
	 */
	private $timeout = 6;

	/**
	 * Timeout for first byte connection in seconds
	 * @type: int
	 */
	private $connectionTimeout = 3;

	/**
	 * Delay when we have many links in micro secondes
	 * @type: int
	 */
	private $delay = 100000;

	private $server = NULL;
	private $signature = NULL;
	private $username = NULL;
	private $password = NULL;
	public $content = "";
	public $link = "";
	public $stats = null;
	public $data = null;
	public $data_debug = null;

	/**
	 * @params: server (string), username (string), password (string)
	 */
	public function __construct($server, $signature, $username = null, $password = null) {
		if(!function_exists('json_decode')) {
			die("PECL json required.");
		}
		if(!function_exists('curl_init')) {
			die("PHP-Curl required.");
		}

		$this->server = $server;
		$this->signature = $signature;
		//$this->username = $username;
		//$this->password = $password;

	}

	/**
	 * CURL Connection
	 * @params: url (string)
	 * @return: url shortening (string)
	 */
	public function connect($action,$inputUrl = null) {
		$buffer = curl_init();
		curl_setopt($buffer, CURLOPT_URL, $this->server);
		curl_setopt($buffer, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
		curl_setopt($buffer, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($buffer, CURLOPT_HEADER, 0);
		curl_setopt($buffer, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($buffer, CURLOPT_POST, 1);
		// TODO switching here
		switch ($action) {
			case 'value':
				$roptions = array(
					'shorturl' => $inputUrl, 
					'signature' => $this->signature,
					//'username' => $this->username,
					//'password' => $this->password, 
					'format' => 'json', 
					'action' => $action
					);
				break;
			
			default:
				$roptions = array('url' => $inputUrl,
					'shorturl' => $inputUrl, 
					'signature' => $this->signature,
					//'username' => $this->username,
					//'password' => $this->password, 
					'format' => 'json', 
					'action' => $action
					);
							break;
		}
		curl_setopt($buffer, CURLOPT_POSTFIELDS, $roptions);

		$data = curl_exec($buffer);
		curl_close($buffer);

		$data = json_decode($data);
		$this->data_debug = $data;
		$this->data = $data->link;
		//die(var_dump($inputUrl));

		$this->verify($data);
		return $data;
	}

	/**
	 * Shorten for content (with multiple links)
	 * Replace all links in current content
	 * @params: url (string)
	 * @return: url shortening (string)
	 */
	public function content($data) {
		foreach($this->search($data) as $key => $toReplace) {
			$byReplace = $this->link($toReplace);
			$data = $this->replace($data, $toReplace, $byReplace);

			if(!is_null($this->delay)) {
				usleep($this->delay);
			}
		}

		$this->content = $data;
		return $this->content;
	}

	/**
	 * Shortener by link
	 * @params: url (string)
	 * @return: url shortening (string)
	 */
	public function link($inputUrl) {
		$data = $this->connect('shorturl',$inputUrl);
		//die(var_dump($data));
		$this->link = $data->shorturl;
		return $this->link;
	}


	/**
	 * DB-stats by link
	 * @params: url (string)
	 * @return: url shortening (string)
	 */
	public function link_stats($shortUrl) {
		$data = $this->connect('url-stats',$shortUrl);
		//die(var_dump($data));
		$this->stats = $data->{'url-stats'};
		return $this->stats;
	}


	/**
	 * DB-stats by link
	 * @params: url (string)
	 * @return: url shortening (string)
	 */
	public function stats($inputUrl) {
		$data = $this->connect('db-stats');
		//die(var_dump($data));
		$this->stats = $data->{'db-stats'};
		return $this->stats;
	}

	/**
	 * Check return request is valid and job is done
	 */
	private function verify($data) {
		if($data->status != "success" && $data->statusCode != 200) {
			return FALSE;
		}
	}

	/**
	 * Search link in content
	 * @params: content (string)
	 * @return: founds (array)
	 */
	private function search($data) {
		preg_match_all("_(^|[\s.:;?\-\]<\(])(https?://[-\w;/?:@&=+$\|\_.!~*\|'()\[\]%#,☺]+[\w/#](\(\))?)(?=$|[\s',\|\(\).:;?\-\[\]>\)])_i", $data, $return);
		return array_map('trim', $return[0]);
	}

	/**
	 * Replace link in content
	 * @params: content (string), toReplace (array), byReplace (array)
	 * @return: content (sring)
	 */
	private function replace($content, $toReplace, $byReplace) {
		return str_replace($toReplace, $byReplace, $content);
	}

}
?>
