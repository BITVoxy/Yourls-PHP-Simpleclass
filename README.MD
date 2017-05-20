Yourls-PHP-Simpleclass
=========================

This class is designed to shorten links in a minimum number of commands for [YOURLS](https://yourls.org).

# Usage for unique link
```
<?php
require_once('yourls.class.php');
$yourls = new yourls("http://YOUR_YOURLS_URI/yourls-api.php", "signature");
$yourls->link("http://www.google.com/"); 
echo $yourls->link;
?>
```

# Usage for multiple links:
```
<?php
require_once('yourls.class.php');
$yourls = new yourls("http://YOUR_YOURLS_URI/yourls-api.php", "signature");
$yourls->content("Hello World! http://www.google.com/ http://www.yahoo.com/"); 
echo $yourls->content;
?>
```

# Reference links
[YOURLS Admin Tools - API Signature - https://YOUR_DOMAIN/admin/tools.php](https://YOUR_DOMAIN/admin/tools.php)
[YOURLS API Guide](https://yourls.org/readme.html#API)
