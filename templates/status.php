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
style('fbsync', 'fbsync');
?>

<div id="app">
	<div id="app-navigation">
		<?php print_unescaped($this->inc('part.navigation')); ?>
		<?php print_unescaped($this->inc('part.settings')); ?>
	</div>

	<div id="app-content" class="main-app-content">
		<h1>WELCOME !</h1>
		<p>
			Hi there! Welcome to the facebook profile picture sync application.
			<br>
			<br>
			Don't forget this app is in early stages.
			<br>
			Please make sure to have backups of your contacts before running a sync.
			<br><br><br>
			How this works:
			<ul>
				<li>In the settings section (bottom left) just login.</li>
				<li>Go to the match section, and match your friends.</li>
				<li>Finally, go to the sync section, click "Sync all" and wait!</li>
			</ul>
			<br>
			ENJOY!
			<br><br>Note to devs, if you know Owncloud dev better than I do (which won't be to hard considering I can't figure out the logic behind the OC architecture)<br>
			PLEASE help! There's a looot to do with this app. Thanks a lot &lt;3!
		</p>
	</div>
</div>