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

use OCA\FbSync\App\Contact;
use OCA\FbSync\Controller\FacebookController;
use Sabre\VObject;
use OCA\DAV\CardDAV\CardDavBackend;
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

	/**
	 * @var CardDavBackend
	 */
	private $backend;

	/**
	 * @var String
	 */
	private $userUID;
    
	public function __construct(FacebookController $fbController, CardDavBackend $backend, $user) {
		$this->fbController = $fbController;
		$this->backend = $backend;
		$this->userUID = $user;
	}
	
	/**
	* Sort Contact item by Name
	*/
	private function sortContacts(Contact $a, Contact $b) {
		return strcasecmp($a->getName(), $b->getName());
	}
    
	/**
	 * Retrieves the user addressbooks IDs
	 */
	private function getAddressBooksIDsForUser() {
		$activeAddressbooksArray = $this->backend->getAddressBooksForUser('principals/users/' . $this->userUID);
		foreach ($activeAddressbooksArray as $activeAddressbook) {
			$activeAddressbooks[] = $activeAddressbook['id'];
		}
		return $activeAddressbooks;
	}
    
	/**
	 * Retrieves all contacts from the ContactsManager and parse them to a
	 * usable format.
	 */
	public function getList(){
		$activeAddressbooks = $this->getAddressBooksIDsForUser();
		$contacts = Array();
		foreach($activeAddressbooks as $activeAddressbook) {
			foreach($this->backend->getCards($activeAddressbook) as $contact) {
				$contacts[$contact['id']] = new Contact(
					$this->fbController,
					$this->backend,
					$contact['id'],
					$contact['uri'],
					$contact['lastmodified'],
					$contact['etag'],
					$activeAddressbook,
					VObject\Reader::read($contact["carddata"])
				);
			}
		}
		uasort($contacts, array($this, 'sortContacts'));
		return $contacts;
	}
    
	
	public function getCards() {
		return $this->backend->getCards(1);
	}
	/**
	 * Retrieves a single contact from the ContactsManager and parse
	 * them to a usable format
	 * TODO Could be a problem if too many contacts.
	 * @return array 
	 */
	public function getContact($id){
		$activeAddressbooks = $this->getAddressBooksIDsForUser();
		foreach($activeAddressbooks as $activeAddressbook) {
			
			$contact = $this->backend->getCard($activeAddressbook, $this->backend->getCardUri($id));
			
			if($contact != null) {
				return new Contact(
					$this->fbController,
					$this->backend,
					$contact['id'],
					$contact['uri'],
					$contact['lastmodified'],
					$contact['etag'],
					$activeAddressbook,
					VObject\Reader::read($contact["carddata"])
				);
			}
		}
		
		return false;
	}
	
	/**
	 * Retrieve and set the facebook photo
	 * @NoAdminRequired
	 */
	public function setPhoto($id) {
		return $this->getContact($id)->setPhoto();
	}
	
	/**
	 * Retrieve and set the facebook photo
	 * @NoAdminRequired
	 */
	public function setBirthday($id) {
		return $this->getContact($id)->setBirthday();
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
	 * Delete all the profile pictures
	 * @NoAdminRequired
	 */
	public function deletePhotos() {
		$contacts = $this->getList();
		$count = 0;
		foreach($contacts as $contact) {
			if(isset($contact->vcard->PHOTO)) {
				$contact->delPhoto();
				$count++;
			}
		}
		return $count;
	}
	
	/**
	 * Delete all the birthdays
	 * @NoAdminRequired
	 */
	public function deleteBdays() {
		$contacts = $this->getList();
		$count = 0;
		foreach($contacts as $contact) {
			if(isset($contact->vcard->BDAY)) {
				$contact->delBday();
				$count++;
			}
		}
		return $count;
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
	public function getPhoto($id, $size=60) {
		$contact = $this->getContact($id);
		if(!$contact) {
			return false;
		}
		return $contact->getPhoto($size);
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
			$contactsName[strtolower($contact->getName())]=$contact;
		}
		$friends = $this->fbController->getfriends();
		// Parse all friends
		foreach($friends as $fbid => $friend) {
			$friend = strtolower($friend);
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
	 * Update birthdays with a secondary system
	 * @NoAdminRequired
	 */
	public function updateBirthdays() {
		$contacts = $this->getList();
		$birthdays = $this->fbController->getBirthdays();
		
		$syncedContacts = array();
		
		foreach ($contacts as $contact) {
			
			if(isset($contact->vcard->FBID)) {
				$FBID = (string)$contact->vcard->FBID;
				
				if(isset($birthdays[$FBID])) {
					
					$bdate = $birthdays[$FBID];
					$BDAY = strtotime($contact->vcard->BDAY);
															
					// Check if birthday exist or if lower than the value we have
					// if greater, then we have a better value 
					if($BDAY > $bdate || is_null($contact->vcard->BDAY)) {
						$birthday = date('Y-m-d', $bdate);
						$contact->updateorsetBirthday($birthday);
						$syncedContacts[] = Array(
							"error" => false,
							"id" => $contact->id,
							"name" => $contact->getName(),
							"name" => $contact->getName(),
							"addressbook" => $contact->addressbook,
							"photo" => isset($contact->vcard->PHOTO),
							"photourl" => $contact->getPhoto(100),
							"birthday" => $birthday
						);
					}
				}
			}
		}
		
		return $syncedContacts;
		
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
					$jaro = $contact->Jaro(strtolower($friend))*100;
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
	
	/**
	 * Match exacts name but use the "People You May Know" list
	 * @NoAdminRequired
	 */
	public function suggestMatch() {
		$contacts = $this->getList();
		$contactsName = Array();
		$edited=0;
		// List contacts by Name
		// Use strtolower to avoid errors based on typo
		foreach ($contacts as $contact) {
			$contactsName[strtolower($contact->getName())]=$contact;
		}
		$friends = $this->fbController->getsuggestedFriends();
		// Parse all friends
		foreach($friends as $fbid => $friend) {
			$friend = strtolower($friend);
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
}