Yourls-PHP-Simpleclass
=========================

This class is designed to shorten links in a minimum number of commands for (YOURLS)[https://yourls.org].


# Usage for unique link
```
<?php
require_once('class.yourls.php');
$yourls = new yourls("http://YOUR_YOURLS_URI/yourls-api.php", "login", "password");
$yourls->link("http://www.google.com/"); 
echo $yourls->link;
?>
```

# Usage for multiple links:
```
<?php
require_once('class.yourls.php');
$yourls = new yourls("http://YOUR_YOURLS_URI/yourls-api.php", "login", "password");
$yourls->content("Hello World! http://www.google.com/ http://www.yahoo.com/"); 
echo $yourls->content;
?>
```
