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
use OCA\FbSync\AppInfo\Application as App;

script('fbsync', 'login');
script('fbsync', 'match');
script('contacts', 'storage');
style('fbsync', 'fbsync');

// Cache ckeck before list
$fromCache = $_['facebook']->fromCache();
// Contacts & friends lists
$contacts = $_['contacts'];
$friends = $_['facebook']->getfriends();
?>

<div id="app" class="fbsync">
	
	<div id="app-navigation">
		<?php print_unescaped($this->inc('part.navigation')); ?>
		<?php print_unescaped($this->inc('part.settings')); ?>
	</div>

	<div id="app-content">
		<div id="controls">
			<div class="controls-left">
<!--				<div class="controls_item button last crumb"><h2>Match found contacts</h2></div>-->
				<button id="togglematch" class="tooltipped-bottom" title="Toggle the view">Display only contacts without match</button>
				<button id="sortA" class="tooltipped-bottom fa fa-sort-alpha-asc" title="Alphabetical order"></button>
				<button id="sortT" class="tooltipped-bottom fa fa-sort-numeric-asc" title="Last edit time order"></button>
				<button id="perfectmatch" class="tooltipped-bottom"
						title="No errors allowed. Will only match identical names">Match exact names</button>
				<button id="approxmatch" class="tooltipped-bottom"
						title="Will use an algorithm and try to match similar names">Match similar names</button>
				<button id="suggestmatch" class="tooltipped-bottom"
						title="Will use the 'People You May Know' list from facebook to match identical names">
					Match suggested</button>
				<div id="controls_loader" class="spinner button hidden">
					<div class="rect1"></div>
					<div class="rect2"></div>
					<div class="rect3"></div>
					<div class="rect4"></div>
					<div class="rect5"></div>
				</div>
			</div>
			<div class="controls-right">
				<div class="controls_item button" id="syncstatus">Loading...</div>
				<button class="controls_item tooltipped-bottom" id="fbstatus" title="Click to reload the friends cache">
					<?php
					if($fromCache) {
						echo count($friends)." friends loaded from cache";
					} else {
						echo count($friends)." friends found and cached";
					}
					?>
				</button>
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
		
		<!-- Fake contact div to load the contact svg before the contacts pictures -->
		<div style="url('/apps/fbsync/img/contact.svg'), url('/apps/fbsync/img/loading.svg');"></div>
		
		<div id="contacts-list" style="display:none" data-friends="<?=count($friends) ?>" data-contacts="<?=count($contacts) ?>">
			<?php
			foreach($contacts as $contact) {
				if($contact->addressbook != "") {
					// Main div
					$htmItem=Array();
					$htmItem['class']="localcontact ";
					$htmItem['data-id']=$contact->id;
					$htmItem['data-bookid']=$contact->addressbook;
					$htmItem['data-name']=$contact->getName();
					$htmItem['data-time']=$contact->lastmodified;
					if(isset($contact->vcard->FBID)) {
						$htmItem['data-fbid']=$contact->getFBID();
					} else {
						$htmItem['class'].='nofbid';
					}
					echo '<div ';
					foreach($htmItem as $elemt => $value) {
						echo $elemt.'="'.$value.'" ';
					}
					echo '>';
					// PHOTO
					echo '<div class="photo';
					if(isset($contact->vcard->PHOTO)) {
//						echo '"><img src="'.$contact->getPhoto().'" height="40" width="40" />';
						echo '"><img src="index.php/apps/fbsync/getphoto/'.$contact->id.'/60" height="60" width="60" />';
					} else {
						echo ' nophoto">';
					}
					echo '</div>';
					echo '<div class="content"><span class="name">';
					echo $contact->getName();
					echo '</span>';
					echo '<select class="fbselect">';
					echo '<option value="false">Choose friend</option>';
					// Building friends options
					$options = "";
					$selected=false;
					foreach($friends as $fbid => $name) {
						$options .= '<option ';
						// Contact match
						if($contact->getFBID()==$fbid) {
							$selected=true;
							$options .= 'selected ';
						}
						$options .= 'value="'.$fbid.'">'.$name.'</option>';
					}
					// If FBID not in friends list
					if(isset($contact->vcard->FBID) && !$selected) {
						$options = '<option selected value="'.$contact->getFBID().'">FBID not in your friends list</option>'.$options;
					}
					echo $options;
					echo '</select>';
					echo '</div>';
					echo '</div>'.PHP_EOL;
				}
			}
			?>
		</div>
	</div>
	
</div>