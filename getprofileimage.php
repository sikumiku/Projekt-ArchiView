<?php
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
	?>