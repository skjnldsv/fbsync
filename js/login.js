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
	});

})(jQuery, OC);