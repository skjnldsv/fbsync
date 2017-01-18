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

namespace OCA\FbSync\App;

use OCP\Image;
use OCA\FbSync\Controller\FacebookController;
use Sabre\VObject;
use OCA\FbSync\AppInfo\Application as App;
use OCA\FbSync\JaroWinkler;

/**
 * Class Contact
 * @package OCA\FbSync\App
 */
class Contact {
	/**
	 * @var FacebookController
	 */
	public $fbController;
	/**
	 * @var Backend
	 */
	public $backend;
	/**
	 * @var integer
	 */
	public $id;
	/**
	 * @var string
	 */
	public $uri;
	/**
	 * @var string
	 */
	public $lastmodified;
	/**
	 * @var string
	 */
	public $etag;
	/**
	 * @var string
	 */
	public $addressbook;
	/**
	 * @var VObject
	 */
	public $vcard;
    
	/**
	* Construct
	* @var FacebookController The facebook controller instance
	* @var integer The contact local id 
	* @var intstringeger The last edit time
	* @var VObject The vcard data
	*/
	public function __construct(
		FacebookController $fbController,
		$backend,
		$id,
		$uri,
		$lastmodified,
		$etag,
		$addressbook,
		$vcard
	) {
		$this->fbController = $fbController;
		$this->backend = $backend;
		$this->id = $id;
		$this->uri = $uri;
		$this->lastmodified = $lastmodified;
		$this->etag = $etag;
		$this->addressbook = $addressbook;
		$this->vcard = $vcard;
	}
    
	/**
	 * Get and set facebook profile picture
	 */
	public function setPhoto(){
		if(isset($this->vcard->FBID)) {
			$img = file_get_contents("https://graph.facebook.com/".$this->getFBID()."/picture?height=1000");

			$image = new Image(base64_encode($img));
			// Center auto crop!!
			$image->centerCrop();

			if($image->height()<100 || $image->width()<100) {

				// Maybe the graph API is disabled ?
				// Let's try to get the pic anyway
				$imgAltUrl = $this->fbController->getPicture_alt($this->getFBID());
				if(!$imgAltUrl) {
					return Array(
						"error" => "Image unreachable",
						"id" => $this->id,
						"name" => $this->getName(),
						"addressbook" => $this->addressbook,
						"img" => $imgAltUrl,
						"photo" => isset($this->vcard->PHOTO),
						"photourl" => $this->getPhoto(100)
					);
				} else if($imgAltUrl == "notfound") {
					return Array(
						"error" => "Wrong FBID, User not found",
						"id" => $this->id,
						"name" => $this->getName(),
						"addressbook" => $this->addressbook,
						"img" => $imgAltUrl,
						"photo" => isset($this->vcard->PHOTO),
						"photourl" => $this->getPhoto(100)
					);
				} else {
					$imgAlt = file_get_contents($imgAltUrl);
					$image = new Image(base64_encode($imgAlt));
					// Center auto crop!!
					$image->centerCrop();
					if($image->height()<100 || $image->width()<100) {
						return Array(
							"error" => "Image too small",
							"id" => $this->id,
							"name" => $this->getName(),
							"addressbook" => $this->addressbook,
							"img" => $imgAltUrl,
							"photo" => isset($this->vcard->PHOTO),
							"photourl" => $this->getPhoto(100)
						);
					}
				}
			}
			// Image too big
			if($image->width()>App::MAXPICTURESIZE || $image->height()>App::MAXPICTURESIZE) {
				$image->resize(App::MAXPICTURESIZE); // Prettier resizing than with browser and saves bandwidth.
			}
			// Image big enough or get without the graph API
			if(isset($this->vcard->PHOTO)) {
				unset($this->vcard->PHOTO);
			}
			// Add and save!
			$this->vcard->add('PHOTO', $image->data(), array('ENCODING' => 'b', 'TYPE' => $image->mimeType()));
			$this->save();
			return Array(
				"error" => false,
				"id" => $this->id,
				"name" => $this->getName(),
				"name" => $this->getName(),
				"addressbook" => $this->addressbook,
				"photo" => isset($this->vcard->PHOTO),
				"photourl" => $this->getPhoto(100),
				"alt_method" => isset($imgAltUrl)?$imgAltUrl:false
			);
		}
	}
    
	/**
	 * Get and set birthday date if not already set
	 */
	public function setBirthday(){
		// We don't want to override data.
		// We only set birthday to people without one defined
		if(!isset($this->vcard->FBID)) {
			return Array(
				"error" => 'No FBID',
				"id" => $this->id,
				"name" => $this->getName(),
				"name" => $this->getName(),
				"addressbook" => $this->addressbook,
				"photo" => isset($this->vcard->PHOTO),
				"photourl" => $this->getPhoto(100)
			);
		} else if(isset($this->vcard->BDAY)) {
			return Array(
				"error" => 'Already have a birthday',
				"id" => $this->id,
				"name" => $this->getName(),
				"name" => $this->getName(),
				"addressbook" => $this->addressbook,
				"photo" => isset($this->vcard->PHOTO),
				"photourl" => $this->getPhoto(100),
				"birthday" => true
			);
		}
		// All good, let's do it
		$birthday = $this->fbController->getBirthday($this->getFBID());
		if(!$birthday) {
			return Array(
				"error" => 'No birthday found',
				"id" => $this->id,
				"name" => $this->getName(),
				"name" => $this->getName(),
				"addressbook" => $this->addressbook,
				"photo" => isset($this->vcard->PHOTO),
				"photourl" => $this->getPhoto(100)
			);
		} else {
			$birthday = date('Y-m-d', strtotime($birthday));
			$this->vcard->add('BDAY', $birthday);
			$this->save();
			return Array(
				"error" => false,
				"id" => $this->id,
				"name" => $this->getName(),
				"name" => $this->getName(),
				"addressbook" => $this->addressbook,
				"photo" => isset($this->vcard->PHOTO),
				"photourl" => $this->getPhoto(100),
				"birthday" => $birthday
			);
		}
	}
	
	/**
	 * update FBID
	 */
	private function updateFBID($fbid=false){
		// Edit or delete?
		if(!$fbid) {
			unset($this->vcard->FBID);
		} else {
			if(isset($this->vcard->PHOTO)) {
				unset($this->vcard->FBID);
			}
			$this->vcard->add('FBID',$fbid);
		}
		return $this->save();
	}
	
	/**
	 * set Birthday
	 */
	public function updateorsetBirthday($timestamp){
		if(isset($this->vcard->BDAY)) {
			unset($this->vcard->BDAY);
		}
		$this->vcard->add('BDAY', $timestamp);
		return $this->save();
	}
	
	/**
	 * Set FBID
	 */
	public function setFBID($fbid) {
		return $this->updateFBID($fbid);
	}
	
	/**
	 * Get contact photo
	 */
	public function getPhoto($size=40) {
		if(!isset($this->vcard->PHOTO)) {
			return false;
		} else {
			$image = new Image(base64_encode((string)$this->vcard->PHOTO));
			$image->resize($size);
			return 'data:'.$image->mimeType().';base64,'.$image->__toString();
		}
	}
	
	/**
	 * Set FBID
	 */
	private function save() {
		return $this->backend->updateCard($this->addressbook, $this->uri, $this->vcard->serialize());
	}
	
	/**
	 * Remove FBID
	 */
	public function delFBID($fbid) {
		return $this->updateFBID();
	}
	
	/**
	 * Delete Photo
	 */
	public function delPhoto() {
		unset($this->vcard->PHOTO);
		return $this->save();
	}
	
	/**
	 * Delete Birthday
	 */
	public function delBday() {
		unset($this->vcard->BDAY);
		return $this->save();
	}
	
	/**
	 * Get Name
	 */
	public function getName() {
		return (string)$this->vcard->FN;
	}
	/**
	 * Get FBID
	 */
	public function getFBID() {
		return (string)$this->vcard->FBID;
	}
	
	/**
	 * Match exacts name
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function Jaro($string) {
		return JaroWinkler::Jaro(strtolower($this->getName()), $string);
	}
		
}
?>