var localcontacts = Array();
var localcontactsIDs = Array();

function syncPictures() {
	var synced = 0;
	var error = 0;
	$('#contacts-list').fadeIn();
	localcontacts.forEach(function(contact, index, array){
		var fbid = contact['FBID'];
		var id = contact['id'];
		var name = contact['FN'];
		// Prevent contacts without addressbook
		var backend = contact['addressbook-key'].split(':')[0];
		var addressbook = contact['addressbook-key'].split(':')[1];
		// Store for future use
		localcontactsIDs[id]=Array(backend,addressbook,name,fbid);
		
		if(fbid != undefined) {
			$.ajax({ 
				url: OC.generateUrl('apps/fbsync/setphoto/'+id),
				type: 'POST',
				data: {"fbid":fbid,"backend":backend,"addressbook":addressbook}
			}).done(function(response) {
				if(response['error']!=undefined) {
						error++;
						contact=localcontactsIDs[response['contactId']];
						var url = OC.generateUrl(
							'apps/contacts/addressbook/{backend}/{addressBookId}/contact/{contactId}/photo?maxSize=100',
							{backend: contact[0], addressBookId: contact[1], contactId: response['contactId']}
						);
						$('#syncstatus').text(synced+" contact"+(synced>1?'s':'')+" synced ("+error+" error"+(error>1?'s':'')+")");
						$('#syncerror').append('<div class="sync-contact tooltipped" title="'+contact[2]+': '+response['error']+'"><img src="'+url+'" /></div>');
				} else {
					var url = OC.generateUrl(
						'apps/contacts/addressbook/{backend}/{addressBookId}/contact/{contactId}/photo/{cachedImage}/crop',
						{backend: response['backend'], addressBookId: response['addressBookId'], contactId: response['contactId'], cachedImage: response['cachedImage']}
					);
					$.ajax({ 
						url: url,
						type: 'POST',
						data: {x:"0", y:"0", w:response['w'], h:response['h']}
					}).done(function(response) {
						synced++;
						contact=localcontactsIDs[response['data']['id']];
						var url = OC.generateUrl(
							'apps/contacts/addressbook/{backend}/{addressBookId}/contact/{contactId}/photo?maxSize=100',
							{backend: contact[0], addressBookId: contact[1], contactId: response['data']['id']}
						);
						$('#syncstatus').text(synced+" contact"+(synced>1?'s':'')+" synced ("+error+" error"+(error>1?'s':'')+")");
						$('#syncsuccess').append('<div class="sync-contact tooltipped" title="'+contact[2]+'"><img src="'+url+'" /></div>');
						// Tooltips
						$('.tooltipped').tipsy()
					}).fail(function() {
						error++;
						contact=localcontactsIDs[response['data']['id']];
						var url = OC.generateUrl(
							'apps/contacts/addressbook/{backend}/{addressBookId}/contact/{contactId}/photo?maxSize=100',
							{backend: contact[0], addressBookId: contact[1], contactId: response['data']['id']}
						);
						$('#syncstatus').text(synced+" contact"+(synced>1?'s':'')+" synced ("+error+" error"+(error>1?'s':'')+")");
						$('#syncerror').append('<div class="sync-contact tooltipped" title="'+contact[2]+'"><img src="'+url+'" /></div>');
					})
				}
			}).fail(function() {
				error++;
				$('#syncstatus').text(synced+" contact"+(synced>1?'s':'')+" synced ("+error+" error"+(error>1?'s':'')+")");
			});
		}
	})
}


(function ($, OC) {
    
	$(document).ready(function () {
		
		// Better visual
		$('#contacts-list').fadeOut();
		
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
        var url1 = OC.generateUrl('apps/fbsync/contacts')
        $.getJSON(url1).done(function (response) {
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
			$("#syncall").remove();
			syncPictures();
		})
		
	});

})(jQuery, OC);