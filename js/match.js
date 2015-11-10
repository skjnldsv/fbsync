
var localcontacts = Array();
var fbcontacts = Array();
var fbcontactsNames = Array();
var loading=0;
var matched=0;
var syncing=false;
var synced=0;
var syncederror=0;

// Loading status manager
function loaded() {
	if(loading==1){
		$('#loading-status').text('Local contacts loaded! Loading facebook friends...');
	}
	if(loading==2){
		$('#loading-status').text('Facebook friends loaded! Loading local contacts...');
	}
	if(loading==3){
		$('#loading-status').text('Initializing data...');
		initSelects();
	}
	if(loading==6){
		$("#loader").remove();
		$('#contacts-list').fadeIn();

		// Watch select changes
		$(".fbselect").change(function() {
			// Update classes
			var bookid=$(this).parent().data('bookid');
			var id=$(this).parent().data('id');
			var name=$(this).parent().find('.name').text();
			
			$(this).prop('disabled', true);
			
			if($(this).val()!="false") {
				$(this).parent().removeClass('nofbid');
				updateFBID(bookid, id, $(this).val(), function() {
					// $this won't work here
					$('[data-id="'+id+'"] select').removeProp('disabled');
					reloadMatched();
				}, function() {
					alert("Error saving "+name+" data !");
				});
			} else {
				$(this).parent().addClass('nofbid');
				updateFBID(bookid, id, null, function() {
					$('[data-id="'+id+'"] select').removeProp('disabled');
					reloadMatched();
				}, function() {
					alert("Error saving "+name+" data !")
				});
			}
			// Update data
		});
	}
}

function updateFBID(addressbook, contact, fbid, success, error) {
	$.ajax({ 
		url: OC.generateUrl('apps/contacts/addressbook/local/'+addressbook+'/contact/'+contact),
		type: 'PATCH',
		data: {"name":"FBID","value":fbid,"parameters":{}}
	}).done(function() {
		success();
		return true;
	}).fail(function() {
		error();
		return false;
	});
}


// Match all contacts with exact Name
function perfectMatch() {
	var count=0;
	syncing=true;
	synced=0;
	$("#perfectmatch").prop('disabled',true);
	fbcontactsNames.forEach(function(friend, index, array){
		var re = /(.*)-([0-9]{0,20})/; 
		var matches = re.exec(friend);
		var name=matches[1]
		var fbid=matches[2];
		if($('[data-name="'+name+'"]').length) {
			count+=$('[data-name="'+name+'"]').length;
			$('[data-name="'+name+'"]').removeClass('nofbid').find('select > option[value='+fbid+']').prop('selected',true);
			var bookid=$('[data-name="'+name+'"]').data('bookid');
			var id=$('[data-name="'+name+'"]').data('id');
			// Saving DATA
			updateFBID(bookid, id, fbid, function(){
				synced++;
				reloadSync(count);
			}, function(){
				syncederror++;
				reloadSync(count);
				alert("Error saving "+name+" data !")
			});
		}
	})
}

function initSelects() {
	var options="";
	// Sort array
	fbcontactsNames.sort();
	// Create options
	fbcontactsNames.forEach(function(friend, index, array){
		var re = /(.*)-([0-9]{0,20})/; 
		var matches = re.exec(friend);
		var name=matches[1]
		var fbid=matches[2];
		options+='<option value="'+fbid+'">'+name+'</option>';
	})
	// Append all contacts
	$(".fbselect").each(function() {
		$(this).append(options);
	})
	// Select matched contacts
	$('.localcontact:not(.nofbid)').each(function() {
		var fbid=$(this).data('fbid');
		$(this).find('select > option[value='+fbid+']').prop('selected',true);
		matched++;
	})
	loading+=3;
	loaded();
	reloadMatched();
	
}

function reloadSync(total) {
	text="Syncing contacts: "+Math.round(100*synced/total)+"%...";
	if(syncederror>0) {
		text+=" ("+syncederror+" error(s))";
	}
	$('#syncstatus').fadeIn().text(text);
	if(synced+syncederror == total) {
		reloadMatched();
		$("#perfectmatch").removeProp('disabled');
	}
}

function reloadMatched() {
	var matched=$('.localcontact:not(.nofbid)').length;
	var contacts=$('.localcontact').length;
	var friends=fbcontactsNames.length;
	$('#syncstatus').fadeIn().text(Math.round(matched/contacts*100)+'% of '+contacts+' matched with '+Math.round(matched/friends*100)+'% ('+matched+') of your Facebook friends')
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
			// If no addressbook selected, only the OC user appear
			if(localcontacts.length == 1) {
				$('#loading-status').text("No contacts found.");
				$('#syncstatus').text('Error!')
				$('#contacts-list').append('<div id="main_error"><h1>No contacts found. Did you hide all the addressbooks?</h1><div>');
				loading+=6;
				loaded();
			} else {
				localcontacts.forEach(function(contact, index, array){
					// Prevent contacts without addressbook
					var addressbook = contact['addressbook-key'].split(':')[1];
					if(addressbook != undefined) {
						var result='<div data-id="'+contact['id']+'" data-bookid="'+addressbook+'" data-name="'+contact['FN']+'" class="localcontact';
						// Photo or default picture
						if(contact['FBID']) {
							fbid=contact['FBID']
							result+='" data-fbid="'+fbid+'">';
						} else {
							result+=' nofbid">';
						}
						result+='<div class="photo';
						// Photo or default picture
						if(contact['photo']) {
							photo=contact['PHOTO'].split('uri:')[1];
							result+='" style="background-image:url(\''+photo+'\')">';
						} else {
							result+=' nophoto">';
						}
						result+='</div>';
						result+='<span class="name">';
						result+=contact['FN'];
						result+='</span>';
		//				result+='<br /><span class="fi-arrow-right selectspan"></span><select><option>Choisir</option></select>';
						result+='<select class="fbselect"><option value="false">Choose friend</option></select>';
						result+='</div>';
						$('#contacts-list').append(result);
					}
				});
				loading+=1;
				loaded();
			}
        }).fail(function() {
			$('#loading-status').text("Error loading local contacts.");
		});
		
//----------  FACEBOOK FRIENDS ----------
        url2 = OC.generateUrl('apps/fbsync/facebook/friends')
        $.getJSON(url2).done(function (response) {
			var friendscount=0;
			$.each($.parseJSON(response[1]), function (i,v) {
				fbcontacts[fbcontacts.length]=Array();
				fbcontacts[fbcontacts.length-1][i]=v;
				fbcontacts[fbcontacts.length-1][v]=i;
				fbcontactsNames.push(v+"-"+i);
			});
			$('#fbstatus').text(fbcontacts.length+" facebook contacts loaded");
			loading+=2;
			loaded();
        }).fail(function() {
			$('#fbstatus').text("Error!");
			$('#loading-status').text("Error loading facebook friends. Are you connected?");
		});
	
//----------  BUTTONS ----------	
		// Toggle matched button
		$("#togglematch").click(function() {
			$('.localcontact:not(.nofbid)').toggle();
			$("#togglematch").toggleClass('on').text($(this).hasClass('on')?"Display all contacts":"Display only contacts without match")
		})

		// PerfectMatch button
		$("#perfectmatch").click(function() {
			perfectMatch();
		})
	
		
	});

})(jQuery, OC);