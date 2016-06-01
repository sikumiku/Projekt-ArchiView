function validateForm() {
	$(".errorMessage").remove();

    var username = document.forms["registration"]["username_reg"].value;
    var pass1 = document.forms["registration"]["password_reg1"].value;
    var pass2 = document.forms["registration"]["password_reg2"].value;
    var validationResult = true;
    if (username == null || username == "") {
    	$("#errors").append(generateErrorHtml"Please fill out username<"));
        validationResult = false;
    }

    if (username.length <= 5) {
    	$("#errors").append(generateErrorHtml("Username needs to be at least 5 characters."));
    	validationResult = false;
    }

    if (pass1 != pass2) {
    	$("#errors").append(generateErrorHtml("Passwords need to match."));
    	validationResult = false;
    } 

    return validationResult;

}

function generateErrorHtml(errormessage) {
	return "<p class='errorMessage' style='color:red'>"+errormessage+"</p>";
}