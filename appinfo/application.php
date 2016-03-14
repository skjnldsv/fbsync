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

use OC\AppFramework\Utility\SimpleContainer;
use OCP\AppFramework\App;
use OCP\Share;
use OCP\IContainer;
use OCP\AppFramework\IAppContainer;
use OCA\FbSync\App\Contacts;
use OCA\FbSync\Controller\PageController;
use OCA\FbSync\Controller\FacebookController;
use OCA\FbSync\Controller\ContactsController;
use OCP\IRequest;
use OCA\DAV\CardDAV\CardDavBackend;

class Application extends App {
	
	 /**
	 * An array holding the current users address books.
	 * @var array
	 */
	static $appname = 'fbsync';
	public $user;
	
	const MAXPICTURESIZE = 720; // Fix for too big vcards not getting properly synced
	const JAROWINKLERMAX = 85;  // Percent for Jaro-Winkler match tolerance
	
	public function __construct ($user=false, array $urlParams=array()) {
		
		parent::__construct(self::$appname, $urlParams);
        $container = $this->getContainer();
		
		// Add the FBID field
		CardDavBackend::$indexProperties[]='FBID';
		
		// Fix for userID
		if(!$user) {
			$this->user = \OCP\User::getUser();
		} else {
			$this->user = $user;
		}
		
		
		/**
		 * CardDavBackend
		 */
		$container->registerService('CardDavBackend', function(IContainer $c) {
			$db = $c->getServer()->getDatabaseConnection();
			$dispatcher = $c->getServer()->getEventDispatcher();
			$principal = new \OCA\DAV\Connector\Sabre\Principal(
				$c->getServer()->getUserManager(),
				$c->getServer()->getGroupManager()
			);
			return new CardDavBackend($db, $principal, $dispatcher);
		});
		 
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
				$c->query('Request'),
				$c->query('OCP\ICache'),
				$c->query('userHome')
			);
		});


		/**
		 * Contacts
		 */
		$container->registerService('Contacts', function(IContainer $c) {
			return new Contacts(
				$c->query('FacebookController'),
				$c->query('CardDavBackend'),
				$this->user
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
		 * ContactsController
		 */
		$container->registerService('ContactsController', function(IContainer $c) {
			return new ContactsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('Contacts')
			);
		});
		
	}
  
   

}

