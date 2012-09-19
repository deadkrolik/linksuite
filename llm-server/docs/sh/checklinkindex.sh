#!/bin/sh
cd /home/path_to_directory/cron
echo "" > ./checklinkindex.log
/usr/local/bin/php ./checklinkindex.php > ./checklinkindex.log