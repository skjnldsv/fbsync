<?php
/**
 * ownCloud - Addressbook
 *
 * @author Jakob Sack
 * @copyright 2011 Jakob Sack mail@jakobsack.de
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
 * CREATE TABLE contacts_addressbooks (
 * id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
 * userid VARCHAR(255) NOT NULL,
 * displayname VARCHAR(255),
 * uri VARCHAR(100),
 * description TEXT,
 * ctag INT(11) UNSIGNED NOT NULL DEFAULT '1'
 * );
 *
 */

namespace OCA\FbSync;

use OCA\FbSync\AppInfo\Application as App;

/**
 * This class manages our addressbooks.
 */
class Addressbook {
	/**
	 * @brief Returns the list of addressbooks for a specific user.
	 * @param string $uid
	 * @param boolean $active Only return addressbooks with this $active state, default(=false) is don't care
	 * @param boolean $shared Whether to also return shared addressbook. Defaults to true.
	 * @return array or false.
	 */
	public static function all($uid, $active = false, $sync = false) {
		$values = array($uid);
		$active_where = '';
		if ($active) {
			$active_where = ' AND `active` = ?';
			$values[] = 1;
		}
		$sqlOrder='`displayname` ASC';
		
		if($sync === true){
			$sqlOrder='`sync` DESC';
		}
		try {
			$stmt = \OCP\DB::prepare( 'SELECT * FROM `'.App::$AddrBookTable.'` WHERE `userid` = ? ' . $active_where . ' ORDER BY '.$sqlOrder );
			$result = $stmt->execute($values);
			if (\OCP\DB::isError($result)) {
				\OCP\Util::write(App::$appname, __METHOD__. 'DB error: ' . \OCP\DB::getErrorMessage($result), \OCP\Util::ERROR);
				return false;
			}
		} catch(\Exception $e) {
			\OCP\Util::writeLog(App::$appname, __METHOD__.' exception: '.$e->getMessage(), \OCP\Util::ERROR);
			\OCP\Util::writeLog(App::$appname, __METHOD__.' uid: '.$uid, \OCP\Util::DEBUG);
			return false;
		}

		$addressbooks = array();
		while( $row = $result->fetchRow()) {
			$row['permissions'] = \OCP\PERMISSION_ALL;
			$addressbooks[] = $row;
		}
		
		// Because contacts app doesnt use their own sql rows...
		if (count($addressbooks)>0) {
			foreach($addressbooks as $key => $addressbook) {
				if(!self::isActive_Alt($addressbook["id"]))
					$addressbooks[$key]['active']=false;
			}
		}
		
		if(!$active && !count($addressbooks)) {
			$id = self::addDefault($uid);
			return array(self::find($id),);
		}
		
		return $addressbooks;
	}
	
	/**
	 * @brief Get active addressbook IDs for a user.
	 * @param integer $uid User id. If null current user will be used.
	 * @return array
	 */
	public static function activeIds($uid = null) {
		if(is_null($uid)) {
			$uid = \OCP\USER::getUser();
		}

		// query all addressbooks to force creation of default if it desn't exist.
		$activeaddressbooks = self::all($uid);
		$ids = array();
		foreach($activeaddressbooks as $addressbook) {
			if($addressbook['active']) {
				$ids[] = $addressbook['id'];
				
			}
		}
		return $ids;
	}

   

	/**
	 * @brief Gets the data of one address book
	 * @param integer $id
	 * @return associative array or false.
	 */
	public static function find($id) {
		try {
			$stmt = \OCP\DB::prepare( 'SELECT * FROM `'.App::$AddrBookTable.'` WHERE `id` = ?' );
			$result = $stmt->execute(array($id));
			if (\OCP\DB::isError($result)) {
				\OCP\Util::writeLog(App::$appname, __METHOD__. 'DB error: ' . \OCP\DB::getErrorMessage($result), \OCP\Util::ERROR);
				return false;
			}
		} catch(\Exception $e) {
			\OCP\Util::writeLog(App::$appname, __METHOD__.', exception: ' . $e->getMessage(), \OCP\Util::ERROR);
			\OCP\Util::writeLog(App::$appname, __METHOD__.', id: ' . $id, \OCP\Util::DEBUG);
			return false;
		}
		$row = $result->fetchRow();
		if(!$row) {
			return false;
		}

		if($row['userid'] != \OCP\USER::getUser() && !\OC_Group::inGroup(\OCP\User::getUser(), 'admin')) {
			$sharedAddressbook = \OCP\Share::getItemSharedWithBySource(App::$ShareAddressBook, App::$ShareAddressBookPREFIX.$id);
			if (!$sharedAddressbook || !($sharedAddressbook['permissions'] & \OCP\PERMISSION_READ)) {
				throw new \Exception(
					'You do not have the permissions to read this addressbook.'
				);
			}
			$row['permissions'] = $sharedAddressbook['permissions'];
		} else {
			$row['permissions'] = \OCP\PERMISSION_ALL;
		}
		return $row;
	}

	/**
	 * @brief Checks if an addressbook is active.
	 * @param integer $id ID of the address book.
	 * @return boolean
	 */
	public static function isActive($id) {
		$sql = 'SELECT `active` FROM `'.App::$AddrBookTable.'` WHERE `id` = ?';
		try {
			$stmt = \OCP\DB::prepare( $sql );
			$result = $stmt->execute(array($id));
			if (\OCP\DB::isError($result)) {
				\OCP\Util::writeLog(App::$appname, __METHOD__. 'DB error: ' . \OCP\DB::getErrorMessage($result), \OCP\Util::ERROR);
				return false;
			}
			$row = $result->fetchRow();
			return (bool)$row['active'];
		} catch(\Exception $e) {
			\OCP\Util::writeLog(App::$appname, __METHOD__.', exception: ' . $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	/**
	 * FROM Contacts https://github.com/owncloud/contacts/blob/master/lib/backend/abstractbackend.php#L421
	 *
	 * @brief Query whether a backend or an address book is active for the contacts app
	 * @param string $addressBookId If null it checks whether the backend is activated.
	 * @return boolean
	 */
	public static function isActive_Alt($addressBookId = null) {

		$key = self::combinedKey($addressBookId);
		$key = 'active_' . $key;

		return !!(\OCP\Config::getUserValue(\OCP\USER::getUser(), 'contacts', $key, 1));
	}
	
	/**
	 * FROM Contacts https://github.com/owncloud/contacts/blob/master/lib/backend/abstractbackend.php#L382
	 *
	 * Creates a unique key for inserting into oc_preferences.
	 * As IDs can have any length and the key field is limited to 64 chars,
	 * the IDs are transformed to the first 8 chars of their md5 hash.
	 * 
	 * @param string $addressBookId.
	 * @param string $contactId.
	 * @throws \BadMethodCallException
	 * @return string
	 */
	protected static function combinedKey($addressBookId = null, $contactId = null) {
		// because we're using the database to get our addressbooks, it's 'local'
		// https://github.com/owncloud/contacts/blob/master/lib/backend/database.php#L43
		$key = 'local';
		if (!is_null($addressBookId)) {

			$key .= '_' . substr(md5($addressBookId), 0, 8);

			if (!is_null($contactId)) {
				$key .= '_' . substr(md5($contactId), 0, 8);
			}

		} else if (!is_null($contactId)) {

			throw new \BadMethodCallException(
				__METHOD__ . ' cannot be called with a contact ID but no address book ID'
			);

		}
		return $key;
	}
}
