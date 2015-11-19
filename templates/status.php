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

<div id="app" class="fbsync">
	<div id="app-navigation">
		<?php print_unescaped($this->inc('part.navigation')); ?>
		<?php print_unescaped($this->inc('part.settings')); ?>
	</div>

	<div id="app-content" class="main-app-content">
		<h1>WELCOME !</h1>
		<div class="main-app-intro">
			Hi there! Welcome to the facebook profile picture sync application.
			<br />
			<br />
			Don't forget this app is in early stages.
			<br />
			Please make sure to have backups of your contacts before running a sync.
			<br /><br /><br />
			How this works:
			<ul>
				<li>In the settings section (bottom left) just login.</li>
				<li>Go to the match section, and match your friends.</li>
				<li>Finally, go to the sync section, click "Sync all" and wait!</li>
			</ul>
			<br />
			ENJOY!
		</div>
		<br />
		<h2>A little history:</h2>
		<div class="main-app-intro">
			Recently facebook decided to block many things of their API. The full friend list became inaccessible, and many profiles picture too. A lot of android app became worthless.
			So because I love owncloud, I decided to work on a sync app.<br />
			I figured out how to retrieve the facebook data by using the mobile website and a cookie auth (which isn't secure for now, because the cookie is stored in plain text on the owncloud root! :O)
			<br /><br />
			Please HELP! Fill an issue for any bugs found. I will happilly work to improve this app on my free time!<br />
			<a href="https://github.com/skjnldsv/Owncloud-FBSync"><h3>Github</h3></a>
		</div>
	</div>
</div>