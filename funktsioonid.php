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

function login() {
	if (isset($_POST['user'])) {
		include_once('projects.html');
	}

	include_once('views/login.html');
}
