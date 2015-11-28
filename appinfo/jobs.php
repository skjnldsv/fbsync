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

namespace OCA\FbSync\Appinfo;

use OC\BackgroundJob\TimedJob;
use OCA\FbSync\Controller\FacebookController;
use OCA\FbSync\App\Contacts;

class Jobs extends TimedJob {
	
	public function __construct() {
		// Run once per day
		$this->setInterval(60);
		$this->interval = 60;
	}
	
	private function updatePhotos() {
		$users = \OC::$server->getUserManager()->search('');
		$datadir = \OCP\Config::getSystemValue('datadirectory');
		\OCP\Util::writeLog('fbsync', "Cron launched: updating photos...", \OCP\Util::INFO);
		
		foreach($users as $user) {
			
			$synced=0;
			$error=0;
			// Init controllers
			$FbController = new FacebookController('fbsync', null, $datadir.'/'.$user->getUID());
			$ContactsController = new Contacts($FbController);
			// Get contacts
			$contacts = $ContactsController->getList();
			\OCP\Util::writeLog('fbsync', count($contacts)." found.", \OCP\Util::INFO);
			// Update pictures
			foreach($contacts as $contact) {
				if(isset($contact->vcard->FBID)) {
					$photo = $contact->setPhoto();
					if(!$photo['error']) {
						$synced++;
					} else {
						$error++;
					}
				}
			}
			\OCP\Util::writeLog('fbsync', $synced." synced with ".$error." error(s) for the user ".$user->getUID(), \OCP\Util::INFO);
		}
		\OCP\Util::writeLog('fbsync', "End of cron.", \OCP\Util::INFO);
	}
	
	/**
	 * This function can't work properly.
	 * You can't get the cache instance within the cron because no user is connected.
	 * Possible solution: access the cache directly in the filesystem
	 */
	private function updateFriends() {
		$users = \OC::$server->getUserManager()->search('');
		$cache = \OCP\ICacheFactory::create('test');
		$datadir = \OCP\Config::getSystemValue('datadirectory');
		\OCP\Util::writeLog('fbsync', "Cron launched: caching friends...", \OCP\Util::INFO);
		foreach($users as $user) {
			$fbsync = new FacebookController('fbsync', $cache, $datadir.'/'.$user->getUID());
			$friends = $fbsync->reload();
			if(!$friends) {
				\OCP\Util::writeLog('fbsync', "Failed to update friends cache for user ".$user->getUID(), \OCP\Util::INFO);
			} else {
				\OCP\Util::writeLog('fbsync', count($friends)." cached for user ".$user->getUID(), \OCP\Util::INFO);
			}
		}
		\OCP\Util::writeLog('fbsync', "Cron finished.", \OCP\Util::INFO);
		
	}
	
    /**
     * @param array $arguments
     */
    public function run($arguments) {
		if( (\OCP\App::isEnabled('contacts') || \OCP\App::isEnabled('contactsplus') ) && \OCP\App::isEnabled('fbsync') ) {
			$this->updatePhotos();
		} else {
			\OCP\Util::writeLog('fbsync', "App not enabled", \OCP\Util::INFO);
            return;
        }
	}
	
}
?>