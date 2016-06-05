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

function getprofileimage() {

	global $connection;

	if (!empty($_SESSION['loggedinuser'])) {
		$profileuser = $_SESSION['loggedinuser'];

		$sqlprofileimage = "SELECT kasutajad.username, kasutajad.profileimage
		FROM saasma_archiview_kasutajad AS kasutajad WHERE kasutajad.username = '$profileuser'";

		$resultprofileimage = mysqli_query($connection, $sqlprofileimage);

		$user_profileimage = array();

		while ($profileimage = mysqli_fetch_assoc($resultprofileimage)) {
			$user_profileimage[] = $profileimage;
		}

	}
}


function login(){

	if (isset($_POST['loggedinuser'])) {
		include_once('projects.html');
	}

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

	include_once('login.html');
}

function logout(){
	$_SESSION=array();
	session_destroy();
	header("Location: ?page=homepage");
}

function registration(){
	global $connection;

	if (isset($_SERVER['REQUEST_METHOD'])) {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$errors = array();

			if (empty($_POST['username_reg'])) {
				$errors[] = "Please enter username.";
			}else {
				$reg_user = htmlspecialchars($_POST['username_reg']);
			}
			//get all the usernames from server
			$sql = "SELECT kasutajad.username
				FROM saasma_archiview_kasutajad AS kasutajad";

			$resultusers = mysqli_query($connection, $sql);

			$userquery = array();

			while ($users = mysqli_fetch_assoc($resultusers)) {
				$userquery[] = $users;
			}

			//check if such an user already exists
			foreach ($userquery as $user) {
				if ($user['username'] == $reg_user) {
					$errors[] = "This username already exists.";
				}
			}

			if (strlen($_POST['username_reg']) < 5) {
				$errors[] = "Please enter a longer username.";
			}

			if ($_POST['password_reg1'] != $_POST['password_reg2']) {
				$errors[] = "Passwords do not match, please enter again.";
			}
			if (strlen($_POST['password_reg1']) < 6) {
				$errors[] = "Please enter a longer password.";
			}


			if (empty($_POST['password_reg1'])) {
				$errors[] = "Please enter password.";
			} else {
				$reg_pass = htmlspecialchars($_POST['password_reg1']);
			}
			if (empty($_POST['password_reg2'])) {
				$errors[] = "Please repeat password.";
			}

			if (empty($errors)){
				//viska sisestatud andmed andmebaasi ja pane andmebaasist sisestatud kasutaja sessiooni, suuna projektide lehele, muidu kuva registration.html
	
				$registeredusername = mysqli_real_escape_string($connection, $reg_user);
				$registeredpassword = mysqli_real_escape_string($connection, $reg_pass);
				$sql = "INSERT INTO saasma_archiview_kasutajad (username, password, profileimage) VALUES ('$registeredusername', SHA1('$registeredpassword'), 'profile_image_default.png')";
				$result = mysqli_query($connection, $sql) or die ("Proovi uuesti.");

				if ($result) {
					if (mysqli_insert_id($connection) > 0) {
						$_SESSION['loggedinuser'] = $registeredusername;
						header("Location: ?page=projects");
						exit(0);
					}
				}
			} 
		}
	}

	if (isset($_POST['loggedinuser'])) {
		include_once('projects.html');
	} else {
		include_once('registration.html');
	}
}

function upload() {

	global $connection;

	if (empty($_SESSION['loggedinuser'])) {
    	header("Location: ?page=projects");
 	}
	$uploaderror = array();
	$errors = array();
	$success = array();
	//upload project
	if(isset($_POST['upload'])){

		$projecttitleupload = htmlspecialchars($_POST['projecttitle_upload']);
		$projecttextupload = htmlspecialchars($_POST['projecttext_upload']);

			if (empty($projecttitleupload) && empty($projecttextupload)){
				$uploaderror['upload'] = 'Palun sisesta oma projekti pealkiri ja kirjeldus.';
			}

		$user = mysqli_real_escape_string($connection, $_SESSION['loggedinuser']);
		$projecttitle = mysqli_real_escape_string($connection, $projecttitleupload);
		$projecttext = mysqli_real_escape_string($connection, $projecttextupload);
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

				if (file_exists($location.$drawingname)) {
					$uploaderror['upload'] = 'This file already exists. Please upload a different file or rename it.';
				}

				if ($_FILES['drawing_upload']['size'][$i] > 5000000) {
					$uploaderror['upload'] = 'Please upload image that is smaller than 500 kb.';
				}

				if ($_FILES['drawing_upload']['error'][$i] !== UPLOAD_ERR_OK) {
					die ("Upload failed with error code " . $_FILES['drawing_upload']['error'][$i]);
				}

				$info = getimagesize($temporaryDrawingPath);
				if ($info === FALSE) {
					die("Unable to determine image type of uploaded file.");
					$uploaderror['upload'] = 'Unable to determine uploaded filetype.';
				}

				if (($info[2] !== IMAGETYPE_GIF) && ($info[2] !== IMAGETYPE_JPEG) && ($info[2] !== IMAGETYPE_PNG)) {
					die("File is not gif, jpeg or png.");
					$uploaderror['upload'] = 'Please upload only gif, jpeg or png fileformats.';
				}

				if (empty($uploaderror)) {
				
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
		}

	//upload imagery
		$total2 = count($_FILES['imagery_upload']['name']);
		for ($i=0; $i<$total2; $i++){
			$temporaryImageryPath = $_FILES['imagery_upload']['tmp_name'][$i];

			
			if ($temporaryImageryPath != "") {

				$imageryname = $_FILES['imagery_upload']['name'][$i];
				$location = 'Uploads/';

				if (file_exists($location.$imageryname)) {
					$uploaderror['upload'] = 'This file already exists. Please upload a different file or rename it.';
				}

				if ($_FILES['imagery_upload']['size'][$i] > 5000000) {
					$uploaderror['upload'] = 'Please upload image that is smaller than 5 Mb.';
				}

				if ($_FILES['imagery_upload']['error'][$i] !== UPLOAD_ERR_OK) {
					die ("Upload failed with error code " . $_FILES['imagery_upload']['error'][$i]);
				}

				$info = getimagesize($temporaryImageryPath);
				if ($info === FALSE) {
					die("Unable to determine image type of uploaded file.");
					$uploaderror['upload'] = 'Unable to determine uploaded filetype.';
				}

				if (($info[2] !== IMAGETYPE_GIF) && ($info[2] !== IMAGETYPE_JPEG) && ($info[2] !== IMAGETYPE_PNG)) {
					die("File is not gif, jpeg or png.");
					$uploaderror['upload'] = 'Please upload only gif, jpeg or png fileformats.';
				}



				if (empty($uploaderror)) {
					if (move_uploaded_file($temporaryImageryPath, $location.$imageryname)){

						$imageryurl = mysqli_real_escape_string($connection, $imageryname);

						$sql = "INSERT INTO saasma_archiview_imagery (imagery, project_id)
						VALUES('$imageryurl', '$id');";
						$result2 = mysqli_query($connection, $sql);

						$success['Message'] = "Upload was successful.";
					}
					else {
						$errors['imageryupload'] = '3D image upload failed.';
		      		}
				} 
				
			
			}
		}

	} else {
		$errors['form'] = 'Please make sure to fill out fields and upload files';
	}

	if (empty($errors) && empty($uploaderror)){
		$_SESSION['message'] = "Upload was successful.";
			header("Location: ?page=showproject&id=$id");
		exit(0);
	} else {
		$errors['save'] = "Please try to upload your project again.";
	}

	

	include_once('upload.html');
}

function showproject() {

	global $connection;

	if (empty($_SESSION['loggedinuser'])) {
    	header("Location: ?page=projects");
 	}

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

	$sql = "SELECT content.id, content.username, content.projecttitle, content.projecttext, imagery.imagery, imagery.project_id
FROM saasma_archiview_projectcontent AS content
LEFT JOIN saasma_archiview_imagery AS imagery ON content.id = imagery.project_id GROUP BY content.id"; 
	
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

	$sql = "SELECT content.id, content.username, content.projecttitle, content.projecttext, imagery.imagery, imagery.project_id
			FROM saasma_archiview_projectcontent AS content
			LEFT JOIN saasma_archiview_imagery AS imagery ON content.id = imagery.project_id
			WHERE content.username =  '$currentuser'
			GROUP BY content.id";


	$result = mysqli_query($connection, $sql);

	$individualprojectquery = array();

	while ($project = mysqli_fetch_assoc($result)) {
		$individualprojectquery[] = $project;
	}

	include_once('myprojects.html');
}

function editprofile() {
	global $connection;

	if (empty($_SESSION['loggedinuser'])) {
    	header("Location: ?page=projects");
 	}

 	//take file from upload form
	//upload to server
 	//place in folder

 		if (isset($_POST['profileimageupload'])) {


 			if(isset($_FILES["profilepicture"]["name"]) && isset($_FILES["profilepicture"]["tmp_name"])) {

	 			$profileimagename = $_FILES["profilepicture"]["name"];
	 			$profileimagetmpname = $_FILES["profilepicture"]["tmp_name"];

	 			$error = array();
	 			$error = $_FILES["profilepicture"]["error"];

	 			if (isset($profileimagename)){
	 				if (!empty($profileimagename)) {
	 					$location = 'Profileimages/';

	 					if (file_exists($location.$profileimagename)) {
							$uploaderror['upload'] = 'This file already exists. Please upload a different file or rename it.';
						}

						if ($_FILES['profilepicture']['size'] > 5000000) {
							$uploaderror['upload'] = 'Please upload image that is smaller than 5 Mb.';
						}

						if ($error !== UPLOAD_ERR_OK) {
							die ("Upload failed with error code " . $error);
						}

						$info = getimagesize($profileimagetmpname);
						if ($info === FALSE) {
							die("Unable to determine image type of uploaded file.");
							$uploaderror['upload'] = 'Unable to determine uploaded filetype.';
						}

						if (($info[2] !== IMAGETYPE_GIF) && ($info[2] !== IMAGETYPE_JPEG) && ($info[2] !== IMAGETYPE_PNG)) {
							die("File is not gif, jpeg or png.");
							$uploaderror['upload'] = 'Please upload only gif, jpeg or png fileformats.';
						}


	 					if (move_uploaded_file($profileimagetmpname, $location.$profileimagename)) {
	 						$success['Message'] = "Profile image upload was successful.";
	 					}

	 					if (!empty($success)) {
	 						$user = mysqli_real_escape_string($connection, $_SESSION['loggedinuser']);
	 						$uploadedprofileimage = mysqli_real_escape_string($connection, $profileimagename);

	 						$sqlnewprofileimage = "UPDATE saasma_archiview_kasutajad SET profileimage = '$uploadedprofileimage' WHERE username = '$user'";

	 						$resultnewprofileimage = mysqli_query($connection, $sqlnewprofileimage);

	 						echo $resultnewprofileimage;
	 					}
	 				}
	 			}
 			}

 		}

 	if (empty($_SESSION['loggedinuser'])) {
    	header("Location: ?page=projects");
 	}

 	//view current profile image, take image from server
 	if (!empty($_SESSION['loggedinuser'])) {
 		$profileuser = $_SESSION['loggedinuser'];

    	$sqlprofileimage = "SELECT kasutajad.username, kasutajad.profileimage
		FROM saasma_archiview_kasutajad AS kasutajad WHERE kasutajad.username = '$profileuser'";

		$resultprofileimage = mysqli_query($connection, $sqlprofileimage) or die ("This user does not have profile image.");

		$user_profileimage = array();

		while ($profileimage = mysqli_fetch_assoc($resultprofileimage)) {
			$user_profileimage[] = $profileimage;
		}
 	}


	include_once('editprofile.html');
}

function about() {
	include_once('about.html');
}

?>