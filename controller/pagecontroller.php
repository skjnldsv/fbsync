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
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;


class PageController extends Controller {

	private $userId;

	public function __construct($AppName, IRequest $request, $UserId){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
     */
    

    /**
	 * @NoCSRFRequired
	 */
	public function index() {
		$params = ['user' => $this->userId];
		return new TemplateResponse('fbsync', 'status', $params);  // templates/status.php
	}

	/**
	 * @NoCSRFRequired
	 */
	public function match() {
		$params = ['user' => $this->userId];
		return new TemplateResponse('fbsync', 'match', $params);  // templates/match.php
	}
    
    /**
	 * @NoCSRFRequired
	 */
	public function sync() {
		$params = ['user' => $this->userId];
		return new TemplateResponse('fbsync', 'sync', $params);  // templates/sync.php
	}

}