<?php
namespace rmtools;
function exec_sep_log($cmd, $cwd = NULL, $env = NULL)
{
	$log_stdout = $log_stderr = NULL;

	$descriptor_spec = array(
			1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
			2 => array("pipe", "w") // stderr is a file to write to
	);

	$env = NULL;
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
	if (!make_directory($ftp_stream, dirname($dir))) return false;
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


function upload_build_result_ftp_curl($src_dir, $target)
{
	include __DIR__ . '/../data/config/credentials_ftps.php';

	$ftp = ftp_connect($ftp_server); 
	$login_result = ftp_login($ftp, $user_snaps, $password);
	if (!$login_result) {
		return false;
	}
	$res = ftp_make_directory($ftp, $target. '/logs');
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

		$ch = $curl[$i] = \curl_init();
		$fp = fopen($local_file, "rb");
		$local_file = basename($local_file);

		$remoteurl = "ftps://${ftp_user}:${ftp_password}@${ftpserver}${ftp_path}/${local_file}";

		\curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		\curl_setopt($ch, CURLOPT_URL, $remoteurl);
		\curl_setopt($ch, CURLOPT_UPLOAD, 1);
		\curl_setopt($ch, CURLOPT_INFILE, $fp);
		\curl_setopt($ch , CURLOPT_USERPWD, $ftp_user . ':' . $ftp_password);

		\curl_multi_add_handle ($mh, $ch);
	}

	$files = glob($src_dir . '/logs/*.*');

	foreach ($files as $i => $local_file) {

		$ch = $curl[$i] = \curl_init();
		$fp = fopen($local_file, "rb");
		$local_file = basename($local_file);

		$remoteurl = "ftps://${ftp_user}:${ftp_password}@${ftpserver}${ftp_path}/logs/${local_file}";

		\curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		\curl_setopt($ch, CURLOPT_URL, $remoteurl);
		\curl_setopt($ch, CURLOPT_UPLOAD, 1);
		\curl_setopt($ch, CURLOPT_INFILE, $fp);
		\curl_setopt($ch , CURLOPT_USERPWD, $ftp_user . ':' . $ftp_password);

		\curl_multi_add_handle ($mh, $ch);
	}

	do {
		$n = \curl_multi_exec($mh,$active);
	} while ($active);

	foreach ($curl as $ch) {
		\curl_multi_remove_handle($mh, $ch);
		if (\curl_errno($ch) != 0) {
			echo \curl_error($ch) . "\n";
		}
		\curl_close($ch);
	}
	\curl_multi_close($mh);
}

function send_error_notification($build_entries, $previous_revision, $current_revision, $url_log)
{
	$errors = '';
	$params = NULL;
	$headers =	'From: noreply@php.net' . "\r\n" .
			'Reply-To: noreply@php.net' . "\r\n" .
			'X-Mailer: rmtools/php.net';

	foreach ($build_entries as $build_name => $entries) {
		$errors .= 'Build ' . $build_name . ":\n";
		foreach ($entries as $e) {
			$errors .= implode(', ', $e) . "\n";
		}
		$errors .= "\n\n";
	}
	$to = 'pierre.php@gmail.com, felipensp@gmail.com';
	$subject = '[rmtools] Build error between r' . $previous_revision . ' and  ' . $current_revision;

	ob_start();
	include __DIR__ . '/../template/mail_notification.tpl.php';
	$body = ob_get_contents();
	ob_end_clean();
	echo $mail . "\n";

	if (substr($to, -7) === 'php.net') {
		$params = '-fnoreply@php.net';
	}

	if($params == null){
		return mail($to, $subject, $body, $headers);
	}else{
		return mail($to, $subject, $body, $headers, $params);
	}
}
