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
			<br>
			<br>
			A little history:
			<br>
			Recently facebook decided to block many things of their API. The full friend list became inaccessible, and many profiles picture access too. A lot of android app became worthless. So because I love owncloud, I decided to work on a sync app.
			<br>
			I figured out how to retrieve the facebook datas by using the mobile website and a cookie auth (which isn't very secure for now, because the cookie is stored in plain text on the owncloud root! :O) 
			<br><br>
			I'm a dev who have been working on many arch and MVC. To be fair, I can't handle the OC MVC system. I can't find the logic in the way it is build. Maybe it will come later! ;)
			Meanwhile, I NEED YOU HELP! 
			<br><br>
			Please help me improving this app! I would gladly work on it for fun! I need help and will gladely accept pull request and discussions on the github page: https://github.com/skjnldsv/Owncloud-FBSync
			<br>Note to devs, if you know Owncloud dev better than I do (which won't be to hard considering I can't figure out the logic behind the OC architecture)<br>
			PLEASE help! There's a looot to do with this app. Thanks a lot &lt;3!
		</p>
	</div>
</div>