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

use OCA\FbSync\AppInfo\Application as FbSyncApp;	

if(\OCP\App::isEnabled('contacts')) {
	$app = new FbSyncApp();
	$c = $app->getContainer();
	
	$navigationEntry = function () use ($c) {
		$urlGenerator = $c->query('OCP\IURLGenerator');
		return [
			'id' => $c->getAppName(),
			'name' => $c->getAppName(),
			'order' => 10,
			'href' => $urlGenerator->linkToRoute('fbsync.page.index'),
			'icon' => $urlGenerator->imagePath(FbSyncApp::$appname, 'app.svg'),
		];
	};
	$c->getServer()->getNavigationManager()->add($navigationEntry);
} else {
	$msg = 'Can not enable the FBSync app because the Contact app is disabled.';
	\OCP\Util::writeLog('fbsync', $msg, \OCP\Util::ERROR);
}

/**
* Cron
*/
//\OC::$server->getJobList()->add('OCA\FbSync\Appinfo\Jobs');
