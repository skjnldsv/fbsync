/**
 * ownCloud - fbsync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author NOIJN <fremulon@protonmail.com>
 * @copyright NOIJN 2015
 */

function logvalid() {
    jQuery('#app-settings-content #fblogin').css({'background-color':'#A1B56C','color':'white'});
}
function logerror() {
    jQuery('#app-settings-content #fblogin').css({'background-color':'#AB4642','color':'white'});
}
function logcheck() {
    jQuery('#app-settings-content #fblogin').css({'background-color':'#DC9656','color':'white'});
}

(function ($, OC) {

	$(document).ready(function () {
		
//----------  LOGIN  ----------
		$('#fblogin').click(function () {
			var url = OC.generateUrl('apps/fbsync/facebook/login');
			var data = {
				'email': btoa($('#app-settings-content #fbemail').val()),
                'pass': btoa($('#app-settings-content #fbpass').val())
			};

			$.post(url, data).success(function (response) {
				if(response[0]=="success") {
                    logvalid();
                    alert("Connected to Facebook.")
                } else if(response[0]=="checkpoint") {
                    logcheck();
                    alert("Facebook is asking for a pin. Please approve the connection from another device. Just check your notifications in another browser or phone where you're logged in.\nRemembre to check the 'save browser' option!\n\nThen, press the login button again!")
                } else {
                    logerror();
                    alert("Password or username error!")
                }
			});

		});


//----------  DELETE COOKIE  ----------
		$('#delcookie').click(function () {
			var url = OC.generateUrl('apps/fbsync/facebook/delcookie');
			$.get(url).success(function (response) {
				if(response) {
					jQuery('#app-settings-content #delcookie').css({'background-color':'#A1B56C','color':'white'});
                    alert("Cookie removed.")
                } else {
					jQuery('#app-settings-content #delcookie').css({'background-color':'#AB4642','color':'white'});
                    alert("Error while removing the cookie. Do you have the right permissions?")
                }
			});

		});
		
		
//----------  LOGIN STATUS ----------
		$.ajax({ 
			url: OC.generateUrl('apps/fbsync/facebook/islogged'),
			type: 'GET'
		}).done(function(response) {
			if(response) {
				status="Connected";
				statusclass="login-ok";
			} else {
				status="Not connected";
				statusclass="login-bad";
			}
			$("#login-status > span").text(status).addClass(statusclass);
			// Reload
			return true;
		}).fail(function(response) {
			console.log(response);
			return false;
		});
		
		
		$('.tooltipped').tipsy();
	});

})(jQuery, OC);