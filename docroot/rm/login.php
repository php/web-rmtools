<?php
include 'Auth.php';

use rmtools as rm;
session_name('_rmtools_');
session_start();

if (!isset($_SESSION['username'])) {
	$username = $password = FALSE;

	if (isset($_POST['username'])) {
		$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
		$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
		// Set magic cookie if login information is available
	} elseif(false) { // FIXME: Disabled magic cookie :]
		// Preserve information previously set in magic cookie if available
		if (isset($_COOKIE['MAGIC_COOKIE']) && !isset($_POST['user']) && !isset($_POST['pw'])) {
			list($user, $password) = explode(":", base64_decode($_COOKIE['MAGIC_COOKIE']), 2);
		}
	}
	$res = rm\Auth::login($username, $password);

	if (!$res) {
		$title = 'login';
		include TPL_PATH . '/header.php';
		include TPL_PATH . '/login.php';
		include TPL_PATH . '/footer.php';
		exit();
	}

	$_SESSION['username'] = $username;
	$_SESSION['time'] = time();

    // FIXME: Disabled magic cookie session
	if (false && $password && $username) {
		setcookie(
			"MAGIC_COOKIE",
			base64_encode("$username:$password"),
			time()+3600*24*12,
			'/',
			'.php.net',
			false, // Secure
			true   // HTTP Only
		);
	}
} else {
	$username = $_SESSION['username'];
}
