<?php

function connect_database(){
	global $connection;
	$host="localhost";
	$user="test";
	$pass="t3st3r123";
	$db="test";
	$connection = mysqli_connect($host, $user, $pass, $db) or die("ei saa ühendust mootoriga- ".mysqli_error());
	mysqli_query($connection, "SET CHARACTER SET UTF8") or die("Ei saanud baasi utf-8-sse - ".mysqli_error($connection));
}

function login(){

	if (isset($_POST['loggedinuser'])) {
		include_once('projects.html');
	}

	include_once('login.html');

	if (isset($_SERVER['REQUEST_METHOD'])) {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$errors = array();
			if (empty($_POST['user'])) {
				$errors[] = "Please enter both username and password.";
			}
			if (empty($_POST['pass'])) {
				$errors[] = "Please enter both username and password.";
			}

			if (empty($errors)){
				global $connection;
				$sisestatudusername = mysqli_real_escape_string($connection, $_POST["user"]);
				$sisestatudpassword = mysqli_real_escape_string($connection, $_POST["pass"]);
				$sql = "SELECT username, password FROM saasma_archiview_kasutajad WHERE username='$sisestatudusername' AND password=SHA1('$sisestatudpassword')";
				$result = mysqli_query($connection, $sql) or die ("User by this name does not exist.");
				$rida = mysqli_num_rows($result);
				if ($rida > 0) { //user was found in db
					$_SESSION['loggedinuser'] = $sisestatudusername;
					header("Location: ?page=projects");
				} 
			}
		}
	}
}

function logout(){
	$_SESSION=array();
	session_destroy();
	header("Location: ?page=homepage");
}

function registration(){
	if (isset($_POST['loggedinuser'])) {
		include_once('projects.html');
	} else {
		include_once('registration.html');
	}

	if (isset($_SERVER['REQUEST_METHOD'])) {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$errors = array();
			if (empty($_POST['username_reg'])) {
				$errors[] = "Please enter username.";
			}
			if ($_POST['password_reg1'] != $_POST['password_reg2']) {
				$errors[] = "Passwords do not match, please enter again.";
			}
			if (empty($_POST['password_reg1'])) {
				$errors[] = "Please enter password.";
			}
			if (empty($_POST['password_reg2'])) {
				$errors[] = "Please repeat password.";
			}

			if (empty($errors)){
				//viska sisestatud andmed andmebaasi ja pane andmebaasist sisestatud kasutaja sessiooni, suuna projektide lehele, muidu kuva registration.html

				echo "You have been registered!";

				global $connection;
				$registeredusername = mysqli_real_escape_string($connection, $_POST["username_reg"]);
				$registeredpassword = mysqli_real_escape_string($connection, $_POST["password_reg1"]);
				$sql = "INSERT INTO saasma_archiview_kasutajad (username, password) VALUES ('$registeredusername', SHA1('$registeredpassword'))";
				$result = mysqli_query($connection, $sql) or die ("Proovi uuesti.");

				if ($result) {
					if (mysqli_insert_id($connection) > 0) {
						$_SESSION['loggedinuser'] = $registeredusername;
						header("Location: ?page=projects");
						exit(0);
					}
				}
			}

			
			echo $registeredusername;
			echo $registeredpassword;
		}
	}
}

function upload() {
	global $connection;

	include_once('upload.html');

	if(isset($_POST['upload'])){
		$drawingname = $_FILES["drawing_upload"]["name"];
		$imageryname = $_FILES["imagery_upload"]["name"];

		$tmp_name1 = $_FILES['drawing_upload']['tmp_name'];
		$tmp_name2 = $_FILES['imagery_upload']['tmp_name'];

		$error = array();
		$error = $_FILES['drawing_upload']['error'];
		$error = $_FILES['imagery_upload']['error'];

		if (isset ($drawingname) && isset ($imageryname)) {
		    if (!empty($drawingname) && !empty($imageryname)) {

		    $location = '../Projekt/Uploads/';

		    if (move_uploaded_file($tmp_name1, $location.$drawingname) && move_uploaded_file($tmp_name2, $location.$imageryname)) {
		        $success['Message'] = "Upload was successful.";
		        echo $success;
		    }

		    if (!empty($success)) {
				$user = mysqli_real_escape_string($connection, $_SESSION['loggedinuser']);
				$drawing = mysqli_real_escape_string($connection, $drawingname);
				$projecttitle = mysqli_real_escape_string($connection, $_POST['projecttitle_upload']);
				$projecttext = mysqli_real_escape_string($connection, $_POST['projecttext_upload']);
				$imagery = mysqli_real_escape_string($connection, $imageryname);
				$sql = "INSERT INTO saasma_archiview_drawings (username, drawing, projecttitle, projecttext, 3dimagery) VALUES ('$user', '$drawing', '$projecttitle', '$projecttext', '$imagery')";
				$result = mysqli_query($connection, $sql);
				if ($result) {
					$id = mysqli_insert_id($connection);
					
					$_SESSION['message'] = "Upload was successful.";
					header("Location: ?page=project_$id");
					exit(0);
				} else {
					$error['save'] = "Please try to upload your project again.";
				}
		    }

	        } else {
	          echo 'Please choose files.';
	          echo $error;
		    }
		}
	}
}

function homepage() {
	include_once('home.html');
}

function projects() {
	include_once('projects.html');
}

function myprojects() {
	include_once('myprojects.html');
}

function about() {
	include_once('about.html');
}

?>