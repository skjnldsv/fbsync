/**
 * fbsync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author NOIJN <skjnldsv@protonmail.com>
 * @copyright NOIJN 2015
 */

var fbcontactsNames = Array();
var matched=0;
var syncing=false;
var synced=0;
var syncederror=0;

function updateFBID(contact, fbid, success, error) {
	$.ajax({ 
		url: OC.generateUrl('apps/fbsync/contact/fbid/'+contact),
		type: 'POST',
		data: {"fbid":fbid}
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
	$("#perfectmatch").text('Matching...').prop('disabled',true).addClass('loading');
	$.ajax({ 
		url: OC.generateUrl('apps/fbsync/perfectmatch'),
		type: 'GET'
	}).done(function(response) {
		$("#perfectmatch").text(response+' contacts updated [refresh in 3s]');
		// Reload
		setTimeout(function() {document.location.reload()},3000);
		return true;
	}).fail(function(response) {
		console.log(response);
		return false;
	});
}


// Match all contacts with exact Name
function approxMatch() {
	$("#approxmatch").text('Matching...').prop('disabled',true).addClass('loading');
	$.ajax({ 
		url: OC.generateUrl('apps/fbsync/approxmatch'),
		type: 'GET'
	}).done(function(response) {
		$("#approxmatch").text(response+' contacts updated [refresh in 3s]');
		// Reload
		setTimeout(function() {document.location.reload()},3000);
		return true;
	}).fail(function(response) {
		console.log(response);
		return false;
	});
}

// Match all contacts with exact Name
function suggestMatch() {
	$("#suggestmatch").text('Matching...').prop('disabled',true).addClass('loading');
	$.ajax({ 
		url: OC.generateUrl('apps/fbsync/suggestmatch'),
		type: 'GET'
	}).done(function(response) {
		$("#suggestmatch").text(response+' contacts updated [refresh in 3s]');
		// Reload
		setTimeout(function() {document.location.reload()},3000);
		return true;
	}).fail(function(response) {
		console.log(response);
		return false;
	});
}

function reloadCache() {
	$("#fbstatus").text('Loading...').prop('disabled',true).addClass('loading');
	$.ajax({ 
		url: OC.generateUrl('apps/fbsync/facebook/reloadfriends'),
		type: 'GET'
	}).done(function(response) {
		$("#fbstatus").text(response+' friends found [refresh in 3s]');
		// Reload
		setTimeout(function() {document.location.reload()},3000);
		return true;
	}).fail(function(response) {
		console.log(response);
		return false;
	});
}

function reloadSync(total) {
	text="Syncing contacts: "+Math.round(100*synced/total)+"%...";
	if(syncederror>0) {
		text+=" ("+syncederror+" error(s))";
	}
	$('#syncstatus').text(text);
	if(synced+syncederror == total) {
		reloadMatched();
		$("#perfectmatch").removeProp('disabled');
	}
}

function reloadMatched() {
	var matched=$('.localcontact:not(.nofbid)').length;
	var contacts=$('.localcontact').length;
	var friends=$('#contacts-list').data('friends');
	$('#syncstatus').text(Math.round(matched/contacts*100)+'% of '+contacts+' matched with '+Math.round(matched/friends*100)+'% ('+matched+') of your Facebook friends')
}


function sortA(a,b){  
	return $(a).data("name") > $(b).data("name") ? 1 : -1;  
};

function sortT(a,b){  
	return $(a).data("time") > $(b).data("time") ? 1 : -1;  
};


(function ($, OC) {
    
	$(document).ready(function () {
		
		$('.tooltipped-top').tipsy({gravity: 's'});
		$('.tooltipped-bottom').tipsy({gravity: 'n'});
		$('#contacts-list').fadeOut();
		
//----------  BUTTONS ----------	
		// Toggle matched button
		$("#togglematch").click(function() {
			$('.localcontact:not(.nofbid)').toggle();
			$("#togglematch").toggleClass('on').text($(this).hasClass('on')?"Display all contacts":"Display only contacts without match")
		})

		// PerfectMatch button
		$("#perfectmatch").click(function() {
			// Fix for tooltip on disabled buttons
			$('.tooltip').fadeOut();
			perfectMatch();
		})
		
		// ApproxMatch button
		$("#approxmatch").click(function() {
			// Fix for tooltip on disabled buttons
			$('.tooltip').fadeOut();
			approxMatch();
		})
		
		// SuggestMatch button
		$("#suggestmatch").click(function() {
			// Fix for tooltip on disabled buttons
			$('.tooltip').fadeOut();
			suggestMatch();
		})
		
		// ReloadCache button
		$("#fbstatus").click(function() {
			// Fix for tooltip on disabled buttons
			$('.tooltip').fadeOut();
			reloadCache();
		})
		
		// Custom FBID button
		$(".custom_fbid").click(function() {
			// Fix for tooltip on disabled buttons
			$('.tooltip').fadeOut();
			// Update classes
			var id=$(this).parent().parent().data('id');
			var name=$(this).parent().find('.name').text();
			
			var fbid = prompt("Enter fbid");
			if (fbid != null && fbid == parseInt(fbid, 10) && !isNaN(fbid)) {
				$(this).parent().parent().removeClass('nofbid');
				updateFBID(id, fbid, function() {
					var select = $('[data-id="'+id+'"] select');
					var option = select.find('option[value='+fbid+']');
					if(option.length>0) {
						option.prop('selected',true);
					} else {
						select.find('.new_fbid').remove();
						select.find('option:eq(0)').after('<option selected class="new_fbid" value="'+fbid+'">FBID not in your friends list</option>')
					}
					$('[data-id="'+id+'"] select').removeProp('disabled');
					reloadMatched();
				}, function() {
					alert("Error saving "+name+" data !");
				});
			} else {
				alert('Wrong input. FBIDs are supposed to be a number.')
			}
		})
		
		// sort Alpha
		$("#sortA").click(function() {
			$(".localcontact").sort(sortA).appendTo($("#contacts-list"));
		})
		
		// sort Time
		$("#sortT").click(function() {
			$(".localcontact").sort(sortT).appendTo($("#contacts-list"));
		})
		
		// Select changes
		$(".fbselect").change(function() {
			// Update classes
			var id=$(this).parent().parent().data('id');
			var name=$(this).parent().find('.name').text();
			
			$(this).prop('disabled', true);
			
			if($(this).val()!="false") {
				$(this).parent().parent().removeClass('nofbid');
				updateFBID(id, $(this).val(), function() {
					// $this won't work here
					$('[data-id="'+id+'"] select').removeProp('disabled');
					reloadMatched();
				}, function() {
					alert("Error saving "+name+" data !");
				});
			} else {
				$(this).parent().parent().addClass('nofbid');
				updateFBID(id, null, function() {
					$('[data-id="'+id+'"] select').removeProp('disabled');
					reloadMatched();
				}, function() {
					alert("Error saving "+name+" data !")
				});
			}
		});
		
//----------  INIT STATS & VARIOUS PAGE LOADING ----------	
		reloadMatched();
		$('#loader').remove();
		$('#contacts-list').fadeIn();
		
//----------  RESIZE & SCREEN ADAPTATION ----------	
		// Fixes the padding in case of low width screen resolution
		$('#contacts-list').css({'padding-top':$('#controls').height()+'px'});
		$(window).resize(function() {
			$('#contacts-list').css({'padding-top':$('#controls').height()+'px'});
			
		});
		
	});

})(jQuery, OC);