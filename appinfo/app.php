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

use OCP\AppFramework\App;
use OCA\FbSync\App\Contacts;
use OCA\Contacts\Utils\Properties;

$app = new App('fbsync');
$container = $app->getContainer();

if(\OCP\App::isEnabled('contacts')) {

	$container->query('OCP\INavigationManager')->add(function () use ($container) {
		$urlGenerator = $container->query('OCP\IURLGenerator');
		$l10n = $container->query('OCP\IL10N');
		return [
			'id' => 'fbsync',
			'order' => 10,
			'href' => $urlGenerator->linkToRoute('fbsync.page.index'),
			'icon' => $urlGenerator->imagePath('fbsync', 'app.svg'),
			'name' => $l10n->t('Fb Sync'),
		];
	});
    
    // Add FBID to allowed vcard fields
    Properties::$indexProperties[]='FBID';
    
    /**
     * Contacts
     */
    $container->registerService('Contacts', function($c) {
        return new Contacts(
            $c->query('OCP\Contacts\IManager'),
            $c->query('OCP\Files\IRootFolder'),
            $c->query('OCP\ICache')
        );
    });

} else {
	$msg = 'Can not enable the FBSync app because the Contacts App is disabled.';
	\OCP\Util::writeLog('fbsync', $msg, \OCP\Util::ERROR);
}


