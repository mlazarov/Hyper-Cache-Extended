<?php

$hyper_labels['wp_cache_not_enabled'] = "WordPress缓存系统尚未启动。请在wp-config.php中添加以下一行代码来激活它。谢谢";
$hyper_labels['configuration'] = "恭喜";
$hyper_labels['activate'] = "是否启用缓存？勾选之";
$hyper_labels['timeout'] = "缓存文件在多少分钟后过期";
$hyper_labels['timeout_desc'] = "分钟 (设置为0的话则代表永不过期)";
$hyper_labels['count'] = "总共缓存页数 (重定向缓存也算在内)";
$hyper_labels['save'] = "保存";
//$hyper_labels['store'] = "Store pages as";
//$hyper_labels['folder'] = "Cache folder";
$hyper_labels['gzip'] = "Gzip 压缩";
$hyper_labels['gzip_desc'] = "发送gzip压缩去激活浏览器";
$hyper_labels['clear'] = "清除缓存";
$hyper_labels['compress_html'] = "优化HTML";
$hyper_labels['compress_html_desc'] = "通过取出无用的空格去最优化HTML。如果文章内含有&lt;pre&gt;标签就不要使用该选项";
$hyper_labels['redirects'] = "缓存WordPress的重定向页面";
$hyper_labels['redirects_desc'] = "可能导致一些配置问题，试试看，希望没有问题";
$hyper_labels['mobile'] = "检测和为手机设备缓存";
$hyper_labels['clean_interval'] = "每多少分钟后自动清除";
$hyper_labels['clean_interval_desc'] = "分钟 (设置为0的话则代表永不清除)";
$hyper_labels['not_activated'] = "Hyper Cache（极限缓存） 没有被正确安装：一些文件和目录没有成功创建。检查看wp-content目录是可写且可删除其内的advanced-cache.php。停用并重启用该插件。";
$hyper_labels['expire_type'] = "什么事件下缓存页面会被删除";
$hyper_labels['expire_type_desc'] = "<b>None</b>: 任何事件下（评论，新文章等），缓存都不会被删除<br />";
$hyper_labels['expire_type_desc'] .= "<b>Single pages</b>: 文章被修改（包括添加了评论），首页和相关缓存被删除。新发表文章时候会清空所有的缓存。<br />";
$hyper_labels['expire_type_desc'] .= "<b>Single pages strictly</b>: 类似单个页面，但是添加新文章时不会清空所有的缓存<br />";
$hyper_labels['expire_type_desc'] .= "<b>All</b>: 所有的缓存页面（博客总是最新的）<br />";
$hyper_labels['expire_type_desc'] .= "注意：当你选中'Single pages strictly'，一个新的文章会出现在首页，但不会出现在分类和标签页里面。如果你在边栏使用了‘新近文章’插件或特色，它不会显示更新的。";
$hyper_labels['advanced_options'] = "高级选项";
$hyper_labels['reject'] = "拒绝的地址";
$hyper_labels['reject_desc'] = "一个一行。当一个地址（如/video/my-new-performance）按照以下地址开始的话，它不会被缓存。";

$hyper_labels['home'] = "不缓存首页";
$hyper_labels['home_desc'] = "打开这个选项，首页和其下的旧文章页面不会被缓存。";

$hyper_labels['feed'] = "是否缓存feed？";
$hyper_labels['feed_desc'] = "一般不，这样我们能保证feed总是最新的，甚至我们在网站上使用了强力的缓存。";

?>
