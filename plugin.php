<?php
/*
Plugin Name: Hyper Cache Extended
Plugin URI: http://marto.lazarov.org/plugins/hyper-cache-extended
Description: Hyper Cache Extended is a cache system for WordPress to improve it's perfomances and save resources. Before update <a href="http://wordpress.org/extend/plugins/hyper-cache-extended/" target="_blank">read the version changes</a>. To manually upgrade remeber the sequence: deactivate, update, activate.
Version: 0.9.9
Author: Martin Lazarov
Author URI: http://marto.lazarov.org
Disclaimer: Use at your own risk. No warranty expressed or implied is provided. Hyper Cache Extened is based on Hyper Cache plugin

---
Copyright 2012  mlazarov  (email : lazarov@mail.bg)
---

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

=== Changelog ===
See readme.txt

*/
define('HYPER_CACHE_EXTENDED', '0.9.9');

$hyper_invalidated = false;
$hyper_invalidated_post_id = null;


// On activation, we try to create files and directories. If something goes wrong
// (eg. for wrong permission on file system) the options page will give a
// warning.
register_activation_hook(__FILE__, 'hyper_activate');
function hyper_activate(){
    $options = get_option('hyper');

    if (!is_array($options)) {

        $options = array();
        $options['comment'] = 1;
        $options['archive'] = 1;
        $options['timeout'] = 1440;
        $options['load'] = 5;
        $options['redirects'] = 1;
        $options['notfound'] = 1;
        $options['clean_interval'] = 60;
        $options['enable_clean'] = 1;
        $options['gzip'] = 1;
        $options['store_compressed'] = 1;
        $options['expire_type'] = 'post';
        $options['path'] = dirname(__FILE__).'/cache/';
        update_option('hyper', $options);
    }

    $buffer = hyper_generate_config($options);
    $file = @fopen(ABSPATH . 'wp-content/advanced-cache.php', 'w');
    @fwrite($file, $buffer);
    @fclose($file);

    @mkdir($options['path']);
    @touch($options['path'] . '_test.dat');
	if($options['enable_clean']){
	    wp_schedule_event(time()+60, 'hourly', 'hyper_clean');
	}
	else{
		wp_clear_scheduled_hook('hyper_clean');
	}

}

add_action('hyper_clean', 'hyper_clean');
function hyper_clean(){
	global $hyper_cache;
    // Latest global invalidation (may be false)
    $invalidation_time = @filemtime($hyper_cache['path'] . '/_global.dat');

    hyper_log('start cleaning');

    $options = get_option('hyper');

    $timeout = $options['timeout']*60;
    if ($timeout == 0) return;

    $time = time();

    $handle = @opendir($hyper_cache['path']);
    if (!$handle) {
        hyper_log('unable to open cache dir');
        return;
    }

    while ($file = readdir($handle)) {
        if ($file == '.' || $file == '..' || $file[0] == '_') continue;

        hyper_log('checking ' . $file . ' for cleaning');
        $t = @filemtime($hyper_cache['path'] . $file);
        hyper_log('file time ' . $t);
        if ($time - $t > $timeout || ($invalidation_time && $t < $invalidation_time)) {
            @unlink($hyper_cache['path'] . $file);
            hyper_log('cleaned ' . $file);
        }
    }
    closedir($handle);

    hyper_log('end cleaning');
}

register_deactivation_hook(__FILE__, 'hyper_deactivate');
function hyper_deactivate(){
	wp_clear_scheduled_hook('hyper_clean');
	//@unlink(ABSPATH . 'wp-content/advanced-cache.php');

	// We can safely delete the hyper-cache directory, is not more used at this time.
    //hyper_delete_path(dirname(__FILE__) . '/cache');

    // burn the file without delete it so one can rewrite it
    $file = @fopen(ABSPATH . 'wp-content/advanced-cache.php', 'wb');
    if ($file){
        @fwrite($file, '');
        @fclose($file);
    }
}

$hyper_notice = '';

if (is_admin()){
    if (!is_dir($hyper_cache['path'])){
    	@mkdir($hyper_cache['path']);
    	if (!is_dir($hyper_cache['path'])){
      	  $hyper_notice .= 'Hyper Cache was not able to create the folder "cache" in its installation dir. Create it by hand and make it writable.<br />';
    	}
    }

    if (!is_file(ABSPATH . 'wp-content/advanced-cache.php')){
        $hyper_notice .= 'Your wp-content folder is not writable. Hyper Cache needs to create a file called advanced-cache.php in to that folder in order to work. Make it writable and deactivate and reactivate Hyper Cache.<br />';
    }

    if (!defined('WP_CACHE') || !WP_CACHE){
        $hyper_notice .= 'The WordPress cache system is not enabled! Please, activate it adding the line of code<br />define("WP_CACHE", true);<br /> in the file wp-config.php just after the define("WPLANG", ...).<br />';
    }

    add_action('admin_notices', 'hyper_admin_notices');
    function hyper_admin_notices() {
        global $hyper_notice;
        if ($hyper_notice == '') return;
        echo '<div class="error fade" style="background-color:red;"><p><strong>' . $hyper_notice . '</strong></p></div>';
    }
}


add_filter("plugin_action_links_hyper-cache-extended/plugin.php", 'hyper_plugin_action_links');
function hyper_plugin_action_links($links){
    $settings_link = '<a href="admin.php?page=hyper-cache-extended/options.php">' . __( 'Settings' ) . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

add_action('admin_menu', 'hyper_admin_menu');
function hyper_admin_menu(){
	$hook=add_submenu_page('index.php','Hyper Cache E','Hyper Cache E','manage_options','hyper-cache-extended/options.php');
	add_action('hook-'.$hook,'hyper-cache-extended','options');
	//add_options_page('Hyper Cache', 'Hyper Cache Extended', 'manage_options', 'hyper-cache-extended/options.php');
}

// Completely invalidate the cache. The hyper-cache directory is renamed
// with a random name and re-created to be immediately available to the cache
// system. Then the renamed directory is removed.
// If the cache has been already invalidated, the function doesn't anything.
function hyper_cache_invalidate(){
    global $hyper_invalidated,$hyper_cache;

    hyper_log("hyper_cache_invalidate> Called");

    if ($hyper_invalidated){
        hyper_log("hyper_cache_invalidate> Cache already invalidated");
        return;
    }

    if (!@touch($hyper_cache['path'] . '_global.dat')){
        hyper_log("hyper_cache_invalidate> Unable to touch cache/_global.dat");
    }
    else{
        hyper_log("hyper_cache_invalidate> Touched cache/_global.dat");
    }
    @unlink($hyper_cache['path'] . '_archives.dat');
    $hyper_invalidated = true;

}

/**
 * Invalidates a single post and eventually the home and archives if
 * required.
 */
function hyper_cache_invalidate_post($post_id){
    global $hyper_invalidated_post_id,$hyper_cache;

    hyper_log("hyper_cache_invalidate_post(" . $post_id . ")> Called");

    if ($hyper_invalidated_post_id == $post_id){
        hyper_log("hyper_cache_invalidate_post(" . $post_id . ")> Post was already invalidated");
        return;
    }

    $options = get_option('hyper');

    if ($options['expire_type'] == 'none'){
        hyper_log("hyper_cache_invalidate_post(" . $post_id . ")> Invalidation disabled");
        return;
    }

    if ($options['expire_type'] == 'post'){
        $post = get_post($post_id);

        $link = get_permalink($post_id);
        hyper_log('Permalink to invalidate ' . $link);
        $link = substr($link, 7);
        hyper_log('Corrected permalink to invalidate ' . $link);
        $file = md5($link);
        hyper_log('File basename to invalidate ' . $file);

        $path = $hyper_cache['path'];
        $handle = @opendir($path);
        if ($handle) {
            while ($f = readdir($handle)){
                if (substr($f, 0, 32) == $file){
                    if (unlink($path . '/' . $f)) {
                        hyper_log('Deleted ' . $path . '/' . $f);
                    }
                    else {
                        hyper_log('Unable to delete ' . $path . '/' . $f);
                    }
                }
            }
            closedir($handle);
        }

        $hyper_invalidated_post_id = $post_id;

        hyper_log("hyper_cache_invalidate_post(" . $post_id . ")> Post invalidated");

        if ($options['archive']){

            hyper_log("hyper_cache_invalidate_post(" . $post_id . ")> Archive invalidation required");

            if (!@touch($hyper_cache['path'] . '_archives.dat')){
                hyper_log("hyper_cache_invalidate_post(" . $post_id . ")> Unable to touch cache/_archives.dat");
            }
            else {
                hyper_log("hyper_cache_invalidate_post(" . $post_id . ")> Touched cache/_archives.dat");
            }
        }
        return;
    }

    if ($options['expire_type'] == 'all'){
        hyper_log("hyper_cache_invalidate_post(" . $post_id . ")> Full invalidation");
        hyper_cache_invalidate();
        return;
    }
}


// Completely remove a directory and it's content.
function hyper_delete_path($path){
    if ($path == null) return;
    $handle = @opendir($path);
    if ($handle){
        while ($file = readdir($handle)){
            if ($file != '.' && $file != '..'){
                @unlink($path . '/' . $file);
            }
        }
        closedir($handle);
    //@rmdir($path);
    }
}

// Counts the number of file in to the hyper cache directory to give an idea of
// the number of pages cached.
function hyper_count(){
	global $hyper_cache;
    $count = 0;
    //if (!is_dir(ABSPATH . 'wp-content/hyper-cache')) return 0;
    if ($handle = @opendir($hyper_cache['path'])){
        while ($file = readdir($handle)){
            if ($file != '.' && $file != '..'){
                $count++;
            }
        }
        closedir($handle);
    }
    return $count;
}

add_action('switch_theme', 'hyper_cache_invalidate', 0);
add_action('edit_post', 'hyper_cache_invalidate_post', 0);
add_action('publish_post', 'hyper_cache_invalidate_post', 0);
add_action('delete_post', 'hyper_cache_invalidate_post', 0);


// Capture and register if a redirect is sent back from WP, so the cache
// can cache (or ignore) it. Redirects were source of problems for blogs
// with more than one host name (eg. domain.com and www.domain.com) comined
// with the use of Hyper Cache Extended.
add_filter('redirect_canonical', 'hyper_redirect_canonical', 10, 2);
$hyper_redirect = null;
function hyper_redirect_canonical($redirect_url, $requested_url){
    global $hyper_redirect;

    $hyper_redirect = $redirect_url;

    return $redirect_url;
}

function hyper_log($text){
	// comment this return; to turn on loging
	return;
	$file = fopen(dirname(__FILE__) . '/log.txt', 'a');
	fwrite($file, $text . "\n");
	fclose($file);
}

function hyper_generate_config(&$options){
	global $hyper_cache;
    $buffer = '';

    $timeout = $options['timeout']*60;
    if ($timeout == 0) $timeout = 2000000000;

    $buffer = "<?php\n";
    $buffer .= '$hyper_cache[\'path\'] = "' . (isset($hyper_cache['path'])?addslashes($hyper_cache['path']):addslashes(dirname(__FILE__).'/cache/')). "\";\n";
    $buffer .= '$hyper_cache[\'charset\']= "' . get_option('blog_charset') . '"' . ";\n";
    // Collect statistics
    //$buffer .= '$hyper_cache_stats = ' . (isset($options['stats'])?'true':'false') . ";\n";
    // Do not cache for commenters
    $buffer .= '$hyper_cache[\'comment\'] = ' . (isset($options['comment'])?'true':'false') . ";\n";
    // Ivalidate archives on post invalidation
    $buffer .= '$hyper_cache[\'archive\'] = ' . ($options['archive']?'true':'false') . ";\n";
    // Single page timeout
    $buffer .= '$hyper_cache[\'timeout\'] = ' . ($timeout) . ";\n";
    // Server Load
    $buffer .= '$hyper_cache[\'load\'] = ' . (int)($options['load']?$options['load']:5) . ";\n";
    // Cache redirects?
    $buffer .= '$hyper_cache[\'redirects\'] = ' . (isset($options['redirects'])?'true':'false') . ";\n";
    // Cache page not found?
    $buffer .= '$hyper_cache[\'notfound\'] = ' . (isset($options['notfound'])?'true':'false') . ";\n";
    // Separate caching for mobile agents?
    $buffer .= '$hyper_cache[\'mobile\'] = ' . (isset($options['mobile'])?'true':'false') . ";\n";
    // WordPress mobile pack integration?
    $buffer .= '$hyper_cache[\'plugin_mobile_pack\'] = ' . (isset($options['plugin_mobile_pack'])?'true':'false') . ";\n";
    // Cache the feeds?
    $buffer .= '$hyper_cache[\'feed\'] = ' . (isset($options['feed'])?'true':'false') . ";\n";
    // Cache GET request with parameters?
    $buffer .= '$hyper_cache[\'cache_qs\'] = ' . (isset($options['cache_qs'])?'true':'false') . ";\n";
    // Strip query string?
    $buffer .= '$hyper_cache[\'strip_qs\'] = ' . (isset($options['strip_qs'])?'true':'false') . ";\n";
    // DO NOT cache the home?
    $buffer .= '$hyper_cache[\'home\'] = ' . (isset($options['home'])?'true':'false') . ";\n";
    // Disable last modified header
    $buffer .= '$hyper_cache[\'lastmodified\'] = ' . (isset($options['lastmodified'])?'true':'false') . ";\n";

    if ($options['gzip']) $options['store_compressed'] = 1;

    $buffer .= '$hyper_cache[\'gzip\'] = ' . (isset($options['gzip'])?'true':'false') . ";\n";
    $buffer .= '$hyper_cache[\'store_compressed\'] = ' . (isset($options['store_compressed'])?'true':'false') . ";\n";

    //$buffer .= '$hyper_cache_clean_interval = ' . ($options['clean_interval']*60) . ";\n";

    if (isset($options['reject']) && trim($options['reject']) != ''){
        $options['reject'] = str_replace(' ', "\n", $options['reject']);
        $options['reject'] = str_replace("\r", "\n", $options['reject']);
        $buffer .= '$hyper_cache_reject = array(';
        $reject = explode("\n", $options['reject']);
        $options['reject'] = '';
        foreach ($reject as $uri){
            $uri = trim($uri);
            if ($uri == '') continue;
            $buffer .= "\"" . addslashes(trim($uri)) . "\",";
            $options['reject'] .= $uri . "\n";
        }
        $buffer = rtrim($buffer, ',');
        $buffer .= ");\n";
    }
    else {
        $buffer .= '$hyper_cache_reject = false;' . "\n";
    }

    if (isset($options['reject_agents']) && trim($options['reject_agents']) != ''){
        $options['reject_agents'] = str_replace(' ', "\n", $options['reject_agents']);
        $options['reject_agents'] = str_replace("\r", "\n", $options['reject_agents']);
        $buffer .= '$hyper_cache[\'reject_agents\'] = array(';
        $reject_agents = explode("\n", $options['reject_agents']);
        $options['reject_agents'] = '';
        foreach ($reject_agents as $uri){
            $uri = trim($uri);
            if ($uri == '') continue;
            $buffer .= "\"" . addslashes(strtolower(trim($uri))) . "\",";
            $options['reject_agents'] .= $uri . "\n";
        }
        $buffer = rtrim($buffer, ',');
        $buffer .= ");\n";
    }
    else {
        $buffer .= '$hyper_cache[\'reject_agents\'] = false;' . "\n";
    }

    if (isset($options['reject_cookies']) && trim($options['reject_cookies']) != ''){
        $options['reject_cookies'] = str_replace(' ', "\n", $options['reject_cookies']);
        $options['reject_cookies'] = str_replace("\r", "\n", $options['reject_cookies']);
        $buffer .= '$hyper_cache[\'reject_cookies\'] = array(';
        $reject_cookies = explode("\n", $options['reject_cookies']);
        $options['reject_cookies'] = '';
        foreach ($reject_cookies as $c){
            $c = trim($c);
            if ($c == '') continue;
            $buffer .= "\"" . addslashes(strtolower(trim($c))) . "\",";
            $options['reject_cookies'] .= $c . "\n";
        }
        $buffer = rtrim($buffer, ',');
        $buffer .= ");\n";
    }
    else {
        $buffer .= '$hyper_cache[\'reject_cookies\'] = false;' . "\n";
    }

    if (isset($options['mobile'])){
        if (!isset($options['mobile_agents']) || trim($options['mobile_agents']) == ''){
            $options['mobile_agents'] = "elaine/3.0\niphone\nipod\npalm\neudoraweb\nblazer\navantgo\nwindows ce\ncellphone\nsmall\nmmef20\ndanger\nhiptop\nproxinet\nnewt\npalmos\nnetfront\nsharp-tq-gx10\nsonyericsson\nsymbianos\nup.browser\nup.link\nts21i-10\nmot-v\nportalmmm\ndocomo\nopera mini\npalm\nhandspring\nnokia\nkyocera\nsamsung\nmotorola\nmot\nsmartphone\nblackberry\nwap\nplaystation portable\nlg\nmmp\nopwv\nsymbian\nepoc";
        }

        if (trim($options['mobile_agents']) != ''){
            $options['mobile_agents'] = str_replace(',', "\n", $options['mobile_agents']);
            $options['mobile_agents'] = str_replace("\r", "\n", $options['mobile_agents']);
            $buffer .= '$hyper_cache[\'mobile_agents\'] = array(';
            $mobile_agents = explode("\n", $options['mobile_agents']);
            $options['mobile_agents'] = '';
            foreach ($mobile_agents as $uri){
                $uri = trim($uri);
                if ($uri == '') continue;
                $buffer .= "\"" . addslashes(strtolower(trim($uri))) . "\",";
                $options['mobile_agents'] .= $uri . "\n";
            }
            $buffer = rtrim($buffer, ',');
            $buffer .= ");\n";
        }
        else {
            $buffer .= '$hyper_cache[\'mobile_agents\'] = false;' . "\n";
        }
    }

    $buffer .= "include(ABSPATH . 'wp-content/plugins/hyper-cache-extended/cache.php');\n";
    $buffer .= '?>';

    return $buffer;
}
?>
