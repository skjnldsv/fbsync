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

namespace OCA\FbSync\App;

use \OCP\Contacts\IManager;
use \OCP\Files\IRootFolder;
use \OCA\FbSync\Controller\ContactsController;
use \OCA\Contacts\Utils\TemporaryPhoto;
use \OCP\ICache;
use \OCP\Image;

/**
 * Class Contacts
 * @package OCA\FbSync\App
 */
class Contacts {
	const APP=1;
	const INTEGRATED=2;
	/**
	 * @var array used to cache the parsed contacts for every request
	 */
	private static $contacts;
	/**
	 * @var array used to cache the parsed initConvs for every request
	 */
	private static $initConvs;
	/**
	 * @var IManager
	 */
	private $contactsManager;
	/**
	 * @var IRootFolder
	 */
	private $rootFolder;
	public $viewType;
    
	public function __construct(IManager $contactsManager, IRootFolder $rootFolder, ICache $cache) {
		$this->contactsManager = $contactsManager;
		$this->rootFolder = $rootFolder;
		$this->setViewType();
		$this->cache = $cache;
	}
    
	private function setViewType(){
		$requestUri = \OCP\Util::getRequestUri();
		if(substr($requestUri, -5) === 'chat/'){
			$this->viewType = self::APP;
		} else {
			$this->viewType = self::INTEGRATED;
		}
	}
    
	/**
	 * Retrieves all contacts from the ContactsManager and parse them to a
	 * usable format.
	 * @return array Returns array with contacts, contacts as a list and
	 * contacts as an associative array
	 */
	public function getContacts(){
		if(count(self::$contacts) === 0){
			$cm = $this->contactsManager;
			$result = $cm->search('',array('FN'));
            return $result;
		}
		return self::$contacts;
	}
    
	/**
	 * Retrieves a single contact from the ContactsManager.
	 * @param int id of the desired contact
	 * @return array
	 */
	public function getContact($contactID){
		if(is_string($contactID) && (int)$contactID != 0){
			$result = $this->getContacts();
			foreach ($result as $r) {
				if($r['id'] == $contactID) {
					return $r;
				}
			}
			return false;
		}
		return false;
	}
    
	/**
	 * Retrieves all contacts from the ContactsManager and parse them to a
	 * usable format.
	 * @return array Returns array with contacts, contacts as a list and
	 * contacts as an associative array
	 */
	public function setPhoto($contactID, $FBID, $backend, $addressbook){
		if(is_string($contactID) && is_string($FBID) && (int)$contactID != 0 && (int)$FBID != 0 ){
			$tmpfname = tempnam("/tmp", "UL_IMAGE");
			$img = file_get_contents("https://graph.facebook.com/$FBID/picture?height=1000");
			file_put_contents($tmpfname, $img);

			$base64 = "PHOTO;ENCODING=b;TYPE=$type;".base64_encode($img);
			
			$image = new Image(base64_encode($img));
			// Center auto crop!!
			$image->centerCrop();
			
			if($image->height()<100 || $image->width()<100) {
				return Array(
					"error"=>'Image too small',
					"backend" => $backend,
					"addressBookId" => $addressbook,
					"contactId" => $contactID,);
			}
			
			$key = uniqid('photo-');
			$this->cache->set($key, $image->data(), 600);

			return Array(
				"backend" => $backend,
				"addressBookId" => $addressbook,
				"contactId" => $contactID,
				"cachedImage" => $key
			);
		}
		return false;
	}
}