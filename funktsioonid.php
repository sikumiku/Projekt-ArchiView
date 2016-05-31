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

	$errors = array();
	$success = array();
	//upload project
	if(isset($_POST['upload'])){
		$user = mysqli_real_escape_string($connection, $_SESSION['loggedinuser']);
		$projecttitle = mysqli_real_escape_string($connection, $_POST['projecttitle_upload']);
		$projecttext = mysqli_real_escape_string($connection, $_POST['projecttext_upload']);
		$sql = "INSERT INTO saasma_archiview_projectcontent (username, projecttitle, projecttext)
					VALUES('$user', '$projecttitle', '$projecttext');";
		$result1 = mysqli_query($connection, $sql);
		if ($result1) {
		    $id = $connection->insert_id;
		} else {
    		$errors['projectupload'] = 'Project upload failed.';
		}
	//upload drawings
		$total1 = count($_FILES['drawing_upload']['name']);

		for ($i=0; $i<$total1; $i++){
			$temporaryDrawingPath = $_FILES['drawing_upload']['tmp_name'][$i];
			if ($temporaryDrawingPath != "") {

				$drawingname = $_FILES['drawing_upload']['name'][$i];

				$location = 'Uploads/';
				print_r($location.$drawingname);
				
				if (move_uploaded_file($temporaryDrawingPath, $location.$drawingname)){


					$drawingurl = mysqli_real_escape_string($connection, $drawingname);

					$sql = "INSERT INTO saasma_archiview_drawings (drawing, project_id)
					VALUES('$drawingurl', '$id');";
					$result2 = mysqli_query($connection, $sql);

					$success['Message'] = "Upload was successful.";
				}
				else {
					$errors['drawingupload'] = 'Drawing upload failed.';
	      		}
			}
		}

	//upload imagery
		$total2 = count($_FILES['imagery_upload']['name']);
		for ($i=0; $i<$total2; $i++){
			$ImageryPath = $_FILES['imagery_upload']['tmp_name'][$i];
			if ($ImageryPath != "") {

				$imageryname = $_FILES['imagery_upload']['name'][$i];
				$location = 'Uploads/';
				
				if (move_uploaded_file($ImageryPath, $location.$imageryname)){

					$imagery = mysqli_real_escape_string($connection, $ImageryPath);

					$sql = "INSERT INTO saasma_archiview_imagery (imagery, project_id)
					VALUES('$imagery', '$id');";
					$result2 = mysqli_query($connection, $sql);

					$success['Message'] = "Upload was successful.";
				}
				else {
					$errors['imageryupload'] = '3D image upload failed.';
	      		}
			}
		}

	} else {
		$errors['form'] = 'Please make sure to fill out fields and upload files';
	}

	if (empty($errors)){
		$_SESSION['message'] = "Upload was successful.";
			header("Location: ?page=showproject&id=$id");
		exit(0);
	} else {
		$errors['save'] = "Please try to upload your project again.";
	}

	print_r($errors);

	include_once('upload.html');
}

function showproject() {

	global $connection;

	$errors = array();

	if (!empty($_GET['id'])) {
    	$id = mysqli_real_escape_string($connection, $_GET['id']);
	}

	$sql1 = "SELECT content.id, content.username, content.projecttitle, content.projecttext
	FROM saasma_archiview_projectcontent AS content WHERE content.id = '$id'";

	$sql2 = "SELECT drawings.drawing, drawings.project_id
	FROM saasma_archiview_drawings AS drawings WHERE drawings.project_id = '$id'";

	$sql3 = "SELECT 3dimages.imagery, 3dimages.project_id
	FROM saasma_archiview_imagery AS 3dimages WHERE 3dimages.project_id = '$id'";

    $result1 = mysqli_query($connection, $sql1);

    if ($result1) {
    	$showprojectquery1['content'] = mysqli_fetch_assoc($result1);
    }

    $result2 = mysqli_query($connection, $sql2);

    $result3 = mysqli_query($connection, $sql3);

	if ($result2) {

		$drawingarray = array();

		while($row = mysqli_fetch_array($result2)) {
			$drawingarray[] = $row;
		}

		
		
	} else {
		$errors[drawings] = 'Drawing query failed.';
	}

	if ($result3) {

		$imageryarray = array();

		while($row = mysqli_fetch_array($result3)) {
			$imageryarray[] = $row;
		}
		
	}else {
		$errors[imagery] = 'Imagery query failed.';
	}

	include_once('showproject.html');

}

function homepage() {
	include_once('home.html');
}

function projects() {
	
	global $connection;

	$sql = "SELECT content.id, content.username, content.projecttitle, content.projecttext, drawings.drawing, drawings.project_id, imagery.imagery, imagery.project_id
	FROM saasma_archiview_projectcontent AS content
	INNER JOIN saasma_archiview_drawings AS drawings ON content.id = drawings.project_id
	INNER JOIN saasma_archiview_imagery AS imagery ON content.id = imagery.project_id"; 
	
	$result = mysqli_query($connection, $sql);

	$projectquery = array();

	while ($project = mysqli_fetch_assoc($result)) {
		$projectquery[] = $project;
	}

	include_once('projects.html');

}


function myprojects() {
	global $connection;

	if (empty($_SESSION['loggedinuser'])) {
    	header("Location: ?page=projects");
 	}

	$currentuser = mysqli_real_escape_string($connection, $_SESSION['loggedinuser']);

	$sql = "SELECT content.id, content.username, content.projecttitle, content.projecttext, drawings.drawing, drawings.project_id, imagery.imagery, imagery.project_id
	FROM saasma_archiview_projectcontent AS content
	INNER JOIN saasma_archiview_drawings AS drawings ON content.id = drawings.project_id
	INNER JOIN saasma_archiview_imagery AS imagery ON content.id = imagery.project_id WHERE content.username = '$currentuser'"; 


	$result = mysqli_query($connection, $sql);

	$individualprojectquery = array();

	while ($project = mysqli_fetch_assoc($result)) {
		$individualprojectquery[] = $project;
	}

	include_once('myprojects.html');
}

function about() {
	include_once('about.html');
}

?>