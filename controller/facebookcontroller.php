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

use OCP\IRequest;
use OCP\AppFramework\Controller;


class FacebookController extends Controller {

	private $userId;

	public function __construct($AppName, IRequest $request, $UserId){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
	}
    
    /**
     * Use and save cookie to do stuff on facebook.
     * Handle the login request.
     */
    private function dorequest($url, $post=false) {
        $ch = curl_init();

        if(is_array($post)) {
            $curlConfig = array(
                CURLOPT_URL             => $url,
                CURLOPT_POST            => 1,
                CURLOPT_RETURNTRANSFER  => 1,
                CURLOPT_COOKIEFILE      => 'cookie.tmp',
                CURLOPT_COOKIEJAR       => 'cookie.tmp',
                CURLOPT_USERAGENT       => '"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6"',
                CURLOPT_FOLLOWLOCATION  => 1,
                CURLOPT_REFERER         => $url,
                CURLOPT_POSTFIELDS      => $post
            );
        } else {
            $curlConfig = array(
                CURLOPT_URL             => $url,
                CURLOPT_RETURNTRANSFER  => 1,
                CURLOPT_COOKIEFILE      => 'cookie.tmp',
                CURLOPT_USERAGENT       => '"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6"',
                CURLOPT_REFERER         => $url
            );
        }
        
        curl_setopt_array($ch, $curlConfig);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        return array($info, $result);
    }

    /**
     * Try to log into Facebook and return results and header (for debug)
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

        if(preg_match('/home/', $html[0]['url'])) {
            $Status = "success";
        } else if(preg_match('/checkpoint/', $html[0]['url'])) {
            $Status = "checkpoint";
        } else if(preg_match('/login/', $html[0]['url'])) {
            $Status = "password";
        } else {
            $Status = "error";
        }

        return array($Status, json_encode($html[0]));

    }

    /**
     * Try to log into Facebook and return results and header (for debug)
     */
    private function getfriends() {
        require("simple_html_dom.php");
        
        $friends = array();
        $page=0;
        $friendLinkFilter = "[href^=/friends/hovercard]";
        $url = 'https://m.facebook.com/friends/center/friends/?ppk=';

        $getdata = $this->dorequest($url.$page);
        $html = str_get_html($getdata[1]);
        if (empty($html)) {
			header('HTTP/1.0 403 Forbidden');
			print_r(array("403 Unauthorized", json_encode($getdata[0])));
			die();
        }
        $main = $html->find('div[id=friends_center_main]', 0);


        // 10 per page. Break when next page empty!
        while(count($main->find('a'.$friendLinkFilter)) != 0) {
            foreach($main->find('a'.$friendLinkFilter) as $friend) {
                // FB ID
                $re = "/uid=([0-9]{5,10})/";
                preg_match($re, $friend->href, $matches);
                $friends[$matches[1]]=$friend->innertext;
            }
            $page++;

            $getdata = $this->dorequest($url.$page);
            $html = str_get_html($getdata[1]);
            $main = $html->find('div[id=friends_center_main]', 0);
        }
        return array("success", json_encode($friends));
    }

	/**
	 * Simple method that posts back the payload of the request
	 * @NoAdminRequired
	 */
	public function login($email, $pass) {
        return $this->fblogin(base64_decode($email), base64_decode($pass));
	}

	/**
	 * Get facebook friends list
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function friends() {
        return $this->getfriends();
	}


}