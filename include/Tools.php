<?php
namespace rmtools;
function exec_sep_log($cmd, $cwd = NULL, $env = NULL)
{
    $return_value = false;
	$log_stdout = $log_stderr = NULL;

	$descriptor_spec = array(
			1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
			2 => array("pipe", "w") // stderr is a file to write to
	);

	$process = proc_open($cmd, $descriptor_spec, $pipes, $cwd, $env);

	if (is_resource($process)) {
		// $pipes now looks like this:
		// 0 => writeable handle connected to child stdin
		// 1 => readable handle connected to child stdout
		// Any error output will be appended to /tmp/error-output.txt
		$log_stdout = stream_get_contents($pipes[1]);
		$log_stderr = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);

		$return_value = proc_close($process);
	}

	return array(
			'log_stdout' => $log_stdout,
			'log_stderr' => $log_stderr,
			'return_value' => $return_value
		);
}

function exec_single_log($cmd, $cwd = NULL, $env = NULL)
{
	/* We take one log only, for stdout and stderr 
	  So it looks like a normal output.
	  */
	$descriptor_spec = array(
		1 => array("pipe", "w"),  // stdout
	);

	$cmd .= ' 2>&1';

	$process = proc_open($cmd, $descriptor_spec, $pipes, $cwd, $env);

	if (is_resource($process)) {
		// $pipes now looks like this:
		// 0 => writeable handle connected to child stdin
		// 1 => readable handle connected to child stdout
		// Any error output will be appended to /tmp/error-output.txt
		$log = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		$return_value = proc_close($process);
	} else {
		return false;
	}

	$ret = array(
		'return_value' => $return_value,
		'log' => $log
	);
	return $ret;
}

function wget($url, $dest)
{
	$fp = fopen ($dest, 'wb+');
	$ch = \curl_init();
	\curl_setopt($ch, CURLOPT_URL,$url);
	\curl_setopt($ch, CURLOPT_FILE, $fp);
	\curl_exec($ch);
	\curl_close($ch);
	\fclose($fp);
}

function rmdir_rf($path)
{
	if (is_dir($path)) {
		$objects = scandir($path);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (is_dir($path."/".$object)) {
					rmdir_rf($path."/".$object); 
				}else {
					@unlink($path."/".$object);
				}
			}
		}
		reset($objects);
		rmdir($path);
	} 
}

function ftp_make_directory($ftp_stream, $dir)
{
	if (ftp_is_dir($ftp_stream, $dir) || @ftp_mkdir($ftp_stream, $dir)) return true;
	if (!ftp_make_directory($ftp_stream, dirname($dir))) return false;
    return ftp_mkdir($ftp_stream, $dir);
}	

function ftp_is_dir($ftp_stream, $dir)
{
	$original_directory = ftp_pwd($ftp_stream);
	if ( @ftp_chdir( $ftp_stream, $dir ) ) {
		ftp_chdir( $ftp_stream, $original_directory );
		return true;
	} else {
		return false;
	}
}

function upload_file($src, $target)
{
	include __DIR__ . '/../data/config/credentials_ftps.php';

	$ftp = ftp_ssl_connect($ftp_server); 
	$login_result = ftp_login($ftp, $user_snaps, $password);
	if (!$login_result) {
		return false;
	}
	$res = ftp_put($ftp, $target, $src, FTP_BINARY);
	ftp_close($ftp);
	return $res;
}

function upload_file_curl($src, $target) // SAZ - Like upload_file(), but using curl
{
	foreach (['credentials_ftps', 'credentials_ftps2'] as $credentials_ftps) {
		include __DIR__ . "/../data/config/$credentials_ftps.php";
		$ftp_user = $user_snaps;
		$ftp_password = $password;

		$remoteurl = "ftps://" . urlencode($ftp_user) . ":" . urlencode($ftp_password) . "@${ftp_server}/${target}";
		$fp = fopen($src, "rb");

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_URL, $remoteurl);
		curl_setopt($ch, CURLOPT_UPLOAD, 1);
		curl_setopt($ch, CURLOPT_INFILE, $fp);
		curl_setopt($ch, CURLOPT_USERPWD, $ftp_user . ':' . $ftp_password);
		if (curl_exec($ch) === false)  {
			echo "Error, upload_file_curl(): " . curl_error($ch) . "\n";
			return false;
		}
		fclose($fp);
	}
	return true;
}

function update_snapshot_page()
{
	include __DIR__ . '/../data/config/credentials_ftps.php';

	$ftp = ftp_ssl_connect($ftp_server); 
	$login_result = ftp_login($ftp, $user_snaps, $password);
	if (!$login_result) {
		return false;
	}
	$res = ftp_raw($ftp, 'site UPDATE_SNAPS_PAGE');
	ftp_close($ftp);
	return $res;
}

function upload_build_result_ftp_curl($src_dir, $target)
{
	foreach (['credentials_ftps', 'credentials_ftps2'] as $credentials_ftps) {
		include __DIR__ . "/../data/config/$credentials_ftps.php";

		$ftp = ftp_ssl_connect($ftp_server);
		if (!$ftp) {
			echo "Cannot connect to $ftp_server\n";
			return false;
		}
		$login_result = ftp_login($ftp, $user_snaps, $password);
		if (!$login_result) {
			return false;
		}
		
		$try = 0;
		do {
			$status = ftp_make_directory($ftp, $target. '/logs');
			$try++;
		} while ( $status === false && $try < 10 );
		ftp_close($ftp);

		$curl = array();

		$ftp_path   = $target;
		$ftp_user   = $user_snaps;
		$ftp_password   = $password;

		if ($ftp_path[0] != '/') {
			$ftp_path = '/' . $ftp_path;
		}
		$mh = \curl_multi_init();

		$files = glob($src_dir . '/*.{zip,json}', GLOB_BRACE);
		foreach ($files as $i => $local_file) {

			$curl[$i] = $ch =  \curl_init();
			$fp = fopen($local_file, "rb");
			$local_file = basename($local_file);

			$remoteurl = "ftps://" . urlencode($ftp_user) . ":" . urlencode($ftp_password) . "@${ftp_server}${ftp_path}/${local_file}";

			\curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			\curl_setopt($ch, CURLOPT_URL, $remoteurl);
			\curl_setopt($ch, CURLOPT_UPLOAD, 1);
			\curl_setopt($ch, CURLOPT_INFILE, $fp);
			\curl_setopt($ch , CURLOPT_USERPWD, $ftp_user . ':' . $ftp_password);

			\curl_multi_add_handle ($mh, $ch);
		}

		$files = glob($src_dir . '/logs/*.*');
		$offset = $i + 1;
		foreach ($files as $i => $local_file) {

			$ch = $curl[$offset + $i] = \curl_init();
			$fp = fopen($local_file, "rb");
			$local_file = basename($local_file);

			$remoteurl = "ftps://" . urlencode($ftp_user) . ":" . urlencode($ftp_password) . "@${ftp_server}${ftp_path}/logs/${local_file}";

			\curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			\curl_setopt($ch, CURLOPT_URL, $remoteurl);
			\curl_setopt($ch, CURLOPT_UPLOAD, 1);
			\curl_setopt($ch, CURLOPT_INFILE, $fp);
			\curl_setopt($ch , CURLOPT_USERPWD, $ftp_user . ':' . $ftp_password);

			\curl_multi_add_handle ($mh, $ch);
		}

		$retry = 0;
		do {
			$err = 0;
			do {
				\curl_multi_exec($mh,$active);
				$info = curl_multi_info_read($mh);
				if ($info !== false) {
					curl_multi_remove_handle($mh, $info['handle']);
					if ($info['result'] != 0)  {
						$err = 1;
//						echo curl_getinfo($info['handle'], CURLINFO_EFFECTIVE_URL) . "\n";
//						echo curl_error($info['handle']) . "\n\n";
						curl_multi_add_handle($mh, $info['handle']);
					}
				}
			} while ($active);
			$retry++;
		} while ($err != 0 && $retry < 10);

		foreach ($curl as $ch) {
			\curl_multi_remove_handle($mh, $ch);
			if (\curl_errno($ch) != 0) {
				echo \curl_error($ch) . "\n";
			}
			\curl_close($ch);
		}
		//\curl_multi_close($mh);
	}
    return true;
}

function send_error_notification($branch_name, $build_entries, $previous_revision, $current_revision, $url_log)
{
	$errors = '';
	$params = NULL;
	$headers =	'From: noreply@php.net' . "\r\n" .
			'Reply-To: noreply@php.net' . "\r\n" .
			'X-Mailer: rmtools/php.net' . "\r\n" .
			'Bcc: pierre.php@gmail.com, felipensp@gmail.com' . "\r\n";

	foreach ($build_entries as $build_name => $entries) {
		$errors .= $branch_name . ', build ' . $build_name . ":\n";
		foreach ($entries as $e) {
			$errors .= implode(', ', $e) . "\n";
		}
		$errors .= "\n\n";
	}

//	$to = 'internals@lists.php.net';
	$to = 'ostcphp@microsoft.com';
	$subject = '[rmtools][' . $branch_name . '] Build error between r' . $previous_revision . ' and  ' . $current_revision;

	ob_start();
	include __DIR__ . '/../template/mail_notification.tpl.php';
	$body = ob_get_contents();
	ob_end_clean();

	if (substr($to, -7) === 'php.net') {
		$params = '-fnoreply@php.net';
	}

	if($params == null){
		return mail($to, $subject, $body, $headers);
	}else{
		return mail($to, $subject, $body, $headers, $params);
	}
}

function copy_r($from, $to)
{
	if (is_dir($from)) {
		if (!file_exists($to)) {
			mkdir($to);
		}
		$objs = scandir($from);
		foreach ($objs as $child) {
			if ('.' == $child || '..' == $child) {
				continue;
			}

			if (is_dir("$from/$child")) {
				if (!copy_r("$from/$child", "$to/$child")) {
					return false;
				}
			} else {
				if (!copy("$from/$child", "$to/$child")) {
					return false;
				}
			}
		}

		return true;

	} else if (is_file($from)) {
		return copy($from, $to);
	}

	return false;
}

function xmail($from, $to, $subject, $text, array $attachment = array())
{
	$header = $mail = array();
	$boundary = md5(uniqid(mt_rand(), 1));

	$header[] = "MIME-Version: 1.0";
	$header[] = "From: $from";
	$header[] = "Content-Type: multipart/mixed;\r\n boundary=\"$boundary\"";

	$mail[] = '--' . $boundary;
	$mail[] = "Content-Type: text/plain; charset=latin1";
	$mail[] = "Content-Transfer-Encoding: 7bit\r\n";
	$mail[] = "$text\r\n";

	foreach($attachment as $att) {
		if (!$att || !file_exists($att) || !is_file($att) || !is_readable($att)) {
			continue;
		}

		$fname = basename($att);
		$fsize = filesize($att);
		if (function_exists('mime_content_type')) {
			$mime_type = mime_content_type($att);
		} else {
			$mime_type = 'application/octet-stream';
		}
		$raw = file_get_contents($att);

		$mail[] = '--' . $boundary;
		$mail[] = "Content-Type: $mime_type; name=\"$fname\"";
		$mail[] = "Content-Transfer-Encoding: base64";
		$mail[] = "Content-Disposition: attachment; filename=\"$fname\"; size=\"$fsize\"\r\n";
		$mail[] = chunk_split(base64_encode($raw)) . "\r\n";
	}

	$mail[] = '--' . $boundary . '--';

	return mail($to, $subject, implode("\r\n", $mail), implode("\r\n", $header));
}

function upload_pecl_pkg_ftp_curl($files, $logs, $target)
{
	foreach (['credentials_ftps', 'credentials_ftps2'] as $credentials_ftps) {
		include __DIR__ . "/../data/config/$credentials_ftps.php";

		$ftp = ftp_ssl_connect($ftp_server);
		if (!$ftp) {
			echo "Cannot connect to $ftp_server\n";
			return false;
		}
		$login_result = ftp_login($ftp, $user_pecl, $password_pecl);
		if (!$login_result) {
			return false;
		}
		
		$try = 0;
		do {
			$status = ftp_make_directory($ftp, $target . '/logs/');
			$try++;
		} while ( $status === false && $try < 10 );
		ftp_close($ftp);

		$curl = array();

		$ftp_path   = $target;
		$ftp_user   = $user_pecl;
		$ftp_password   = $password_pecl;

		if ($ftp_path[0] != '/') {
			$ftp_path = '/' . $ftp_path;
		}
		$mh = \curl_multi_init();

		$files = is_array($files) ? $files : array($files);
		$offset = 0;
		foreach ($files as $i => $local_file) {

			$curl[$i] = $ch =  \curl_init();
			$fp = fopen($local_file, "rb");
			$local_file = basename($local_file);

			$remoteurl = "ftps://" . urlencode($ftp_user) . ":" . urlencode($ftp_password) . "@${ftp_server}${ftp_path}/${local_file}";

			\curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			\curl_setopt($ch, CURLOPT_URL, $remoteurl);
			\curl_setopt($ch, CURLOPT_UPLOAD, 1);
			\curl_setopt($ch, CURLOPT_INFILE, $fp);
			\curl_setopt($ch , CURLOPT_USERPWD, $ftp_user . ':' . $ftp_password);

			\curl_multi_add_handle ($mh, $ch);
			$offset++;
		}

		$files = is_array($logs) ? $logs : array($logs);
		foreach ($files as $i => $local_file) {

			$ch = $curl[$offset + $i] = \curl_init();
			$fp = fopen($local_file, "rb");
			$local_file = basename($local_file);

			$remoteurl = "ftps://" . urlencode($ftp_user) . ":" . urlencode($ftp_password) . "@${ftp_server}${ftp_path}/logs/${local_file}";

			\curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			\curl_setopt($ch, CURLOPT_URL, $remoteurl);
			\curl_setopt($ch, CURLOPT_UPLOAD, 1);
			\curl_setopt($ch, CURLOPT_INFILE, $fp);
			\curl_setopt($ch , CURLOPT_USERPWD, $ftp_user . ':' . $ftp_password);

			\curl_multi_add_handle ($mh, $ch);
		}

		$retry = 0;
		do {
			$err = 0;
			do {
				\curl_multi_exec($mh,$active);
				$info = curl_multi_info_read($mh);
				if ($info !== false) {
					curl_multi_remove_handle($mh, $info['handle']);
					if ($info['result'] != 0)  {
						$err = 1;
//						echo curl_getinfo($info['handle'], CURLINFO_EFFECTIVE_URL) . "\n";
//						echo curl_error($info['handle']) . "\n\n";
						curl_multi_add_handle($mh, $info['handle']);
					}
				}
			} while ($active);
			$retry++;
		} while ($err != 0 && $retry < 10);

		foreach ($curl as $ch) {
			\curl_multi_remove_handle($mh, $ch);
			if (\curl_errno($ch) != 0) {
				echo \curl_error($ch) . "\n";
			}
			\curl_close($ch);
		}
		//\curl_multi_close($mh);
	}
    return true;
}

function upload_pickle_pkg_ftp_curl($files, $logs, $target)
{
	include __DIR__ . '/../data/config/credentials_ftps.php';

	$ftp = ftp_ssl_connect($ftp_server);
	if (!$ftp) {
		echo "Cannot connect to $ftp_server\n";
		return false;
	}
	$login_result = ftp_login($ftp, $user_pickle, $password_pickle);
	if (!$login_result) {
		return false;
	}
	
	$try = 0;
	do {
		$status = ftp_make_directory($ftp, $target . '/logs/');
		$try++;
	} while ( $status === false && $try < 10 );
	ftp_close($ftp);

	$curl = array();

	$ftp_path   = $target;
	$ftp_user   = $user_pickle;
	$ftp_password   = $password_pickle;

	if ($ftp_path[0] != '/') {
		$ftp_path = '/' . $ftp_path;
	}
	$mh = \curl_multi_init();

	$files = is_array($files) ? $files : array($files);
	$offset = 0;
	foreach ($files as $i => $local_file) {

		$curl[$i] = $ch =  \curl_init();
		$fp = fopen($local_file, "rb");
		$local_file = basename($local_file);

		$remoteurl = "ftps://" . urlencode($ftp_user) . ":" . urlencode($ftp_password) . "@${ftp_server}${ftp_path}/${local_file}";

		\curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		\curl_setopt($ch, CURLOPT_URL, $remoteurl);
		\curl_setopt($ch, CURLOPT_UPLOAD, 1);
		\curl_setopt($ch, CURLOPT_INFILE, $fp);
		\curl_setopt($ch , CURLOPT_USERPWD, $ftp_user . ':' . $ftp_password);

		\curl_multi_add_handle ($mh, $ch);
		$offset++;
	}

	$files = is_array($logs) ? $logs : array($logs);
	foreach ($files as $i => $local_file) {

		$ch = $curl[$offset + $i] = \curl_init();
		$fp = fopen($local_file, "rb");
		$local_file = basename($local_file);

		$remoteurl = "ftps://" . urlencode($ftp_user) . ":" . urlencode($ftp_password) . "@${ftp_server}${ftp_path}/logs/${local_file}";

		\curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		\curl_setopt($ch, CURLOPT_URL, $remoteurl);
		\curl_setopt($ch, CURLOPT_UPLOAD, 1);
		\curl_setopt($ch, CURLOPT_INFILE, $fp);
		\curl_setopt($ch , CURLOPT_USERPWD, $ftp_user . ':' . $ftp_password);

		\curl_multi_add_handle ($mh, $ch);
	}

	$retry = 0;
	do {
		$err = 0;
		do {
			\curl_multi_exec($mh,$active);
			$info = curl_multi_info_read($mh);
			if ($info !== false) {
				curl_multi_remove_handle($mh, $info['handle']);
				if ($info['result'] != 0)  {
					$err = 1;
//					echo curl_getinfo($info['handle'], CURLINFO_EFFECTIVE_URL) . "\n";
//					echo curl_error($info['handle']) . "\n\n";
					curl_multi_add_handle($mh, $info['handle']);
				}
			}
		} while ($active);
		$retry++;
	} while ($err != 0 && $retry < 10);

	foreach ($curl as $ch) {
		\curl_multi_remove_handle($mh, $ch);
		if (\curl_errno($ch) != 0) {
			echo \curl_error($ch) . "\n";
		}
		\curl_close($ch);
	}
	//\curl_multi_close($mh);
    return true;
}

