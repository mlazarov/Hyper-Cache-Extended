<?php

$options = get_option('hyper');

if (!$options['notranslation'])
{
    $plugin_dir = basename(dirname(__FILE__));
    load_plugin_textdomain('hyper-cache', 'wp-content/plugins/' . $plugin_dir, $plugin_dir);
}


if (isset($_POST['clean']))
{
    hyper_delete_path($hyper_cache['path']);
}

$error = false;
if (isset($_POST['save'])) 
{
    if (!check_admin_referer()) die('No hacking please');
    
    $tmp = stripslashes_deep($_POST['options']);

    if ($options['gzip'] != $tmp['gzip'])
    {
        hyper_delete_path($hyper_cache['path']);
    }

    $options = $tmp;
    
    if (!is_numeric($options['timeout'])) $options['timeout'] = 60;
    $options['timeout'] = (int)$options['timeout'];

    if (!is_numeric($options['clean_interval'])) $options['clean_interval'] = 60;
    $options['clean_interval'] = (int)$options['clean_interval'];

    $buffer = hyper_generate_config($options);
    
    $file = @fopen(ABSPATH . 'wp-content/advanced-cache.php', 'w');
    if ($file) {
    @fwrite($file, $buffer);
    @fclose($file);
    }
    else {
        $error = true;
    }
    update_option('hyper', $options);

    // When the cache does not expire
    if ($options['expire_type'] == 'none')
    {
        @unlink($hyper_cache['path'] . '/_global.dat');
        @unlink($hyper_cache['path'] . '/_archives.dat');
    }
} 
else 
{
    if ($options['mobile_agents'] == '')
    {
        $options['mobile_agents'] = "elaine/3.0\niphone\nipod\npalm\neudoraweb\nblazer\navantgo\nwindows ce\ncellphone\nsmall\nmmef20\ndanger\nhiptop\nproxinet\nnewt\npalmos\nnetfront\nsharp-tq-gx10\nsonyericsson\nsymbianos\nup.browser\nup.link\nts21i-10\nmot-v\nportalmmm\ndocomo\nopera mini\npalm\nhandspring\nnokia\nkyocera\nsamsung\nmotorola\nmot\nsmartphone\nblackberry\nwap\nplaystation portable\nlg\nmmp\nopwv\nsymbian\nepoc";
    }
}


?>
<div class="wrap">

<h2>Hyper Cache Extended <small><?php echo HYPER_CACHE_EXTENDED; ?></small></h2>
<?php
    if ($error)
    {
        echo '<p><strong>Options saved BUT not active because Hyper Cache was not able to update the file wp-content/advanced-cache.php (is it writable?).</strong></p>';
    }
?>
<?php
    if (!@touch($hyper_cache['path'] . '/_test.dat'))
    {
        echo '<p><strong>Hyper Cache was not able to create files in the folder "cache" in its installation dir. Make it writable (eg. chmod 777).</strong></p>';
    }
?>
<!--iframe width="100%" height="120" src="http://www.satollo.net/services/hyper-cache" style="border: 1px solid #ccc"></iframe//-->

<!--p>
    <?php printf(__('You can find more details about configurations and working mode
    on <a href="%s">Hyper Cache official page</a>.', 'hyper-cache'),
    'http://www.satollo.net/plugins/hyper-cache'); ?>
</p//-->

<form method="post" action="">
<?php wp_nonce_field(); ?>

<h3><?php _e('Cache status', 'hyper-cache'); ?></h3>
<table class="form-table">
<tr valign="top">
    <th><?php _e('Files in cache (valid and expired)', 'hyper-cache'); ?></th>
    <td><?php echo hyper_count(); ?></td>
</tr>
<tr valign="top">
    <th>Cleaning process</th>
    <td>Next run on: <?php echo gmdate(get_option('date_format') . ' ' . get_option('time_format'), wp_next_scheduled('hyper_clean') + get_option('gmt_offset')*3600); ?></td>
</tr>
</table>
<p class="submit">
    <input class="button" type="submit" name="clean" value="<?php _e('Clear cache', 'hyper-cache'); ?>">
</p>
</form>

<h3><?php _e('Configuration'); ?></h3>

<form method="post" action="">
<table class="form-table">

<tr valign="top">
    <th><?php _e('Cached pages timeout', 'hyper-cache'); ?></th>
    <td>
        <input type="text" size="5" name="options[timeout]" value="<?php echo htmlspecialchars($options['timeout']); ?>"/>
        (<?php _e('minutes', 'hyper-cache'); ?>)
        <br />
        <?php _e('Minutes a cached page is valid and served to users. A zero value means a cached page is
        valid forever.', 'hyper-cache'); ?>
        <?php _e('If a cached page is older than specified value (expired) it is no more used and
        will be regenerated on next request of it.', 'hyper-cache'); ?>
        <?php _e('720 minutes is half a day, 1440 is a full day and so on.', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Cache invalidation mode', 'hyper-cache'); ?></th>
    <td>
        <select name="options[expire_type]">
            <option value="all" <?php echo ($options['expire_type'] == 'all')?'selected':''; ?>><?php _e('All cached pages', 'hyper-cache'); ?></option>
            <option value="post" <?php echo ($options['expire_type'] == 'post')?'selected':''; ?>><?php _e('Only modified posts', 'hyper-cache'); ?></option>
            <!--<option value="post_strictly" <?php echo ($options['expire_type'] == 'post_strictly')?'selected':''; ?>><?php _e('Only modified pages', 'hyper-cache'); ?></option>-->
            <option value="none" <?php echo ($options['expire_type'] == 'none')?'selected':''; ?>><?php _e('Nothing', 'hyper-cache'); ?></option>
        </select>
        <br />
        <input type="checkbox" name="options[archive]" value="1" <?php echo $options['archive']?'checked':''; ?>/>
        <?php _e('Invalidate home, archives, categories on single post invalidation', 'hyper-cache'); ?>
        <br />
        <br />
        <?php _e('"Invalidation" is the process of deleting cached pages when they are no more valid.', 'hyper-cache'); ?>
        <?php _e('Invalidation process is started when blog contents are modified (new post, post update, new comment,...) so
        one or more cached pages need to be refreshed to get that new content.', 'hyper-cache'); ?>
        <?php _e('A new comment submission or a comment moderation is considered like a post modification
        where the post is the one the comment is relative to.', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Disable cache for commenters', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[comment]" value="1" <?php echo $options['comment']?'checked':''; ?>/>
        <br />
        <?php _e('When users leave comments, WordPress show pages with their comments even if in moderation
        (and not visible to others) and pre-fills the comment form.', 'hyper-cache'); ?>
        <?php _e('If you want to keep those features, enable this option.', 'hyper-cache'); ?>
        <?php _e('The caching system will be less efficient but the blog more usable.'); ?>

    </td>
</tr>

<tr valign="top">
    <th><?php _e('Feeds caching', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[feed]" value="1" <?php echo $options['feed']?'checked':''; ?>/>
        <br />
        <?php _e('When enabled the blog feeds will be cache as well.', 'hyper-cache'); ?>
        <?php _e('Usually this options has to be left unchecked but if your blog is rather static,
        you can enable it and have a bit more efficiency', 'hyper-cache'); ?>
    </td>    
</tr>
</table>
<p class="submit">
    <input class="button" type="submit" name="save" value="<?php _e('Update'); ?>">
</p>

<h3><?php _e('Configuration for mobile devices', 'hyper-cache'); ?></h3>
<table class="form-table">
<tr valign="top">
    <th>WordPress Mobile Pack</th>
    <td>
        <input type="checkbox" name="options[plugin_mobile_pack]" value="1" <?php echo $options['plugin_mobile_pack']?'checked':''; ?>/>
        <br />
        Enbale integration with <a href="http://wordpress.org/extend/plugins/wordpress-mobile-pack/">WordPress Mobile Pack</a> plugin. If you have that plugin, Hyper Cache use it to detect mobile devices and caches saparately
        the different pages generated.
    </td>
</tr>
<tr valign="top">
    <th><?php _e('Detect mobile devices', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[mobile]" value="1" <?php echo $options['mobile']?'checked':''; ?>/>
        <br />
        <?php _e('When enabled mobile devices will be detected and the cached page stored under different name.', 'hyper-cache'); ?>
        <?php _e('This makes blogs with different themes for mobile devices to work correctly.', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Mobile agent list', 'hyper-cache'); ?></th>
    <td>
        <textarea wrap="off" rows="4" cols="70" name="options[mobile_agents]"><?php echo htmlspecialchars($options['mobile_agents']); ?></textarea>
        <br />
        <?php _e('One per line mobile agents to check for when a page is requested.', 'hyper-cache'); ?>
        <?php _e('The mobile agent string is matched against the agent a device is sending to the server.', 'hyper-cache'); ?>
    </td>
</tr>
</table>
<p class="submit">
    <input class="button" type="submit" name="save" value="<?php _e('Update'); ?>">
</p>


<h3><?php _e('Compression', 'hyper-cache'); ?></h3>

<?php if (!function_exists('gzencode') || !function_exists('gzinflate')) { ?>

<p><?php _e('Your hosting space has not the "gzencode" or "gzinflate" function, so no compression options are available.', 'hyper-cache'); ?></p>

<?php } else { ?>

<table class="form-table">
<tr valign="top">
    <th><?php _e('Enable compression', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[gzip]" value="1" <?php echo $options['gzip']?'checked':''; ?> />
        <br />
        <?php _e('When possible the page will be sent compressed to save bandwidth.', 'hyper-cache'); ?>
        <?php _e('Only the textual part of a page can be compressed, not images, so a photo
        blog will consume a lot of bandwidth even with compression enabled.', 'hyper-cache'); ?>
        <?php _e('Leave the options disabled if you note malfunctions, like blank pages.', 'hyper-cache'); ?>
        <br />
        <?php _e('If you enable this option, the option below will be enabled as well.', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Disk space usage', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[store_compressed]" value="1" <?php echo $options['store_compressed']?'checked':''; ?> />
        <br />
        <?php _e('Enable this option to minimize disk space usage.', 'hyper-cache'); ?>
        <?php _e('The cache will be a little less performant.', 'hyper-cache'); ?>
        <?php _e('Leave the options disabled if you note malfunctions, like blank pages.', 'hyper-cache'); ?>
    </td>
</tr>
</table>
<p class="submit">
    <input class="button" type="submit" name="save" value="<?php _e('Update'); ?>">
</p>
<?php } ?>


<h3><?php _e('Advanced options', 'hyper-cache'); ?></h3>

<table class="form-table">
<tr valign="top">
    <th><?php _e('Translation', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[notranslation]" value="1" <?php echo $options['notranslation']?'checked':''; ?>/>
        <br />
        <?php _e('DO NOT show this panel translated.', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Disable Last-Modified header', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[lastmodified]" value="1" <?php echo $options['lastmodified']?'checked':''; ?>/>
        <br />
        <?php _e('Diasable some HTTP headers (Last-Modified) which improve performances but some one is reporting they create problems which some hosting configurations.','hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Home caching', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[home]" value="1" <?php echo $options['home']?'checked':''; ?>/>
        <br />
        <?php _e('DO NOT cache the home page so it is always fresh.','hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Redirect caching', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[redirects]" value="1" <?php echo $options['redirects']?'checked':''; ?>/>
        <br />
        <?php _e('Cache WordPress redirects.', 'hyper-cache'); ?>
        <?php _e('WordPress sometime sends back redirects that can be cached to avoid further processing time.', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Page not found caching (HTTP 404)', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[notfound]" value="1" <?php echo $options['notfound']?'checked':''; ?>/>
    </td>
</tr>

<tr valign="top">
    <th>Strip query string</th>
    <td>
        <input type="checkbox" name="options[strip_qs]" value="1" <?php echo $options['strip_qs']?'checked':''; ?>/>
        <br />
        This is a really special case, usually you have to kept it disabled. When enabled, URL with query string will be
        reduced removing the query string. So the URL http://www.domain.com/post-title and
        http://www.domain.com/post-title?a=b&amp;c=d are cached as a single page.<br />
        Setting this option disable the next one.
        <br />
        <strong>Many plugins can stop to work correctly with this option enabled
        (eg. my <a href="http://www.satollo.net/plugins/newsletter">Newsletter plugin</a>)</strong>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('URL with parameters', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[cache_qs]" value="1" <?php echo $options['cache_qs']?'checked':''; ?>/>
        <br />
        <?php _e('Cache requests with query string (parameters).', 'hyper-cache'); ?>
        <?php _e('This option has to be enabled for blogs which have post URLs with a question mark on them.', 'hyper-cache'); ?>
        <?php _e('This option is disabled by default because there is plugins which use
        URL parameter to perform specific action that cannot be cached', 'hyper-cache'); ?>
        <?php _e('For who is using search engines friendly permalink format is safe to
        leave this option disabled, no performances will be lost.', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('URI to reject', 'hyper-cache'); ?></th>
    <td>
        <textarea wrap="off" rows="5" cols="70" name="options[reject]"><?php echo htmlspecialchars($options['reject']); ?></textarea>
        <br />
        <?php _e('Write one URI per line, each URI has to start with a slash.', 'hyper-cache'); ?>
        <?php _e('A specified URI will match the requested URI if the latter starts with the former.', 'hyper-cache'); ?>
        <?php _e('If you want to specify a stric matching, surround the URI with double quotes.', 'hyper-cache'); ?>

        <?php
        $languages = get_option('gltr_preferred_languages');
        if (is_array($languages))
        {
            echo '<br />';
            $home = get_option('home');
            $x = strpos($home, '/', 8); // skips http://
            $base = '';
            if ($x !== false) $base = substr($home, $x);
            echo 'It seems you have Global Translator installed. The URI prefixes below can be added to avoid double caching of translated pages:<br />';
            foreach($languages as $l) echo $base . '/' . $l . '/ ';
        }
        ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Agents to reject', 'hyper-cache'); ?></th>
    <td>
        <textarea wrap="off" rows="5" cols="70" name="options[reject_agents]"><?php echo htmlspecialchars($options['reject_agents']); ?></textarea>
        <br />
        <?php _e('Write one agent per line.', 'hyper-cache'); ?>
        <?php _e('A specified agent will match the client agent if the latter contains the former. The matching is case insensitive.', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Cookies matching', 'hyper-cache'); ?></th>
    <td>
        <textarea wrap="off" rows="5" cols="70" name="options[reject_cookies]"><?php echo htmlspecialchars($options['reject_cookies']); ?></textarea>
        <br />
        <?php _e('Write one cookie name per line.', 'hyper-cache'); ?>
        <?php _e('When a specified cookie will match one of the cookie names sent bby the client the cache stops.', 'hyper-cache'); ?>
        <?php if (defined('FBC_APP_KEY_OPTION')) { ?>
        <br />
        <?php _e('It seems you have Facebook Connect plugin installed. Add this cookie name to make it works
        with Hyper Cache:', 'hyper-cache'); ?>
        <br />
        <strong><?php echo get_option(FBC_APP_KEY_OPTION); ?>_user</strong>
        <?php } ?>

    </td>
</tr>

</table>

<p class="submit">
    <input class="button" type="submit" name="save" value="<?php _e('Update'); ?>">
</p>
</form>
</div>
