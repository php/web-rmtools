<?php
namespace rmtools;
class Auth {

	public function isLogged() {
	}

    /**
     * Check user+password against master.php.net [required auth function]
     *
     * Checks if the given user exists and the given
     * plaintext password is correct
     *
     * @return  bool true or int error code
     */
	public function login($user, $pass = '') {
		$post = http_build_query(array(
									"token" => MASTER_AUTH_TOKEN,
									"username" => $user,
									"password" => $pass,
									), '', '&'
								);

		$opts = array(
					"method"  => "POST",
					"header"  => "Content-type: application/x-www-form-urlencoded",
					"content" => $post,
					"ignore_errors" => TRUE,
				);

		$ctx = stream_context_create(array("http" => $opts));
		$s = file_get_contents("https://master.php.net/fetch/cvsauth.php", false, $ctx);
		if (!$s) {
			return false;
		}
		$a = unserialize($s);
		/*
		define("E_UNKNOWN", 0);
		define("E_USERNAME", 1);
		define("E_PASSWORD", 2);
		*/
		if (!is_array($a)) {
			return 0;
		}
		if (isset($a["errno"])) {
			return (int)$a["errno"];
		}

		return true;
	}

}
