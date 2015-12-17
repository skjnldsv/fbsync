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

use OCP\Image;
use OCA\FbSync\Controller\FacebookController;
use Sabre\VObject;
use OCA\FbSync\AppInfo\Application as App;
use OCA\FbSync\VCard;
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
	 * @var integer
	 */
	public $id;
	/**
	 * @var string
	 */
	public $addressbook;
	/**
	 * @var string
	 */
	public $lastmodified;
	/**
	 * @var VObject
	 */
	public $vcard;
    
	/**
	* Construct
	* @var FacebookController The facebook controller instance
	* @var integer The contact local id 
	* @var string The adressbook backend (usually "local")
	* @var intstringeger The last edit time
	* @var VObject The vcard data
	*/
	public function __construct(
		FacebookController $fbController,
		$id,
		$addressbook,
		$lastmodified,
		VObject $vcard
	) {
		$this->fbController = $fbController;
		$this->id = $id;
		$this->addressbook = $addressbook;
		$this->lastmodified = $lastmodified;
		$this->backend = 'local';
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
						"backend" => $this->backend,
						"addressbook" => $this->addressbook,
						"img" => $imgAltUrl,
						"photo" => isset($this->vcard->PHOTO)
					);
				} else if($imgAltUrl == "notfound") {
					return Array(
						"error" => "Wrong FBID, User not found",
						"id" => $this->id,
						"name" => $this->getName(),
						"backend" => $this->backend,
						"addressbook" => $this->addressbook,
						"img" => $imgAltUrl,
						"photo" => isset($this->vcard->PHOTO)
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
							"backend" => $this->backend,
							"addressbook" => $this->addressbook,
							"img" => $imgAltUrl,
							"photo" => isset($this->vcard->PHOTO)
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
				"backend" => $this->backend,
				"addressbook" => $this->addressbook,
				"photo" => isset($this->vcard->PHOTO),
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
				"backend" => $this->backend,
				"addressbook" => $this->addressbook,
				"photo" => isset($this->vcard->PHOTO)
			);
		} else if(isset($this->vcard->BDAY)) {
			return Array(
				"error" => 'Already have a birthday',
				"id" => $this->id,
				"name" => $this->getName(),
				"name" => $this->getName(),
				"backend" => $this->backend,
				"addressbook" => $this->addressbook,
				"photo" => isset($this->vcard->PHOTO),
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
				"backend" => $this->backend,
				"addressbook" => $this->addressbook,
				"photo" => isset($this->vcard->PHOTO)
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
				"backend" => $this->backend,
				"addressbook" => $this->addressbook,
				"photo" => isset($this->vcard->PHOTO),
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
	 * Set FBID
	 */
	public function setFBID($fbid) {
		return $this->updateFBID($fbid);
	}
	
	/**
	 * Get photo URL
	 */
	public function getPhotoUrl($size=false) {
		if(App::$contactPlus) {
			$photo = "/index.php/apps/contactsplus/getcontactphoto/".$this->id;						 	
		} else {

			$photo = "/index.php/apps/contacts/addressbook/".
				$this->backend."/".
				$this->addressbook."/contact/".
				$this->id."/photo";
		}
		
		if(!$size) {
			header('Location: '.$photo);
		} else {
			header('Location: '.$photo.'?maxSize='.$size);
		}
		return $photo;
	}
	
	/**
	 * Get photo URL
	 */
	public function getPhoto($size=40) {
		$image = new Image($this->vcard->PHOTO);
		$image->resize($size);
//		return base64_encode($this->vcard->PHOTO);
		return 'data:'.$image->mimeType().';base64,'.$image->__toString();
	}
	
	/**
	 * Set FBID
	 */
	private function save() {
		Vcard::updateDBProperties($this->id, $this->vcard);
		return VCard::edit($this->id, $this->vcard);
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