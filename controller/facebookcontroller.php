<?php
/**
 * ownCloud - fbsync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author NOIJN <fremulon@protonmail.com>
 * @copyright NOIJN 2015
 */

namespace OCA\FbSync\Controller;

use \OCP\IRequest;
use \OCP\AppFramework\Controller;
use \OCP\IUser;
use \OCP\ICache;
use OCA\FbSync\AppInfo\Application as App;
require("simple_html_dom.php");


class FacebookController extends Controller {
		
	/**
	 * @var ICache
	 */
	private $cache;
	
	/**
	 * @var String Cookie location
	 * @var String useragent for requests (fake desktop)
	 */
	private $userHome;
	private $cookieName = '/fbsync.cookie';
	private $userAgent = '"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6"';
	/**
	 * @var Int Friends cache time (24h)
	 * @var Int Friends cache key
	 */
	private $cacheKey;

	public function __construct($AppName, IRequest $request, ICache $cache=null, $userHome){
		parent::__construct($AppName, $request);
		$this->cache = $cache;
		$this->userHome = $userHome;
		$this->cacheKey = "FBfriends-".substr(md5($this->userHome), 0, 8);
	}
    
    /**
     * Use and save cookie to do stuff on facebook.
     * Handle the login request.
	 *
	 * @var string Url of the request
	 * @var bool POST or GET
	 * @var bool FOLLOWLOCATION Enable or not in GET request
     */
    private function dorequest($url, $post=false, $follow=false) {

        $ch = curl_init();
		
		// Create cookie file to prevent write error
		$cookie = fopen($this->userHome.$this->cookieName, "a+") or die("Unable to open file!");
		fclose($cookie);
		
        if(is_array($post)) {
            $curlConfig = array(
                CURLOPT_URL             => $url,
                CURLOPT_POST            => 1,
                CURLOPT_RETURNTRANSFER  => 1,
                CURLOPT_COOKIEFILE      => $this->userHome.$this->cookieName,
                CURLOPT_COOKIEJAR       => $this->userHome.$this->cookieName,
                CURLOPT_USERAGENT       => $this->userAgent,
                CURLOPT_FOLLOWLOCATION  => 1,
                CURLOPT_REFERER         => $url,
                CURLOPT_POSTFIELDS      => $post
            );
        } else {
            $curlConfig = array(
                CURLOPT_URL             => $url,
                CURLOPT_RETURNTRANSFER  => 1,
                CURLOPT_COOKIEFILE      => $this->userHome.$this->cookieName,
                CURLOPT_USERAGENT       => $this->userAgent,
                CURLOPT_REFERER         => $url
            );
			// Prevent unwanted redirection
			if($follow) {
				$curlConfig[CURLOPT_FOLLOWLOCATION]=1;
			}
        }
        
        curl_setopt_array($ch, $curlConfig);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
		
        return array($info, $result);
    }

    /**
     * Try to log into Facebook and return results and header (for debug)
	 * @var string Username
	 * @var string Password
	 * @return Array Status of the facebook connexion and the request data
     */
    private function fblogin($user, $pass) {
        // Submit those variables to the server
        $post_data = array(
            'email' => $user,
            'pass' => $pass,
            'default_persistent' => 1,
            'login' => 'Connexion',
            'version' => 1,
            'lsd' => 'AVqBNk4B',
            'ajax' => '0',
            'm_ts' => time()
        );

        $html = $this->dorequest('https://m.facebook.com/login.php', $post_data);

    	//echo ($html[1]);
    	//die();

        if(preg_match('/home/i', $html[0]['url'])) {
            $Status = "success";
        } else if(preg_match('/checkpoint/i', $html[0]['url'])) {
            $Status = "checkpoint";
        } else if(preg_match('/login\.php$/i', $html[0]['url'])) {
			// already logged in
            $Status = "success";
        } else if(preg_match('/login/i', $html[0]['url'])) {
            $Status = "password";
        } else {
            $Status = "error";
        }

        return array($Status, json_encode($html[0]));

    }

    /**
     * Retrieve the facebook friends list
	 * @var bool Ignore cache and force reload
	 * @return Array Friends with FBID and Names
     */
    public function getfriends($ignoreCache=false) {
		
		// try cache if defined
		if($this->cache) {
			$cachedFriends = json_decode($this->cache->get($this->cacheKey), true);
			if(!empty($cachedFriends) && is_array($cachedFriends) && !$ignoreCache) {
				return $cachedFriends;

			}
		}
		
		if($this->islogged()) {

			$friends = array();
			$page=0;
			$friendLinkFilter = "[href^=/friends/hovercard]";
			$url = 'https://m.facebook.com/friends/center/friends/?ppk=';

			$getdata = $this->dorequest($url.$page);
			$html = str_get_html($getdata[1]);
			if (empty($html)) {
				return false;
			}
			$main = $html->find('div[id=friends_center_main]', 0);
			if (is_null($main)) {
				return false;
			}

			// 10 per page. Break when next page empty!
			while(count($main->find('a'.$friendLinkFilter)) != 0) {
				foreach($main->find('a'.$friendLinkFilter) as $friend) {
					// FB ID
					$re = "/uid=([0-9]{1,20})/";
					preg_match($re, $friend->href, $matches);
					// $friends[fbid]=name
					$friends[(int)$matches[1]]=html_entity_decode($friend->innertext, ENT_QUOTES);
				}
				$page++;

				$getdata = $this->dorequest($url.$page);
				$html = str_get_html($getdata[1]);
				$main = $html->find('div[id=friends_center_main]', 0);
			}
			
			// Alphabetical order
			asort($friends);
			
			// To cache if defined
			if($this->cache) {
				$this->cache->set($this->cacheKey, json_encode($friends));
				\OCP\Util::writeLog('fbsync', count($friends)." friends cached", \OCP\Util::INFO);
			}
			
			return $friends;
			
		} else {
			return false;
		}
    }

    /**
     * Retrieve the 2 first pages of your friends suggestions (24*2 peoples)
	 * Increasing to 3 or more reduce the suggestion relevance
	 * @return Array Friends with FBID and Names
     */
    public function getsuggestedFriends() {
		
		if($this->islogged()) {
			
			$url = 'https://m.facebook.com/friends/center/suggestions/';
			$friends=Array();
			$friendLinkFilter = "[href^=/friends/hovercard]";
			
			$getdata = $this->dorequest($url.$page);
			$html = str_get_html($getdata[1]);
			if (empty($html)) {
				return false;
			}
			$main = $html->find('div[id=friends_center_main]', 0);
			if (is_null($main)) {
				return false;
			}

			// 2 first pages
			for($count=0; $count < 2; $count++) {
				foreach($main->find('a'.$friendLinkFilter) as $friend) {
					// FB ID
					$re = "/uid=([0-9]{1,20})/";
					preg_match($re, $friend->href, $matches);
					// $friends[fbid]=name
					$friends[(int)$matches[1]]=html_entity_decode($friend->innertext, ENT_QUOTES);
				}
				// Let's get the next page link
				$re = "/<a href=\"(\\/friends\\/center\\/suggestions[0-9a-z\\/\\?=&;_]{0,60})#friends_center_main\">/mi"; 
				preg_match_all($re, html_entity_decode($main->innertext, ENT_QUOTES), $matches);
				$url = $matches[1][0];
				$getdata = $this->dorequest('https://m.facebook.com'.$url);
				$html = str_get_html($getdata[1]);
				$main = $html->find('div[id=friends_center_main]', 0);
			}
			
			// No sort because we want to keep the suggestion order
			return $friends;
			
		} else {
			return false;
		}
	}
	

    /**
     * Get picture for user who disabled the graph api
	 * @var integer The Facebook ID
	 * @return string Url of the profile picture
     */
	public function getPicture_alt($fbid) {
		if(!$this->islogged()) {
			return false;
		}
		
		$getdata = $this->dorequest("https://m.facebook.com/$fbid", false, true);
		if (preg_match("/not found/mi", $getdata[1])) {
			return "notfound";
		}
		if (empty($getdata[1])) {
			return false;
		}
		$re = "/(photo\\.php\\?fbid=[0-9]{0,20}&amp;id=[a-z0-9;&.=\\\\]{20,300})\\\"/mi"; 
		preg_match_all($re, $getdata[1], $matches);
		if(empty($matches)) {
			return false;
		}
		$getdata2 = $this->dorequest('https://m.facebook.com/'.$matches[1][0]);
		$re2 = "/<img src=\\\"(https:\\/\\/[\\\"&-_.\\/;=?a-z0-9\\\"]*hphotos[\\\"&-_.\\/;=?a-z0-9\\\"]*)\\\"/mi"; 
		preg_match_all($re2, html_entity_decode($getdata2[1]), $matches2);
		if(empty($matches2)) {
			return false;
		}
		return $matches2[1][0];
	}
	

    /**
     * Get birthday and format it to the Vcard format
	 * @var integer The Facebook ID
	 * @return string Birthday date to Y-m-d format
     */
	public function getBirthday($fbid) {
		if(!$this->islogged()) {
			return false;
		}
		
		$getdata = $this->dorequest("https://m.facebook.com/profile.php?v=info&id=$fbid");
		
		$html = str_get_html($getdata[1]);
		if (is_null($html)) {
			return false;
		}

		$birthdayContainer = $html->find('div[title=Birthday]', 0);
		if (is_null($birthdayContainer)) {
			return false;
		}
		
		$birthday = $birthdayContainer->find('td', 1)->find('div', 0);
		// Empty or not enough data
		// EDIT: disabled, better having only month and day than nothing
		// if (empty($birthday) || date('Y-m-d', $birthday->innertext) == date('Y')) {
		if (is_null($birthday)) {
			return false;
		}
		
		return $birthday->innertext;
	}
	
	/**
	 * Check if logged to facebook
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return bool
	 */
	public function islogged() {
		// No cookie set, we don't need to go further
		if(filesize($this->userHome.$this->cookieName) == 0) {
			return false;
		}
		// Check if redirected to the login page
		$testlogin = $this->dorequest("https://m.facebook.com/friends/");
		if(preg_match('/login/i', $testlogin[0]['redirect_url'])) {
            return false;
        } else {
            return true;
        }
	}

	/**
	 * Login to facebook
	 * @NoAdminRequired
	 */
	public function login($email, $pass) {
        return $this->fblogin(base64_decode($email), base64_decode($pass));
	}

	/**
	 * Login to facebook
	 * @NoAdminRequired
	 */
	public function delCookie() {
        return unlink($this->userHome.$this->cookieName);
	}
	
	/**
	 * Force reload cache
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function reload() {
//		\OCP\Util::writeLog('fbsync', "Cron launched", \OCP\Util::INFO);
		$friends = $this->getfriends(true);
        return is_bool($friends) ? $friends : count($friends);
	}
	
	/**
	 * Get cache info
	 * @NoAdminRequired
	 */
	public function fromCache() {
		if($this->cache) {
			$cachedFriends = json_decode($this->cache->get($this->cacheKey), true);
			return (!empty($cachedFriends) && is_array($cachedFriends));
		} else {
			return false;
		}
	}

}