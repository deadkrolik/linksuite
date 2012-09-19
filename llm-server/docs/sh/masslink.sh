#!/bin/sh
cd /home/path_to_directory/cron
echo "" > ./masslink.log
/usr/local/bin/php ./masslink.php > ./masslink.log