<?php
/**
 * ownCloud - Addressbook
 *
 * @author Jakob Sack
 * @copyright 2011 Jakob Sack mail@jakobsack.de
 * @copyright 2012 Thomas Tanghus <thomas@tanghus.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
/*
 *
 * The following SQL statement is just a help for developers and will not be
 * executed!
 *
 * CREATE TABLE contacts_cards (
 * id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
 * addressbookid INT(11) UNSIGNED NOT NULL,
 * fullname VARCHAR(255),
 * carddata TEXT,
 * uri VARCHAR(100),
 * lastmodified INT(11) UNSIGNED
 * );
 */

namespace OCA\FbSync;

use Sabre\VObject;
use OCA\FbSync\AppInfo\Application as App;

/**
 * This class manages our vCards
 */
class VCard {
	/**
	 * @brief Returns all cards of an address book
	 * @param integer $id
	 * @param integer $offset
	 * @param integer $limit
	 * @param array $fields An array of the fields to return. Defaults to all.
	 * @return array|false
	 *
	 * The cards are associative arrays. You'll find the original vCard in
	 * ['carddata']
	 */
	public static function all($id, $offset=null, $limit=null, $fields = array(),$bOnlyVCard=false) {
		$result = null;

		$qfields = count($fields) > 0
			? '`' . implode('`,`', $fields) . '`'
			: '*';
		
		$addWhere='';
		if($bOnlyVCard){
			$addWhere="AND `component` = 'VCARD' ";
		}
			
		if(is_array($id) && count($id)) {
			$id_sql = join(',', array_fill(0, count($id), '?'));
			$sql = "SELECT * FROM `".App::$ContactsTable."` WHERE `addressbookid` IN (".$id_sql.") ".$addWhere." ORDER BY LOWER(`fullname`) ";
			try {
				$stmt = \OCP\DB::prepare($sql, $limit, $offset);
				$result = $stmt->execute($id);
				if (\OCP\DB::isError($result)) {
					\OCP\Util::writeLog(App::$appname, __METHOD__. 'DB error: ' . \OCP\DB::getErrorMessage($result), \OCP\Util::ERROR);
					return false;
				}
			} catch(\Exception $e) {
				\OCP\Util::writeLog(App::$appname, __METHOD__.', exception: ' . $e->getMessage(), \OCP\Util::ERROR);
				return false;
			}
		} elseif(is_int($id) || is_string($id)) {
			try {
				$sql = "SELECT * FROM `".App::$ContactsTable."` WHERE `addressbookid` = ? ".$addWhere."  ORDER BY LOWER(`fullname`) ";
				$stmt = \OCP\DB::prepare($sql, $limit, $offset);
				$result = $stmt->execute(array($id));
				if (\OCP\DB::isError($result)) {
					\OCP\Util::writeLog(App::$appname, __METHOD__. 'DB error: ' . \OCP\DB::getErrorMessage($result), \OCP\Util::ERROR);
					return false;
				}
			} catch(\Exception $e) {
				\OCP\Util::writeLog(App::$appname, __METHOD__.', exception: '.$e->getMessage(), \OCP\Util::ERROR);
				\OCP\Util::writeLog(App::$appname, __METHOD__.', ids: '. $id, \OCP\Util::DEBUG);
				return false;
			}
		} else {
			\OCP\Util::writeLog(App::$appname, __METHOD__. '. Addressbook id(s) argument is empty: '.print_r($id, true), \OCP\Util::DEBUG);
			return false;
		}
		$cards = array();
		if(!is_null($result)) {
			while( $row = $result->fetchRow()) {
				
				// Prevent undefined indexes if using the default contacts app
				if(isset($row['bcompany']) && isset($row['lastname'])) {
					
					if($row['bcompany']){
						$row['sortFullname'] = mb_substr($row['fullname'],0,3,"UTF-8");
					} else {
						if($row['lastname'] !== ''){	
							$row['sortFullname'] = mb_substr($row['lastname'],0,3,"UTF-8");
						} else {
							$row['sortFullname'] = mb_substr($row['surename'],0,3,"UTF-8");
						}
					}
					
				} else {
					$row['lastname'] = '';
					$row['bcompany'] = false;
				}
				
				if($row['fullname'] == '' && $row['lastname'] == ''){
					$row['fullname'] = 'unknown';
					$row['sortFullname'] = mb_substr($row['fullname'],0,3,"UTF-8");
				}
				$cards[] = $row;
				
			}
		}
		return $cards;
	}
	
	public static function compareContactsLastname($a, $b) {
			return \OCP\Util::naturalSortCompare($a['sortLastname'], $b['sortLastname']);
	}
	
	public static function compareContactsFullname($a, $b) {
			return \OCP\Util::naturalSortCompare($a['sortFullname'], $b['sortFullname']);
	}


	/**
	 * @brief Returns a card
	 * @param integer $id
	 * @param array $fields An array of the fields to return. Defaults to all.
	 * @return associative array or false.
	 */
	public static function find($id, $fields = array() ) {
		if(count($fields) > 0 && !in_array('addressbookid', $fields)) {
			$fields[] = 'addressbookid';
		}
		try {
			$qfields = count($fields) > 0
				? '`' . implode('`,`', $fields) . '`'
				: '*';
			$stmt = \OCP\DB::prepare( 'SELECT ' . $qfields . ' FROM `'.App::$ContactsTable.'` WHERE `id` = ?' );
			$result = $stmt->execute(array($id));
			if (\OCP\DB::isError($result)) {
				\OCP\Util::writeLog(App::$appname, __METHOD__. 'DB error: ' . \OCP\DB::getErrorMessage($result), \OCP\Util::ERROR);
				return false;
			}
		} catch(\Exception $e) {
			\OCP\Util::writeLog(App::$appname, __METHOD__.', exception: '.$e->getMessage(), \OCP\Util::ERROR);
			\OCP\Util::writeLog(App::$appname, __METHOD__.', id: '. $id, \OCP\Util::DEBUG);
			return false;
		}

		$row = $result->fetchRow();
		if($row) {
			try {
				$addressbook = Addressbook::find($row['addressbookid']);
			} catch(\Exception $e) {
				\OCP\Util::writeLog(App::$appname, __METHOD__.', exception: '.$e->getMessage(), \OCP\Util::ERROR);
				\OCP\Util::writeLog(App::$appname, __METHOD__.', id: '. $id, \OCP\Util::DEBUG);
				throw $e;
			}
		}
		return $row;
	}

	/**
	* VCards with version 2.1, 3.0 and 4.0 are found.
	*
	* If the VCARD doesn't know its version, 3.0 is assumed and if
	* option UPGRADE is given it will be upgraded to version 3.0.
	*/
	const DEFAULT_VERSION = '3.0';

	/**
	* The vCard 2.1 specification allows parameter values without a name.
	* The parameter name is then determined from the unique parameter value.
	* In version 2.1 e.g. a phone can be formatted like: TEL;HOME;CELL:123456789
	* This has to be changed to either TEL;TYPE=HOME,CELL:123456789 or TEL;TYPE=HOME;TYPE=CELL:123456789 - both are valid.
	*
	* From: https://github.com/barnabywalters/vcard/blob/master/barnabywalters/VCard/VCard.php
	*
	* @param string value
	* @return string
	*/
	public static function paramName($value) {
		static $types = array (
				'DOM', 'INTL', 'POSTAL', 'PARCEL','HOME', 'WORK',
				'PREF', 'VOICE', 'FAX', 'MSG', 'CELL', 'PAGER',
				'BBS', 'MODEM', 'CAR', 'ISDN', 'VIDEO',
				'AOL', 'APPLELINK', 'ATTMAIL', 'CIS', 'EWORLD',
				'INTERNET', 'IBMMAIL', 'MCIMAIL',
				'POWERSHARE', 'PRODIGY', 'TLX', 'X400',
				'GIF', 'CGM', 'WMF', 'BMP', 'MET', 'PMB', 'DIB',
				'PICT', 'TIFF', 'PDF', 'PS', 'JPEG', 'QTIME',
				'MPEG', 'MPEG2', 'AVI',
				'WAVE', 'AIFF', 'PCM',
				'X509', 'PGP');
		static $values = array (
				'INLINE', 'URL', 'CID');
		static $encodings = array (
				'7BIT', 'QUOTED-PRINTABLE', 'BASE64');
		$name = 'UNKNOWN';
		if (in_array($value, $types)) {
			$name = 'TYPE';
		} elseif (in_array($value, $values)) {
			$name = 'VALUE';
		} elseif (in_array($value, $encodings)) {
			$name = 'ENCODING';
		}
		return $name;
	}

	/**
	 * @brief edits a card
	 * @param integer $id id of card
	 * @param Sabre\VObject\Component $card  vCard file
	 * @return boolean true on success, otherwise an exception will be thrown
	 */
	public static function edit($id, VObject\Component $card) {
		$oldcard = self::find($id);
		if (!$oldcard) {
			\OCP\Util::writeLog(App::$appname, __METHOD__.', id: '
				. $id . ' not found.', \OCP\Util::DEBUG);
			throw new \Exception(
				'Could not find the vCard with ID.' . $id
			);
		}
		if(is_null($card)) {
			return false;
		}
		// NOTE: Owner checks are being made in the ajax files, which should be done
		// inside the lib files to prevent any redundancies with sharing checks
		$addressbook = Addressbook::find($oldcard['addressbookid']);
		if ($addressbook['userid'] != \OCP\User::getUser()) {
			$sharedAddressbook = \OCP\Share::getItemSharedWithBySource(App::SHAREADDRESSBOOK,App::SHAREADDRESSBOOKPREFIX.$oldcard['addressbookid'],\OCP\Share::FORMAT_NONE, null, true);
			$addressbook_permissions = 0;
			$contact_permissions = 0;
			if ($sharedAddressbook) {
				$addressbook_permissions = $sharedAddressbook['permissions'];
			}
			$permissions = max($addressbook_permissions, $contact_permissions);
			if (!($permissions & \OCP\PERMISSION_UPDATE)) {
				throw new \Exception(
					'You do not have the permissions to edit this contact.'
				);
			}
		}
		//App::loadCategoriesFromVCard($id, $card);
        $sComponent='VCARD';
		//\OCP\Util::writeLog(App::$appname,'XXXX: '.$card->{'X-ADDRESSBOOKSERVER-KIND'}->getValue(), \OCP\Util::DEBUG);
       if(isset($card->{'X-ADDRESSBOOKSERVER-KIND'})){
       	   $sComponent='GROUP';
       }
	   
	    $fn = '';
		$lastname = '';
		$surename = '';	
	
			
		if(isset($card->N)){
			$temp=explode(';',$card->N);
			if(!empty($temp[0])){	
				$lastname = $temp[0];
				$surename = $temp[1].' ';
			}
			if(empty($temp[0])){
				$surename = $temp[1].' ';
			}
		}

		$organization = '';
		if(isset($card->ORG)){
			$temp=explode(';',$card->ORG);	
			$organization = 	$temp[0];
		}
		
		$bCompany = isset($card->{'X-ABSHOWAS'}) ? 1 : 0;
		if($bCompany && $organization !== ''){
			$card->FN = $organization;
			$fn = $organization;
		} else {
			if($lastname !== '' || $surename !== ''){	
				$fn = $surename.$lastname;
				$card->FN = $fn;
			}
		}
		
		$bGroup = isset($card->CATEGORIES) ? 1 : 0;
		$now = new \DateTime;
		$card->REV = $now->format(\DateTime::W3C);

		$data = $card->serialize();
		
		$stmt = \OCP\DB::prepare( 'UPDATE `'.App::$ContactsTable.'` SET `fullname` = ?, `carddata` = ?, `lastmodified` = ? WHERE `id` = ?' );
		try {
			$result = $stmt->execute(array($fn, $data, time(), $id));
			if (\OCP\DB::isError($result)) {
				\OCP\Util::writeLog(App::$appname, __METHOD__. 'DB error: ' . \OCP\DB::getErrorMessage($result), \OCP\Util::ERROR);
				return false;
			}
		} catch(\Exception $e) {
			\OCP\Util::writeLog(App::$appname, __METHOD__.', exception: '. $e->getMessage(), \OCP\Util::ERROR);
			\OCP\Util::writeLog(App::$appname, __METHOD__.', id'.$id, \OCP\Util::DEBUG);
			return false;
		}

//		App::cacheThumbnail($oldcard['id']);
//		App::updateDBProperties($id, $card);
//		Addressbook::touch($oldcard['addressbookid']);
//		App::loadCategoriesFromVCard($id, $card);
		//\OC_Hook::emit('\OCA\Contacts\VCard', 'post_updateVCard', $id);
		return true;
	}
	
	/**
	 * @brief returns the calendarid of an object
	 * @param integer $id
	 * @return integer
	 */
	public static function getAddressbookid($id) {
		$vcard = self::find($id,array('addressbookid'));
		return $vcard['addressbookid'];
	}

	/*
	 * No differences in db between the contacts+ app and the contacts app. No alt function needed
	 *
	 * From Contactsplus
	 * https://github.com/libasys/contactsplus/blob/master/lib/app.php#L721
	 *
	 * Update the Properties table
	 * @param $contactid int
	 * @param $vcard  vcard object
	 */
	public static function updateDBProperties($contactid, $vcard = null) {
		$stmt = \OCP\DB::prepare('DELETE FROM `'.App::$ContactsProbTable.'` WHERE `contactid` = ?');
		try {
			$stmt->execute(array($contactid));
		} catch(\Exception $e) {
			\OCP\Util::writeLog(App::$appname, __METHOD__.
				', exception: ' . $e->getMessage(), \OCP\Util::ERROR);
			\OCP\Util::writeLog(App::$appname, __METHOD__.', id: '
				. $id, \OCP\Util::DEBUG);
			throw new \Exception(
				'There was an error deleting properties for this contact.'
			);
		}

		if(is_null($vcard)) {
			return;
		}

		$stmt = \OCP\DB::prepare( 'INSERT INTO `'.App::$ContactsProbTable.'` '
			. '(`userid`, `contactid`,`name`,`value`,`preferred`) VALUES(?,?,?,?,?)' );
		foreach($vcard->children as $property) {
			if(!in_array($property->name, App::$index_properties)) {
				continue;
			}
			$preferred = 0;
			foreach($property->parameters as $parameter) {
				if($parameter->name == 'TYPE' && strtoupper($parameter->getValue()) == 'PREF') {
					$preferred = 1;
					break;
				}
			}
			try {
				$result = $stmt->execute(
					array(
						\OCP\User::getUser(), 
						$contactid, 
						$property->name, 
						$property->getValue(), 
						$preferred,
					)
				);
				if (\OCP\DB::isError($result)) {
					\OCP\Util::writeLog(App::$appname, __METHOD__. 'DB error: ' 
						. \OCP\DB::getErrorMessage($result), \OCP\Util::ERROR);
					return false;
				}
			} catch(\Exception $e) {
				\OCP\Util::writeLog(App::$appname, __METHOD__.', exception: '.$e->getMessage(), \OCP\Util::ERROR);
				return false;
			}
		}
	}
}
