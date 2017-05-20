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
	public $limit = 5;

	/**
	 * @params: server (string), username (string), password (string)
	 */
	public function __construct($server, $signature = null, $username = null, $password = null) {
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
	public function connect($action,$arg = []) {
		$inputUrl = $arg['input_url'];
		//$arg['limit'] = $this->limit;
		$buffer = curl_init();
		curl_setopt($buffer, CURLOPT_URL, $this->server);
		curl_setopt($buffer, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
		curl_setopt($buffer, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($buffer, CURLOPT_HEADER, 0);
		curl_setopt($buffer, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($buffer, CURLOPT_POST, 1);
		// TODO switching here
			// constant
		$opt2 = array(
					'signature' => $this->signature,
					//'username' => $this->username,
					//'password' => $this->password, 
					'format' => 'json'					
					);
		switch ($action) {
			case 'shorturl':
				$opt1 = array('url' => $inputUrl , 'keyword' => $arg['keyword'] , 'title' => $arg['title'],'action' => $action );
				break;

			case 'expand':
				$opt1 = array('shorturl' => $inputUrl, 'action' => $action);
				break;

			case 'url_stats':
				$opt1 = array('shorturl' => $inputUrl, 'action' => 'url-stats');
				break;

			case 'stats':
				$opt1 = array('shorturl' => $inputUrl,'filter' => $arg['filter']  , 'limit' => $arg['limit'], 'action' => $action);
				break;

			case 'db_stats':
				$opt1 = ['action' => 'db-stats'];
				break;
			
			default:
				$opt1 = array('url' => $inputUrl, 'action' => $action);
				break;
		}
		$roptions = array_merge($opt1, $opt2);
		//die(var_dump($roptions));
		curl_setopt($buffer, CURLOPT_POSTFIELDS, $roptions);

		$data = curl_exec($buffer);
		curl_close($buffer);

		$data = json_decode($data);
		//die(var_dump($data));
		$this->data_debug = $data;

		if ($data->link) {
				$this->data = $data->link;
		} else {
			$this->data = $data;
		}

		//die(var_dump($inputUrl));

		$this->verify($data);
		return $data;
	}

	/**
	 * Shortener by link
	 * @params: url (string)
	 * @return: url shortening (string)
	 */
	public function link($inputUrl,$inputKeyword = null,$inputTitle = null) {
		
		if (!empty($inputKeyword) XOR !empty($inputTitle)) {
			$arg = array('input_url' => $inputUrl, 'keyword' => $inputUrl , 'title' => $inputTitle);
		} else {
			$arg = array('input_url' => $inputUrl);
		}
		$data = $this->connect('shorturl',$arg);
		//die(var_dump($data));
		$this->link = $data->shorturl;
		return $this->link;
	}


	/**
	 * expand url by link
	 * @params: url (string)
	 * @return: url shortening (string)
	 */
	public function expand($shortUrl) {
		$data = $this->connect('expand' , array( 'input_url' => $shortUrl ) );
		//die(var_dump($data));
		//$this->stats = $data;
		return $data->longurl;
	}


	/**
	 * stats for 1 link
	 * @params: url (string)
	 * @return: url shortening (string)
	 */
	public function link_stats($shortUrl) {
		$data = $this->connect('url_stats', array( 'input_url' => $shortUrl ) );
		//die(var_dump($data));
		$this->stats = $data->link;
		return $this->data;
	}


	/**
	 * all the db stats
	 * @params: url (string)
	 * @return: url shortening (string)
	 */
	public function stats_log($inputUrl, $filter = null, $limit = null) {

		if (empty($filter) XOR empty($limit)) {
			$arg = array('input_url' => $inputUrl);
		} else {
			$arg = array('input_url' => $inputUrl, 'filter' => $filter, 'limit' => $limit );
		}
		$data = $this->connect('stats',$arg);
		//die(var_dump($data));
		$this->stats = $data->stats;
		// filter
		// limit
		return $this->stats;
	}


	/**
	 * DB-stats
	 * @params: url (string)
	 * @return: url shortening (string)
	 */
	public function dbstats() {
		$data = $this->connect('db_stats');
		//die(var_dump($data));
		$this->stats = $data->{'db-stats'};
		return $this->stats;
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
