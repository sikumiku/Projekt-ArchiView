<?php
require_once('funktsioonid.php');

session_start();
connect_database();

$page="homepage";
if (isset($_GET['page']) && $_GET['page']!=""){
	$page=htmlspecialchars($_GET['page']);
}

include_once('header.html');

switch($page){
	case "homepage":
		homepage();
	break;
	case "projects":
		projects();
	break;
	case "myprojects":
		myprojects();
	break;
	case "showproject";
		showproject();
	break;
	case "about":
		about();
	break;
	case "upload":
		upload();
	break;
	case "login":
		login();
	break;
	case "registration":
		registration();
	break;
	case "logout":
		logout();
	break;
	default:
		include_once('home.html');
	break;
}

include_once('footer.html');

?>