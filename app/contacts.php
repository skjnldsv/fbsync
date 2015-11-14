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
use \OCA\FbSync\Controller\ContactsController;
use \OCA\Contacts\Utils\TemporaryPhoto;
use \OCP\ICache;
use \OCP\Image;

/**
 * Class Contacts
 * @package OCA\FbSync\App
 */
class Contacts {
	/**
	 * @var array used to cache the parsed contacts for every request
	 */
	private static $contacts;
	/**
	 * @var IManager
	 */
	private $contactsManager;
    
	public function __construct(IManager $contactsManager, ICache $cache) {
		$this->contactsManager = $contactsManager;
		$this->cache = $cache;
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
			
			$max_size=TemporaryPhoto::MAX_SIZE;
			$height=$image->height()>$max_size?$max_size:$image->height();
			$width=$image->width()>$max_size?$max_size:$image->width();
			
			$key = uniqid('photo-');
			$this->cache->set($key, $image->data(), 600);

			return Array(
				"backend" => $backend,
				"addressBookId" => $addressbook,
				"contactId" => $contactID,
				"cachedImage" => $key,
				"h" => $height,
				"w" => $width
			);
		}
		return false;
	}
}