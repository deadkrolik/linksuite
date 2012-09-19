#!/bin/sh
cd /home/path_to_directory/cron
echo "" > ./siteindexer.log
/usr/local/bin/php ./siteindexer.php > ./siteindexer.log