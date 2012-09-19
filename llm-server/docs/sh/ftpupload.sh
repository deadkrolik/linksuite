#!/bin/sh
cd /home/path_to_directory/cron
echo "" > ./ftpupload.log
/usr/local/bin/php ./ftpupload.php > ./ftpupload.log