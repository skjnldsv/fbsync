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

use \OCA\FbSync\App\Contact;
use \OCA\FbSync\Controller\FacebookController;
use \OCA\FbSync\VCard;
use \OCA\FbSync\Addressbook;
use Sabre\VObject;
use OCA\FbSync\AppInfo\Application as App;

/**
 * Class Contacts
 * @package OCA\FbSync\App
 */
class Contacts {
	/**
	 * @var FacebookController
	 */
	private $fbController;
    
	public function __construct(FacebookController $fbController) {
		$this->fbController = $fbController;
	}
	
	/**
	* Sort Contact item by Name
	*/
	private function sortContacts(Contact $a, Contact $b) {
		return strcasecmp($a->getName(), $b->getName());
	}
    
	/**
	 * Retrieves all contacts from the ContactsManager and parse them to a
	 * usable format.
	 */
	public function getList(){
		$activeAddressbooks = Addressbook::activeIds();
		$contacts = Array();
		foreach($activeAddressbooks as $activeAddressbook) {
			foreach(VCard::all([$activeAddressbook[0]]) as $contact) {
				$contacts[$contact['id']] = new Contact(
					$this->contactsManager,
					$this->cache,
					$this->fbController,
					$contact['id'],
					$contact['addressbookid'],
					$contact['lastmodified'],
					VObject\Reader::read($contact["carddata"])
				);
			}
		}
		uasort($contacts, array($this, 'sortContacts'));
		return $contacts;
	}
    
	/**
	 * Retrieves a single contact from the ContactsManager and parse
	 * them to a usable format
	 * @return array 
	 */
	public function getContact($id){
		$contact = VCard::find($id);
		$activeAddressbooks=Addressbook::activeIds();
		// Do you have the right to get this contact?
		if(in_array($contact['addressbookid'], $activeAddressbooks)) {
			return new Contact(
				$this->contactsManager,
				$this->cache,
				$this->fbController,
				$contact['id'],
				$contact['addressbookid'],
				$contact['lastmodified'],
				VObject\Reader::read($contact["carddata"])
			);
		} else {
			return false;
		}
	}
	
	/**
	 * Retrieve and set the facebook photo
	 * @NoAdminRequired
	 */
	public function setPhoto($id) {
		return $this->getContact($id)->setPhoto();
	}
	
	/**
	 * Get a list of contact IDs that have a FBID
	 * @NoAdminRequired
	 */
	public function contactsIds() {
		$contacts = $this->getList();
		$idList = Array();
		foreach($contacts as $contact) {
			if(isset($contact->vcard->FBID))
				$idList[] = $contact->id;
		}
		return $idList;
	}
	
	
	/**
	 * Match exacts name
	 * @NoAdminRequired
	 */
	public function setFBID($id, $fbid) {
		return $this->getContact($id)->setFBID($fbid);
	}
	
	/**
	 * Match exacts name
	 * @NoAdminRequired
	 */
	public function delFBID($id) {
		return $this->getContact($id)->delFBID();
	}
	
	/**
	 * Get profile picture url
	 * @NoAdminRequired
	 */
	public function getPhoto($id, $size) {
		return $this->getContact($id)->getPhotoUrl($size);
	}
	
	/**
	 * Match exacts name
	 * @NoAdminRequired
	 */
	public function perfectMatch() {
		$contacts = $this->getList();
		$contactsName = Array();
		$edited=0;
		// List contacts by Name
		foreach ($contacts as $contact) {
			$contactsName[$contact->getName()]=$contact;
		}
		$friends = $this->fbController->getfriends();
		// Parse all friends
		foreach($friends as $fbid => $friend) {
			// Match exact name
			if(isset($contactsName[$friend])) {
				if($contactsName[$friend]->getFBID() != $fbid) {
					$edited++;
					$contactsName[$friend]->setFBID($fbid);
				}
			}
		}
		return $edited;
	}
	
	/**
	 * Match approx name using Jaro-Winkler algorithm
	 * @NoAdminRequired
	 */
	public function approxMatch() {
		$results = Array();
		$contacts = $this->getList();
		$friends = $this->fbController->getfriends();
		$edited=0;
		
		// Build an array of used fbid
		$FBIDs=Array();
		foreach($contacts as $contact) {
			if(isset($contact->vcard->FBID))
				$FBIDs[]=$contact->getFBID();
		}
		
		// Go through all contacts
		foreach($contacts as $contact) {
			// Only if no FBID set (We do not want to override previous macthes)
			if(!in_array($contact->getFBID(), $FBIDs)) {
				foreach($friends as $fbid => $friend) {
					$jaro = $contact->Jaro($friend)*100;
					// Only best matches
					if($jaro > App::JAROWINKLERMAX && !in_array($fbid, $FBIDs)) {
						// Store as integer and big enough so that no results will overwrite another
						$results[round($jaro*100000)][$fbid]=$contact;
					}
				}
			}
		}
		// Order by Jaro score
		krsort($results);
		// Time to parse the results
		foreach($results as $score => $result) {
			foreach($result as $fbid => $contact) {
				// If a contact as been set before, ignore the other potential matches
				if(!in_array($fbid, $FBIDs)) {
					$contact->setFBID($fbid);
					$FBIDs[]=$fbid;
					$edited++;
				}
			}
		}
		
		return $edited;
	}
}