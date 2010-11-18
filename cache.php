<?php

// http://wordpress.org/extend/plugins/hyper-cache-extended/
global $hyper_cache_stop;

$hyper_cache_stop = false;

hyper_log_cache('hyper cache init',3);

// Do not cache post request (comments, plugins and so on)
if ($_SERVER["REQUEST_METHOD"] == 'POST')
	return false;

// Try to avoid enabling the cache if sessions are managed with request parameters and a session is active
if (defined(SID) && SID != ''){
	hyper_log_cache('SID found returning',2);
	return false;
}

$hyper_uri = $_SERVER['REQUEST_URI'];
$hyper_qs = strpos($hyper_uri, '?');

if ($hyper_qs !== false) {
	if ($hyper_cache['strip_qs'])
		$hyper_uri = substr($hyper_uri, 0, $hyper_qs);
	else
		if (!$hyper_cache['cache_qs'])
			return false;
}

if (strpos($hyper_uri, 'robots.txt') !== false)
	return false;

// Checks for rejected url
if ($hyper_cache_reject !== false) {
	foreach ($hyper_cache_reject as $uri) {
		if (substr($uri, 0, 1) == '"') {
			if ($uri == '"' . $hyper_uri . '"')
				return false;
		}
		if (substr($hyper_uri, 0, strlen($uri)) == $uri)
			return false;
	}
}

if ($hyper_cache['reject_agents'] !== false) {
	$hyper_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	foreach ($hyper_cache['reject_agents'] as $hyper_a) {
		if (strpos($hyper_agent, $hyper_a) !== false)
			return false;
	}
}

// Do nested cycles in this order, usually no cookies are specified
if ($hyper_cache['reject_cookies'] !== false) {
	foreach ($hyper_cache['reject_cookies'] as $hyper_c) {
		foreach ($_COOKIE as $n => $v) {
			if (substr($n, 0, strlen($hyper_c)) == $hyper_c)
				return false;
		}
	}
}

// Do not use or cache pages when a wordpress user is logged on
foreach ($_COOKIE as $n => $v) {
	// If it's required to bypass the cache when the visitor is a commenter, stop.
	if ($hyper_cache['comment'] && substr($n, 0, 15) == 'comment_author_')
		return false;

	// SHIT!!! This test cookie makes to cache not work!!!
	if ($n == 'wordpress_test_cookie')
		continue;
	// wp 2.5 and wp 2.3 have different cookie prefix, skip cache if a post password cookie is present, also
	if (substr($n, 0, 14) == 'wordpressuser_' || substr($n, 0, 10) == 'wordpress_' || substr($n, 0, 12) == 'wp-postpass_') {
		return false;
	}
}

// Do not cache WP pages, even if those calls typically don't go throught this script
if (strpos($hyper_uri, '/wp-') !== false)
	return false;

// We don't support Multisite for now
if (function_exists('is_multisite') && is_multisite() && strpos($hyper_uri, '/files/') !== false)
	return false;

$hyper_uri = $_SERVER['HTTP_HOST'] . $hyper_uri;

// The name of the file with html and other data
$hyper_cache_name = md5($hyper_uri);
$hc_file = $hyper_cache['path'] . $hyper_cache_name . hyper_mobile_type() . '.dat';

if (!file_exists($hc_file)) {
	hyper_log_cache('Cache not found! hyper_cache_start()',3);
	hyper_cache_start(false);
	return;
}

if(!$hyper_cache['load']) $hyper_cache['load'] = 5;

$loadavg = explode(' ',@file_get_contents('/proc/loadavg'));
$server_load = (float)$loadavg[0];

$hc_file_time = @ filemtime($hc_file);
$hc_file_age = time() - $hc_file_time;

if ($hc_file_age > $hyper_cache['timeout']){
	if($server_load < $hyper_cache['load']) {
		hyper_cache_start();
		return;
	}else{
		hyper_log_cache('File expired but Server load ('.$server_load.') above ('.$hyper_cache['load'].')',2);
	}
}

$hc_invalidation_time = @ filemtime($hyper_cache['path'] . '_global.dat');
if ($hc_invalidation_time && $hc_file_time < $hc_invalidation_time) {
	hyper_log_cache('Global expired! hyper_cache_start()',3);
	hyper_cache_start();
	return;
}

// Load it and check is it's still valid
$hyper_data = @ unserialize(file_get_contents($hc_file));

if (!$hyper_data) {
	hyper_log_cache('Invalid data! hyper_cache_start()',1);
	hyper_cache_start();
	return;
}

if ($hyper_data['type'] == 'home' || $hyper_data['type'] == 'archive') {

	$hc_invalidation_archive_file = @ filemtime($hyper_cache['path'] . '_archives.dat');
	if ($hc_invalidation_archive_file){
		if($hc_file_time < $hc_invalidation_archive_file){
			if($server_load < $hyper_cache['load']) {
				hyper_log_cache('Archive or home expired! hyper_cache_start()',2);
				hyper_cache_start();
				return;
			}else{
				hyper_log_cache('Archives expired but Server load ('.$server_load.') above ('.$hyper_cache['load'].')',2);
			}
		}
	}
}

// Valid cache file check ends here
if ($hyper_data['location']) {
	hyper_log_cache('Sending Location',3);
	header('Location: ' . $hyper_data['location']);
	flush();
	die();
}

// It's time to serve the cached page
if (array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER)) {
	$if_modified_since = strtotime(preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]));
	if ($if_modified_since >= $hc_file_time) {
		hyper_log_cache('Sending 304 Not Modified',3);
		header("HTTP/1.0 304 Not Modified");
		flush();
		die();
	}
}

###############################################
#### Now serve the real content ###############
###############################################

// True if user ask to NOT send Last-Modified
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
if (!$hyper_cache['lastmodified']) {
	header('Last-Modified: ' . date("r", $hc_file_time));
}

header('Content-Type: ' . $hyper_data['mime']);
if ($hyper_data['status'] == 404)
	header("HTTP/1.1 404 Not Found");

// Send the cached html
if ($hyper_cache['gzip'] && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false && strlen($hyper_data['gz']) > 0) {
	hyper_log_cache('Send gzip encoded data',3);
	header('Content-Encoding: gzip');
	echo $hyper_data['gz'];
} else {
	// No compression accepted, check if we have the plain html or
	// decompress the compressed one.
	if ($hyper_data['html']) {
		//header('Content-Length: ' . strlen($hyper_data['html']));
		hyper_log_cache('Sending flat data',3);
		echo $hyper_data['html'];
	} else {
		$buffer = hyper_cache_gzdecode($hyper_data['gz']);
		if ($buffer === false)
			echo 'Error retriving the content';
		else{
			hyper_log_cache('Sending decoded flat data',3);
			echo $buffer;
		}
	}
}
flush();
die();

function hyper_cache_start($delete = true) {
	global $hc_file;

	if ($delete) @ unlink($hc_file);
	hyper_log_cache('hyper_cache_start()' . $delete,3);
	foreach ($_COOKIE as $n => $v) {
		if (substr($n, 0, 14) == 'comment_author') {
			unset ($_COOKIE[$n]);
		}
	}

	ob_start('hyper_cache_callback');
}

// From here Wordpress starts to process the request

// Called whenever the page generation is ended
function hyper_cache_callback($buffer) {
	global $hyper_cache, $hyper_cache_stop, $hyper_redirect, $hc_file, $hyper_cache_name;

	$buffer = apply_filters('hyper_cache_buffer', $buffer);

	if ($hyper_cache_stop)
		return $buffer;

	if (!$hyper_cache['notfound'] && is_404()) {
		return $buffer;
	}

	if (strpos($buffer, '</body>' && !is_feed()) === false)
		return $buffer;

	// WP is sending a redirect
	if ($hyper_redirect) {
		if ($hyper_cache['redirects']) {
			$data['location'] = $hyper_redirect;
			hyper_cache_write($data);
		}
		return $buffer;
	}

	if (is_home() && $hyper_cache['home']) {
		return $buffer;
	}

	if (is_feed() && !$hyper_cache['feed']) {
		return $buffer;
	}

	if (is_home())
		$data['type'] = 'home';
	else
		if (is_feed())
			$data['type'] = 'feed';
		else
			if (is_archive())
				$data['type'] = 'archive';
			else
				if (is_single())
					$data['type'] = 'single';
				else
					if (is_page())
						$data['type'] = 'page';
	$buffer = trim($buffer);

	// Can be a trackback or other things without a body. We do not cache them, WP needs to get those calls.
	if (strlen($buffer) == 0)
		return '';

	if (!$hyper_cache['charset'])
		$hyper_cache['charset'] = 'UTF-8';

	if (is_feed()) {
		$data['mime'] = 'text/xml;charset=' . $hyper_cache['charset'];
	} else {
		$data['mime'] = 'text/html;charset=' . $hyper_cache['charset'];
	}

	$buffer .= "\n<!--\n";
	$buffer .= "Hyper cache file: $hyper_cache_name\n";
	$buffer .= "Cache created: " . date('d-m-Y H:i:s') . "\n";
	$buffer .= ' -->';

	$data['html'] = $buffer;

	if (is_404())
		$data['status'] = 404;

	hyper_cache_write($data);

	return $buffer;
}

function hyper_cache_write(& $data) {
	global $hyper_cache, $hc_file;

	$data['uri'] = $_SERVER['REQUEST_URI'];

	// Look if we need the compressed version
	if ($hyper_cache['store_compressed']) {
		$data['gz'] = gzencode($data['html']);
		if ($data['gz'])
			unset ($data['html']);
	}
	$file = fopen($hc_file, 'w');
	fwrite($file, serialize($data));
	fclose($file);
	hyper_log_cache('Cache writed',2);

	header('Last-Modified: ' . date("r", @ filemtime($hc_file)));
}

function hyper_mobile_type() {
	global $hyper_cache;

	if ($hyper_cache['plugin_mobile_pack']) {
		@ include_once ABSPATH . 'wp-content/plugins/wordpress-mobile-pack/plugins/wpmp_switcher/lite_detection.php';
		if (function_exists('lite_detection')) {
			$is_mobile = lite_detection();
			if (!$is_mobile)
				return '';
			include_once ABSPATH . 'wp-content/plugins/wordpress-mobile-pack/themes/mobile_pack_base/group_detection.php';
			if (function_exists('group_detection')) {
				return 'mobile' . group_detection();
			} else
				return 'mobile';
		}
	}

	if (!isset ($hyper_cache['mobile']) || $hyper_cache['mobile_agents'] === false)
		return '';

	$hyper_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	foreach ($hyper_cache['mobile_agents'] as $hyper_a) {
		if (strpos($hyper_agent, $hyper_a) !== false) {
			if (strpos($hyper_agent, 'iphone') || strpos($hyper_agent, 'ipod')) {
				return 'iphone';
			} else {
				return 'pda';
			}
		}
	}
	return '';
}

function hyper_cache_gzdecode($data) {

	$flags = ord(substr($data, 3, 1));
	$headerlen = 10;
	$extralen = 0;

	$filenamelen = 0;
	if ($flags & 4) {
		$extralen = unpack('v', substr($data, 10, 2));

		$extralen = $extralen[1];
		$headerlen += 2 + $extralen;
	}
	if ($flags & 8) // Filename

		$headerlen = strpos($data, chr(0), $headerlen) + 1;
	if ($flags & 16) // Comment

		$headerlen = strpos($data, chr(0), $headerlen) + 1;
	if ($flags & 2) // CRC at end of file

		$headerlen += 2;
	$unpacked = gzinflate(substr($data, $headerlen));
	return $unpacked;
}

function hyper_log_cache($msg,$level=2) {
	global $hyper_uri,$hc_file;
	/*
	 * Debug Levels
	 * 0 - Critical error
	 * 1 - Warning
	 * 2 - Message
	 * 3 - Debug
	 */
	// return;
	if($_SERVER['REMOTE_ADDR']!='93.152.186.125'){
		// return;
		if($level>2)return;
	}
	$file = fopen(dirname(__FILE__) . '/log-cache.txt', 'a');
	$text = '[' . date('Y.m.d H:i') . ']['.$_SERVER['REMOTE_ADDR']."]\n";
	$text.= "CF: $hc_file\n";
	$text.= "URL: ".urldecode($hyper_uri)."\n";
	$text.= $msg;
	fwrite($file, $text . "\n\n");
	fclose($file);
}
?>