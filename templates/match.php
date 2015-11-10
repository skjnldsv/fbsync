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

script('fbsync', 'login');
script('fbsync', 'match');
script('contacts', 'storage');
style('fbsync', 'fbsync');
?>

<div id="app">
	<div id="app-navigation">
		<?php print_unescaped($this->inc('part.navigation')); ?>
		<?php print_unescaped($this->inc('part.settings')); ?>
	</div>

	<div id="app-content">
		<div id="controls" class="clear">
			<div class="controls-left">
				<div class="controls_item button last crumb"><h2>Match found contacts</h2></div>
				<button id="togglematch">Display only contacts without match</button>
				<button id="perfectmatch">Match exacts names</button>
			</div>
			<div class="controls-right">
				<div class="controls_item button" id="syncstatus">Loading...</div>
				<div class="controls_item button" id="fbstatus">Loading...</div>
			</div>
		</div>
		<div id="loader">
			<div class="spinner">
				<div class="rect1"></div>
				<div class="rect2"></div>
				<div class="rect3"></div>
				<div class="rect4"></div>
				<div class="rect5"></div>
			</div>
			<div id="loading-status">Loading contacts...</div>
		</div>
		<div id="contacts-list">
		</div>
	</div>
</div>