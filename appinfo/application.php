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
 
namespace OCA\FbSync\AppInfo;

use \OC\AppFramework\Utility\SimpleContainer;
use \OCP\AppFramework\App;
use \OCP\Share;
use \OCP\IContainer;
use \OCP\AppFramework\IAppContainer;
use \OCA\FbSync\App\Contacts;
use \OCA\FbSync\Controller\PageController;
use \OCA\FbSync\Controller\FacebookController;
use \OCP\IRequest;

class Application extends App {
	
	 /**
	 * An array holding the current users address books.
	 * @var array
	 */
	static $appname = 'fbsync';
	public $user;
	
	// No PHOTO in $ContactsProbTable
	public static $index_properties = array('BDAY', 'UID', 'N', 'FN', 'TITLE', 'ROLE', 'NOTE', 'NICKNAME', 'ORG',
											'CATEGORIES', 'EMAIL', 'TEL', 'IMPP', 'ADR', 'URL', 'GEO', 'CLOUD', 'FBID');
	// From contacts+
	static $ContactsTable;
	static $AddrBookTable;
	static $ContactsProbTable;
	static $ShareAddressBook;
	static $ShareAddressBookPREFIX;
	static $contactPlus=false;
	const MAXPICTURESIZE = 800;
	const JAROWINKLERMAX = 85; // Percent for Jaro-Winkler match tolerance
	
	public function __construct ($user=false, array $urlParams=array()) {
		
		parent::__construct(self::$appname, $urlParams);
        $container = $this->getContainer();
		if(!$user) {
			$this->user = \OCP\User::getUser();
		} else {
			$this->user = $user;
		}
		\OCP\Util::writeLog('fbsync', $this->user, \OCP\Util::INFO);
		
		
		// Contact+ compatibility
		if(\OCP\App::isEnabled('contactsplus')) {
			self::$ContactsTable = '*PREFIX*conplus_cards';
			self::$AddrBookTable = '*PREFIX*conplus_addressbooks';
			self::$ContactsProbTable = '*PREFIX*conplus_cards_properties';
			self::$ShareAddressBook = 'cpladdrbook';
			self::$ShareAddressBookPREFIX = '';
			self::$contactPlus=true;
		} else {
			self::$ContactsTable = '*PREFIX*contacts_cards';
			self::$AddrBookTable = '*PREFIX*contacts_addressbooks';
			self::$ContactsProbTable = '*PREFIX*contacts_cards_properties';
			self::$ShareAddressBook = 'addressbook';
			self::$ShareAddressBookPREFIX = '';
		}
		 
		/**
		 * User home folder
		 */
		$container -> registerService('userHome', function(IContainer $c) {
			$datadir = \OCP\Config::getSystemValue('datadirectory');
			return $datadir.'/'.$this->user;
			
		});
		
		/**
		 * Controller
		 */
		$container->registerService('FacebookController', function(IContainer $c) {
			return new FacebookController(
				$c->query('AppName'),
				$c->query('OCP\IRequest'),
				$c->query('OCP\ICache'),
				$c->query('userHome')
			);
		});
		
		/**
		 * PageController
		 */
		$container->registerService('PageController', function(IContainer $c) {
			return new PageController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('Contacts'),
				$c->query('FacebookController')
			);
		});


		/**
		 * Contacts
		 */
		$container->registerService('Contacts', function(IContainer $c) {
			return new Contacts(
				$c->query('OCP\Contacts\IManager'),
				$c->query('OCP\ICache'),
				$c->query('FacebookController'),
				$c->query('ContactContainer')
			);
		});

		/**
		* Cron
		*/
	//	\OC::$server->getJobList()->add('\OCA\FbSync\Controller\FacebookController', 'reload');


	}
  
   

}

