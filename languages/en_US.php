<?php

//$hyper_labels['wp_cache_not_enabled'] = "The wordPress cache system is not enabled. Please, activate it adding the line of code below in the file wp-config.php. Thank you!";
//$hyper_labels['configuration'] = "Configuration";
//$hyper_labels['activate'] = "Activate the cache?";
$hyper_labels['timeout'] = "Expire a cached page after";
$hyper_labels['timeout_desc'] = "minutes (set to zero to never expire)";
$hyper_labels['count'] = "Total cached pages (cached redirect is counted too)";
$hyper_labels['save'] = "Save";
//$hyper_labels['store'] = "Store pages as";
//$hyper_labels['folder'] = "Cache folder";
$hyper_labels['gzip'] = "Gzip compression";
$hyper_labels['gzip_desc'] = "Send gzip compressed pages to enabled browsers";
$hyper_labels['clear'] = "Clear the cache";
$hyper_labels['compress_html'] = "Optimize HTML";
$hyper_labels['compress_html_desc'] = "Try to optimize the HTML removing unuseful spaces. Do not use if you are using &lt;pre&gt; tags in the posts";
$hyper_labels['redirects'] = "Cache the WP redirects";
$hyper_labels['redirects_desc'] = "Can give problems with some configuration. Try and hope.";
$hyper_labels['mobile'] = "Detetect and cache for mobile devices";
$hyper_labels['clean_interval'] = "Autoclean every";
$hyper_labels['clean_interval_desc'] = "minutes (set to zero to disable)";
//$hyper_labels['not_activated'] = "Hyper Cache is NOT correctly installed: some files or directories have not been created. Check if the wp-content directory is writable and remove any advanced-cache.php file into it. Deactivate and reactivate the plugin.";
$hyper_labels['expire_type'] = "What cached pages to delete on events";
$hyper_labels['expire_type_desc'] = "<b>none</b>: the cache never delete the cached page on events (comments, new posts, and so on)<br />";
$hyper_labels['expire_type_desc'] .= "<b>single pages</b>: the cached pages relative to the post modified (by the editor or when a comment is added) plus the home page. New published posts invalidate all the cache.<br />";
$hyper_labels['expire_type_desc'] .= "<b>single pages strictly</b>: as 'single pages' but without to invalidate all the cache on new posts publishing.<br />";
$hyper_labels['expire_type_desc'] .= "<b>all</b>: all the cached pages (the blog is always up to date)<br />";
$hyper_labels['expire_type_desc'] .= "Beware: when you use 'single pages strictly', a new post will appear on home page, but not on category and tag pages. If you use the 'last posts' widget/feature on sidebar it won't show updated.";
$hyper_labels['advanced_options'] = "Advanced options";

$hyper_labels['reject'] = "URI to reject";
$hyper_labels['reject_desc'] = "One per line. When a URI (eg. /video/my-new-performance) starts with one of the listed lines, it won't be cached.";

$hyper_labels['home'] = "Do not cache the home";
$hyper_labels['home_desc'] = "Enabling this option, the home page and the subsequent pages for older posts will not be cached.";

$hyper_labels['feed'] = "Cache the feed?";
$hyper_labels['feed_desc'] = "Usually not, so we are sure to feed always an updated feed even if we do a strong cache of the web pages";

// New from version 2.2.4
$hyper_labels['urls_analysis'] = "URLs with query string";
$hyper_labels['urls_analysis_desc'] = "URLs with parameters are URLs like www.satollo.com?param=value.";
$hyper_labels['urls_analysis_desc'] .= "Hyper Cache creates cache page names using the full URL, with its parameters.";
$hyper_labels['urls_analysis_desc'] .= "When using permalinks, URLs parameters are ignored by WordPress so calling tha same post URL with fake parameters creates many identical cache entries, with disk space waste.";
$hyper_labels['urls_analysis_desc'] .= "There is an exception: the 's' parameter is used by WordPress to actite the internal search engine.";
$hyper_labels['urls_analysis_desc'] .= "So if you disable the URLs with parameter caching, the search results won't be cached.";
$hyper_labels['urls_analysis_desc'] .= "Other plugins can use parameters, caching of those URLs can rise up problem or be a performance improvement.";
$hyper_labels['urls_analysis_desc'] .= "I cannot give you a final solution... BUT if you have the permalink disabled the cache will work only with this option enabled.";

$hyper_labels['urls_analysis_default'] = "Do NOT cache URLs with parameters";
$hyper_labels['urls_analysis_full'] = "Cache all URLs";
// To be implemented
//$hyper_labels['urls_analysis_removeqs'] = "Remove query string and redirect";

$hyper_labels['storage'] = "Storage";
$hyper_labels['storage_nogzencode_desc'] = "You have not the zlib extension installed, leave the default option!";

$hyper_labels['gzip_nogzencode_desc'] = "There is not 'gzencode' function, may be you PHP has not the zlib extension active.";

// New from version 2.2.5
$hyper_labels['reject_agents'] = "User agents to reject";
$hyper_labels['reject_agents_desc'] = "A 'one per line' list of user agents that, when matched, makes to skip the caching process.";
$hyper_labels['mobile_agents'] = "Mobile user agents";
$hyper_labels['mobile_agents_desc'] = "A 'one per line' list of user agents to identify mobile devices.";
$hyper_labels['_notranslation'] = "Do not use translations for this configuration panel";


$hyper_labels['cron_key'] = "Cron action key";
$hyper_labels['cron_key_desc'] = "A complicate to guess key to start actions from cron calls (no single or double quotes, no spaces)";

?>
