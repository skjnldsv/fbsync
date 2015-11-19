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


Application::$l10n = \OC::$server->getL10N('fbsync');

class Application extends App {
	
	 /**
	 * An array holding the current users address books.
	 * @var array
	 */
	public static $appname = 'fbsync';
	public static $l10n;
	// No PHOTO in $ContactsProbTable
	public static $index_properties = array('BDAY', 'UID', 'N', 'FN', 'TITLE', 'ROLE', 'NOTE', 'NICKNAME', 'ORG',
											'CATEGORIES', 'EMAIL', 'TEL', 'IMPP', 'ADR', 'URL', 'GEO', 'CLOUD', 'FBID');

	// From contacts+
	const THUMBNAIL_PREFIX = 'contacts-photo-';
	const THUMBNAIL_SIZE = 28;
	const ContactsTable='*PREFIX*contacts_cards';
	const AddrBookTable='*PREFIX*contacts_addressbooks';
	const ContactsProbTable='*PREFIX*contacts_cards_properties';
	const SHAREADDRESSBOOK = 'addressbook';
	const SHAREADDRESSBOOKPREFIX = '';
	const SHARECONTACT = 'cplcontact';
	const SHARECONTACTPREFIX = '';
	const MAXPICTURESIZE = 800;
	const JAROWINKLERMAX = 85; // Percent for Jaro-Winkler match tolerance
	
	public function __construct (array $urlParams=array()) {
		
		parent::__construct(self::$appname, $urlParams);
        $container = $this->getContainer();
	
		 
		/**
		 * User home folder
		 */
		$container -> registerService('userHome', function(IContainer $c) {
			$server = $c->query('ServerContainer');
			return $server->getUserSession()->getUser()->getHome();
			
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
		 * L10N
		 */
		$container -> registerService('L10N', function(IContainer $c) {
			return $c -> query('ServerContainer') -> getL10N($c -> query('AppName'));
		});


		/**
		 * Contacts
		 */
		$container->registerService('Contacts', function($c) {
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

