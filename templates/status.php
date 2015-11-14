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
			I'm a dev who has been working on many arch and MVC. To be fair, I can't handle the OC MVC system.<br />
			I can't find the logic in the way it is built. Maybe it will come later! ;)<br />
			<br />
			Meanwhile, I NEED YOU HELP! Please help me improving this app! I would gladly work on it for fun! I need help and will happily accept pull request and discussions on <a href="https://github.com/skjnldsv/Owncloud-FBSync"><h3>the github page</h3></a>
			So if you know Owncloud dev better than I do (which won't be to hard considering I can't figure out the logic behind the OC architecture)<br />
			<br />
			PLEASE help! There's a lot to do with this app. Thanks a lot!!
		</div>
	</div>
</div>