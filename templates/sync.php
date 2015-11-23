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
script('fbsync', 'sync');
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
<!--				<div class="controls_item button last crumb"><h2>Sync profile pictures</h2></div>-->
				<button id="syncall" class="tooltipped-bottom syncbutton"
						title="Will sync profile pictures for people matched with one of your facebook friend">
					Sync pictures
				</button>
				<button id="syncbday" class="tooltipped-bottom syncbutton"
						title="Will sync birthdays only for the contacts who doesn't have one set yet">
					Sync birthdays
				</button>
			</div>
			<div class="controls-right">
				<div class="controls_item button tooltipped-bottom" id="syncstatus"
					 title="Only the contacts previously matched will be used here">Loading...</div>
			</div>
		</div>
		<div id="loader" class="hidden">
			<div class="spinner">
				<div class="rect1"></div>
				<div class="rect2"></div>
				<div class="rect3"></div>
				<div class="rect4"></div>
				<div class="rect5"></div>
			</div>
			<div id="loading-status">Syncing...</div>
		</div>
		<div id="contacts-list" class="hidden">
			<div id="syncsuccess" class="clear"><h2>Successful syncs</h2><br></div>
			<div id="syncerror" class="clear"><h2>Syncs errors</h2><br></div>
		</div>
	</div>
</div>