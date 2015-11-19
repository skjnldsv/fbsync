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

function isDoneSyncing(total) {
	if(total==localcontacts.length) {
		$("#syncall").text('Done !').removeClass('loading');
	}
}

function syncPictures() {
	var synced = 0;
	var error = 0;
	$('#contacts-list').fadeIn();
	localcontacts.forEach(function(id, index, array){
		$.get(OC.generateUrl('apps/fbsync/setphoto/'+id)).done(function(response) {

			var url = OC.generateUrl(
				'apps/contacts/addressbook/{backend}/{addressbook}/contact/{id}/photo?maxSize=100',
				{backend: response['backend'], addressbook: response['addressbook'], id: response['id']}
			);
			var syncstatus = synced+" contact"+(synced>1?'s':'')+" synced ("+error+" error"+(error>1?'s':'')+")";
			var contactdivStart = '<div class="sync-contact tooltipped" title="'+response['name'];
			var contactdivError = ': '+response['error']
			var contactdivEnd = '"><img src="'+url+'" /></div>'
			$('#syncstatus').text(syncstatus);

			// Error or success?
			if(response['error']!=false) {
				error++;
				$('#syncerror').append(contactdivStart+contactdivError+contactdivEnd);
			} else {
				synced++;
				$('#syncsuccess').append(contactdivStart+contactdivEnd);
			}
			$('.tooltipped').tipsy();
			isDoneSyncing(synced+error);
		}).fail(function() {
			error++;
			$('#syncstatus').text(synced+" contact"+(synced>1?'s':'')+" synced ("+error+" error"+(error>1?'s':'')+")");
		});
	})
}


(function ($, OC) {
    
	$(document).ready(function () {
		
		// Better visual
		$('#contacts-list').fadeOut();
		$('.tooltipped').tipsy();
		
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
			$("#loader").remove();
			$('#syncstatus').text(localcontacts.length-1+' contacts loaded')
        }).fail(function(){
			$('#loading-status').text('Unexpected error...');
			console.log(localcontacts);
		});
	
//----------  BUTTONS ----------	
		// Toggle matched button
		$("#syncall").click(function() {
			$("#syncall").text('Loading...').prop('disabled',true).addClass('loading');
			syncPictures();
		})
		
	});

})(jQuery, OC);