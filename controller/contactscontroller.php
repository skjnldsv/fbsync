<?php
/**
 * ownCloud - fbsync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author NOIJN <skjnldsv@protonmail.com>
 * @copyright NOIJN 2015
 */

namespace OCA\FbSync\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCA\FbSync\App\Contacts;

class ContactsController extends Controller {

	/**
	 * @var Contacts OCA\FbSync\App\Contacts;
	 */
	private $app;

	/**
	 * @var \OCP\IConfig
	 */
	private $config;

	/**
	 * @var \OCP\Contacts\IManager
	 */
	private $cm;

	public function __construct(
		$appName,
		IRequest $request,
		Contacts $app
	){
		parent::__construct($appName, $request);
		$this->app = $app;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return JSONResponse
	 */
	public function perfectMatch(){
		return new JSONResponse($this->app->perfectMatch());
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return JSONResponse
	 */
	public function approxMatch(){
		return new JSONResponse($this->app->approxMatch());
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return JSONResponse
	 */
	public function suggestMatch(){
		return new JSONResponse($this->app->suggestMatch());
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return JSONResponse
	 */
	public function setPhoto($id){
		return new JSONResponse($this->app->setPhoto($id));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return JSONResponse
	 */
	public function setBirthday($id){
		return new JSONResponse($this->app->setBirthday($id));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return JSONResponse
	 */
	public function setBirthdayAlt(){
		return new JSONResponse($this->app->updateBirthdays());
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return JSONResponse
	 */
	public function getPhoto($id, $size){
		return new JSONResponse($this->app->getPhoto($id, $size));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return JSONResponse
	 */
	public function getFbContacts(){
		return new JSONResponse($this->app->contactsIds());
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return JSONResponse
	 */
	public function deletePhotos(){
		return new JSONResponse($this->app->deletePhotos());
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return JSONResponse
	 */
	public function deleteBdays(){
		return new JSONResponse($this->app->deleteBdays());
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return JSONResponse
	 */
	public function updateFBID($id, $fbid){
		// Delete or set?
		if(is_null($fbid)) {
			return new JSONResponse($this->app->delFBID($id));
		} else {
			return new JSONResponse($this->app->setFBID($id, $fbid));
		}
	}

}
