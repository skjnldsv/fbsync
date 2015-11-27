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

function isDoneSyncing(synced, error, ignored, syncbutton) {
	var syncstatus = synced+" contact"+(synced>1?'s':'')+" synced ("+error+" error"+(error>1?'s':'')+" & "+ignored+" ignored)";
	$('#syncstatus').text(syncstatus);
	if(synced+error+ignored==localcontacts.length) {
		$(syncbutton).text('Done !').removeClass('loading');
		$('#loader').fadeOut();
		$('#contacts-list').fadeIn();
		$(".syncbutton").removeProp('disabled');
		$("#syncpic").text($("#syncpic").data('text')).removeData('text');
		$("#syncbday").text($("#syncbday").data('text')).removeData('text');
		
	}
}

function syncPictures() {
	
	$('#loader').fadeIn();
	
	var synced = 0;
	var error = 0;
	localcontacts.forEach(function(id, index, array){
		$.get(OC.generateUrl('apps/fbsync/setphoto/'+id)).done(function(response) {

			var url = OC.generateUrl(
				'apps/fbsync/getphoto/{id}/100',
				{id: response['id']}
			);
			var contactdivStart = '<div class="sync-contact tooltipped" title="'+response['name'];
			var contactdivError = ': '+response['error']
			var contactdivEnd = '"><img src="'+url+'" height="100" width="100" /></div>'

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
			isDoneSyncing(synced, error, 0,  "#syncpic");
					
		}).fail(function() {
			
			error++;
			isDoneSyncing(synced, error, 0,  "#syncpic");
			
		});
	})
}

function syncBirthdays() {
	
	$('#loader').fadeIn();
	
	var synced = 0;
	var error = 0;
	var ignored = 0;
	localcontacts.forEach(function(id, index, array){
		$.get(OC.generateUrl('apps/fbsync/setbday/'+id)).done(function(response) {

			var url = OC.generateUrl(
				'apps/fbsync/getphoto/{id}/100',
				{id: response['id']}
			);
			var contactdivStart = '<div class="sync-contact tooltipped" title="'+response['name'];
			var contactdivError = ': '+response['error']
			var contactdivEnd = '"><img src="'+url+'" height="100" width="100" /></div>'
			
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
			isDoneSyncing(synced, error, ignored,  "#syncbday");
			
		}).fail(function() {
			
			error++;
			isDoneSyncing(synced, error, ignored,  "#syncbday");
			
		});
	})
}


(function ($, OC) {
    
	$(document).ready(function () {
		
		// Better visual
		$('#contacts-list').fadeOut();
		$('.tooltipped-top').tipsy({gravity: 's'});
		$('.tooltipped-bottom').tipsy({gravity: 'n'});
		
		// Hack to resize auto #controls (/core/js/js.js:1435)
		if($('#controls').length) {
			var controlsWidth;
			// if there is a scrollbar …
			if($('#app-content').get(0).scrollHeight > $('#app-content').height()) {
				if($(window).width() > 768) {
					controlsWidth = $('#content').width() - $('#app-navigation').width() - getScrollBarWidth();
					if (!$('#app-sidebar').hasClass('hidden') && !$('#app-sidebar').hasClass('disappear')) {
						controlsWidth -= $('#app-sidebar').width();
					}
				} else {
					controlsWidth = $('#content').width() - getScrollBarWidth();
				}
			} else { // if there is none
				if($(window).width() > 768) {
					controlsWidth = $('#content').width() - $('#app-navigation').width();
					if (!$('#app-sidebar').hasClass('hidden') && !$('#app-sidebar').hasClass('disappear')) {
						controlsWidth -= $('#app-sidebar').width();
					}
				} else {
					controlsWidth = $('#content').width();
				}
			}
			$('#controls').css('width', controlsWidth);
			$('#controls').css('min-width', controlsWidth);
		}
		
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
			// Save and set new text
			$("#syncpic").data('text', $("#syncpic").text()).text('Loading...').addClass('loading');
			$(".syncbutton").prop('disabled',true);
			// Empty previous sync data
			$('.sync-results').empty();
			syncPictures();
		})
		$("#syncbday").click(function() {
			// Save and set new text
			$("#syncbday").data('text', $("#syncbday").text()).text('Loading...').addClass('loading');
			$(".syncbutton").prop('disabled',true);
			// Empty previous sync data
			$('.sync-results').empty();
			syncBirthdays();
		})
		
	});

})(jQuery, OC);