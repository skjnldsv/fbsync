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

use \OCP\AppFramework\Controller;
use \OCP\IRequest;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\Contacts\IManager;
use \OCP\IConfig;
use \OCA\FbSync\App\Contacts;

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
		Contacts $app,
		IManager $cm,
		IConfig $config
	){
		parent::__construct($appName, $request);
		$this->app = $app;
		$this->cm = $cm;
		$this->config = $config;
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @return TemplateResponse
	 */
	public function index() {
		session_write_close();
		$contacts = $this->app->getContacts();
		$backends = $this->app->getBackends();
		$backendsToArray = array();
		foreach($backends as $backend){
			$backendsToArray[$backend->getId()] = $backend->toArray();
		}
		$initConvs = $this->app->getInitConvs();
		$params = array(
			"initvar" => json_encode(array(
				"contacts" => $contacts['contacts'],
				"contactsList" => $contacts['contactsList'],
				"contactsObj" => $contacts['contactsObj'],
				"backends" => $backendsToArray,
				"initConvs" => $initConvs,
				"avatars_enabled" => $this->config->getSystemValue('enable_avatars', true)
			)),
 		);
		return new TemplateResponse($this->appName, 'main', $params);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return JSONResponse
	 */
	public function contacts(){
		session_write_close();
		return new JSONResponse($this->app->getContacts());
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return JSONResponse
	 */
	public function getcontact($id){
		session_write_close();
		return new JSONResponse($this->app->getContact($id));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return JSONResponse
	 */
	public function setphoto($id, $fbid, $backend, $addressbook){
		session_write_close();
		return new JSONResponse($this->app->setPhoto($id, $fbid, $backend, $addressbook));
	}

	/**
	 * @NoAdminRequired
	 * @return JSONResponse
	 */
	public function addContact($contacts){

		$addressbooks = $this->cm->getAddressBooks();
		$key = array_search('Contacts', $addressbooks);

		// Create contacts
		$ids = array();
		foreach ($contacts as $contact){
			$r = $this->cm->createOrUpdate($contact, $key);
			$ids[] = $r->getId();
		}

		// Return just created contacts as contacts which can be used by the Chat app
		$contacts =  $this->app->getContacts();
		$newContacts = array();
		foreach ($ids as $id){
			$newContacts[$id] = $contacts['contactsObj'][$id];
		}

		return $newContacts;
	}

	/**
	 * @NoAdminRequired
	 * @return JSONResponse
	 */
	public function removeContact($contacts){
		// Create contacts
		$ids = array();
		foreach ($contacts as $contact){
			$this->cm->delete($contact, 'local:1');
		}

	}


	/**
	 * @NoAdminRequired
	 * @return JSONResponse
	 */
	public function initVar(){
		session_write_close();
		$contacts = $this->app->getContacts();
		$backends = $this->app->getBackends();
		$backendsToArray = array();
		foreach($backends as $backend){
			$backendsToArray[$backend->getId()] = $backend->toArray();
		}
		$initConvs = $this->app->getInitConvs();
		return array(
			"contacts" => $contacts['contacts'],
			"contactsList" => $contacts['contactsList'],
			"contactsObj" => $contacts['contactsObj'],
			"backends" => $backendsToArray,
			"initConvs" => $initConvs,
			"avatars_enabled" => $this->config->getSystemValue('enable_avatars', true)
		);
	}

}
