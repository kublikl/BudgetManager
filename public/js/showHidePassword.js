function myFunction(id) {
	var password = document.getElementById(id);
	
	if (password.type === "password") {
		password.type = "text";
		$('#showHideIcon').removeClass('fa-eye');
		$('#showHideIcon').addClass('fa-eye-slash');
		
	} else {
		password.type = "password";
		$('#showHideIcon').removeClass('fa-eye-slash');
		$('#showHideIcon').addClass('fa-eye');
	}}