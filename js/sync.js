/**
 * ownCloud - fbsync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author NOIJN <fremulon@protonmail.com>
 * @copyright NOIJN 2015
 */

var localcontacts = new Array();

function isDoneSyncing(synced, error, ignored, total, syncbutton) {
	var syncstatus = synced+" contact"+(synced>1?'s':'')+" updated ("+error+" error"+(error>1?'s':'')+" & "+ignored+" ignored)";
	$('#syncstatus').text(syncstatus);
	if(synced+error+ignored >= total) {
		$(syncbutton).text('Done !').removeClass('loading');
		$('#loader').fadeOut();
		$('#contacts-list-results').fadeIn();
		$(".syncbutton").removeProp('disabled');
		$(syncbutton).text($(syncbutton).data('text')).removeData('text');
		// Fixes the padding in case of low width screen resolution
		$('#contacts-list-results').css({'padding-top':$('#controls').height()+'px'});	
	}
}

function delPictures() {
	$('#loader').fadeIn();
	
	$.get(OC.generateUrl('apps/fbsync/contacts/delphotos'))
		.done(function(response) {
			// Status
			$('.tooltipped').tipsy();
			isDoneSyncing(response, 0, 0, 0, "#delpictures");
		}).fail(function() {
			error++;
			isDoneSyncing(0, response, 0, 0, "#delpictures");
		});
}

function delBdays() {
	$('#loader').fadeIn();
	
	$.get(OC.generateUrl('apps/fbsync/contacts/delbdays'))
		.done(function(response) {
			// Status
			$('.tooltipped').tipsy();
			isDoneSyncing(response, 0, 0, 0, "#delbdays");
		}).fail(function() {
			isDoneSyncing(0, response, 0, 0, "#delbdays");
		});
}

function syncPictures() {
	
	$('#loader').fadeIn();
	
	var synced = 0;
	var error = 0;
	localcontacts.forEach(function(id, index, array){
		$.get(OC.generateUrl('apps/fbsync/setphoto/'+id))
			.done(function(response) {

			var contactdivStart = '<div class="sync-contact tooltipped" title="'+response['name'];
			var contactdivError = ': '+response['error'];
			if(response['photo'] == true) {
				var contactdivEnd = '"><img src="'+response['photourl']+'" height="100" width="100" /></div>';
			} else {
				var contactdivEnd = '"></div>';
			}

			// Error or success?
			if(response['error']!=false) {
				error++;
				$('#sync-errors > .sync-results').append(contactdivStart+contactdivError+contactdivEnd);
			} else {
				synced++;
				$('#sync-success > .sync-results').append(contactdivStart+contactdivEnd);
			}
			
			// Status
			$('.tooltipped').tipsy();
			isDoneSyncing(synced, error, 0, localcontacts.length, "#syncpic");
					
		}).fail(function() {
			
			error++;
			isDoneSyncing(synced, error, 0, localcontacts.length, "#syncpic");
			
		});
	})
}

function syncBirthdays() {
	
	$('#loader').fadeIn();
	
	var synced = 0;
	var error = 0;
	var ignored = 0;
	localcontacts.forEach(function(id, index, array){
		$.get(OC.generateUrl('apps/fbsync/setbday/'+id))
			.done(function(response) {

			var contactdivStart = '<div class="sync-contact tooltipped" title="'+response['name'];
			var contactdivError = ': '+response['error'];
			if(response['photo'] == true) {
				var contactdivEnd = '"><img src="'+response['photourl']+'" height="100" width="100" /></div>';
			} else {
				var contactdivEnd = '"></div>';
			}
			
			// Error or success?
			if(response['birthday'] == true) {
				ignored++;
				$('#sync-ignored > .sync-results').append(contactdivStart+contactdivError+contactdivEnd);
			} else if(response['error']!=false) {
				error++;
				$('#sync-errors > .sync-results').append(contactdivStart+contactdivError+contactdivEnd);
			} else {
				synced++;
				$('#sync-success > .sync-results').append(contactdivStart+': '+response['birthday']+contactdivEnd);
			}
			
			// Status
			$('.tooltipped').tipsy();
			isDoneSyncing(synced, error, ignored, localcontacts.length, "#syncbday");
			
		}).fail(function() {
			
			error++;
			isDoneSyncing(synced, error, ignored, localcontacts.length, "#syncbday");
			
		});
	})
}

function syncBirthdaysAlt() {
	
	$('#loader').fadeIn();
	
	var synced = 0;
	$.get(OC.generateUrl('apps/fbsync/setBirthdayAlt'))
	.done(function(response) {
		if(response.length>0) {
			
			response.forEach(function(contact, index, array){
				var contactdivStart = '<div class="sync-contact tooltipped" title="'+contact['name'];
				if(contact['photo'] == true) {
					var contactdivEnd = '"><img src="'+contact['photourl']+'" height="100" width="100" /></div>';
				} else {
					var contactdivEnd = '"></div>';
				}

				synced++;
				$('#sync-success > .sync-results').append(contactdivStart+': '+contact['birthday']+contactdivEnd);

				// Status
				$('.tooltipped').tipsy();
				isDoneSyncing(synced, 0, 0, response.length, "#syncbdayalt");
			})
			
		} else {
			
		}
		
	}).fail(function() {
			
		error++;
		isDoneSyncing(0, 1, 0, 1, "#syncbdayalt");
			
	});
}


(function ($, OC) {
    
	$(document).ready(function () {
		
		// Better visual
		$('#contacts-list-results').fadeOut();
		$('.tooltipped-top').tipsy({gravity: 's'});
		$('.tooltipped-bottom').tipsy({gravity: 'n'});
		
		
//----------  LOCAL CONTACTS ----------
        var url = OC.generateUrl('apps/fbsync/FBcontacts')
        $.getJSON(url).done(function (response) {
			localcontacts=response;
			$('#syncstatus').text(localcontacts.length-1+' contacts loaded')
        }).fail(function(){
			$('#loader').fadeIn();
			$('#loading-status').text('Unable to load contacts...');
			console.log(localcontacts);
		});
	
//----------  BUTTONS ----------	
		// Toggle matched button
		$("#syncpic").click(function() {
			// Fix for tooltip on disabled buttons
			$('.tooltip').fadeOut();
			// Save and set new text
			$("#syncpic").data('text', $("#syncpic").text()).text('Loading...').addClass('loading');
			$(".syncbutton").prop('disabled',true);
			// Empty previous sync data
			$('.sync-results').empty();
			syncPictures();
		})
		$("#syncbday").click(function() {
			// Fix for tooltip on disabled buttons
			$('.tooltip').fadeOut();
			// Save and set new text
			$("#syncbday").data('text', $("#syncbday").text()).text('Loading...').addClass('loading');
			$(".syncbutton").prop('disabled',true);
			// Empty previous sync data
			$('.sync-results').empty();
			syncBirthdays();
		})
		$("#syncbdayalt").click(function() {
			if (confirm("This secondary button uses the facebook calendar to get the desired data. It's useful if you don't want to change your facebook langage to get the first method working or if the 1 button is failing. Please remember that the saved year won't be correct.")) {
				// Fix for tooltip on disabled buttons
				$('.tooltip').fadeOut();
				// Save and set new text
				$("#syncbdayalt").data('text', $("#syncbdayalt").text()).text('Loading...').addClass('loading');
				$(".syncbutton").prop('disabled',true);
				// Empty previous sync data
				$('.sync-results').empty();
				syncBirthdaysAlt();
			}
		})
		$("#delpictures").click(function() {
			if (confirm("Are you sure ?!")) {
				// Fix for tooltip on disabled buttons
				$('.tooltip').fadeOut();
				// Save and set new text
				$("#delpictures").data('text', $("#delpictures").text()).text('Loading...').addClass('loading');
				$(".syncbutton").prop('disabled',true);
				// Empty previous sync data
				$('.sync-results').empty();
				delPictures();
			}
		})
		$("#delbdays").click(function() {
			if (confirm("Are you sure ?!")) {
				// Fix for tooltip on disabled buttons
				$('.tooltip').fadeOut();
				// Save and set new text
				$("#delbdays").data('text', $("#delbdays").text()).text('Loading...').addClass('loading');
				$(".syncbutton").prop('disabled',true);
				// Empty previous sync data
				$('.sync-results').empty();
				delBdays();
			}
		})
		
//----------  RESIZE & SCREEN ADAPTATION ----------	
		// Fixes the padding in case of low width screen resolution
		$('#contacts-list-results').css({'padding-top':$('#controls').height()+'px'});
		$(window).resize(function() {
			$('#contacts-list-results').css({'padding-top':$('#controls').height()+'px'});
			
		});
		
	});

})(jQuery, OC);
