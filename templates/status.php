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
	<div id="app-navigation" class="main-app-nav">
		<?php print_unescaped($this->inc('part.navigation')); ?>
		<?php print_unescaped($this->inc('part.settings')); ?>
	</div>

	<div id="app-content" class="main-app-content">
		<span id="high_res">
			<div id="welcome" class="intro-info">
				Welcome!!<br />This application sync profile pictures <u>and</u> birthdays from Facebook!
			</div>
			<div id="match" class="intro-info">
				2nd, go here to match your contacts to your friends!
			</div>
			<div id="sync" class="intro-info">
				Then you can sync right here! :)
			</div>
			<div id="warning" class="intro-info">
				<b>Make backups!!</b><br />
				This app has been verified, but you never can be too careful. ;)
			</div>
			<div id="warning2" class="intro-info">
				If you want to sync your birthdays, <u>switch your facebook to English first</u>!<br />
				It's a limitation I can't avoid because we don't use their API! :(
			</div>
			<div id="login" class="intro-info">
				<b>First!</b> Login here!
			</div>
			<div id="github" class="intro-info">
				A suggestion?<br />
				A problem?<br />
				Post it on github <a href="https://github.com/skjnldsv/Owncloud-FBSync/issues">HERE</a>!
			</div>
			<div id="copyright" class="intro-info">
				<a href="http://www.flaticon.com/packs/hand-drawn-arrows">Arrows</a>
			</div>
		</span>
		<span id="low_res">
			<b>Welcome!!</b><br />This application sync profile pictures <u>and</u> birthdays from Facebook!
			<br /><br />
			<b>Make backups!!</b><br />
			This app has been verified, but you never can be too careful. ;)
			<br /><br /><br />
			If you want to sync your birthdays, <u>switch your facebook to English first</u>!<br />
			It's a limitation I can't avoid because we don't use their API! :(
			<br /><br /><br />
			<h2>How to use:</h2>
			<ul>
				<li>1. Connect to facebook (bottom left)</li>
				<li>2. Enable at least one addressbook in your contact app</li>
				<li>3. Match your contacts with your fiends (menu on the left)</li>
				<li>4. Finally, sync the data you want in the sync page! :)</li>
			</ul>
			<br /><br />
			
			<div id="github" class="intro-info">
				A suggestion?<br />
				A problem?<br />
				Post it on github <a href="https://github.com/skjnldsv/Owncloud-FBSync/issues">HERE</a>!
		</span>
	</div>
</div>