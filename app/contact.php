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

use OCA\FbSync\Controller\ContactsController;
use OCP\ICache;
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
	 * @var ICache
	 */
	private $cache;
	/**
	 * @var IManager
	 */
	private $contactsManager;
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
	* @var ICache 
	* @var integer The contact local id 
	* @var integer|false The contact facebook id
	* @var string The adressbook backend (usually "local")
	* @var integer The adressbook id
	* @var string The photo url
	*/
	public function __construct(
		IManager $contactsManager,
		ICache $cache,
		FacebookController $fbController,
		integer $id,
		string $addressbook,
		string $lastmodified,
		VObject $vcard
	) {
		$this->contactsManager = $contactsManager;
		$this->cache = $cache;
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
						"img" => $imgAltUrl
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
							"img" => $imgAltUrl
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
				"addressbook" => $this->addressbook
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
			$photo = "index.php/apps/contactsplus/getcontactphoto/".$this->id;						 	
		} else {

			$photo = "index.php/apps/contacts/addressbook/".
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
		return JaroWinkler::Jaro($this->getName(), $string);
	}
		
}
?>