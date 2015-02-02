#!/bin/bash

sed -r 's/(ENGINE|DEFAULT.CHARSET|AUTO_INCREMENT)=\w+//gm' < newinstall.sql |
  sed -r 's/(PRIMARY KEY \(.+\)),/\1/m' |
  sed -r 's/(KEY `enabled` \(`enabled`\))/\/* \1 *\//m' | # remove from featurecodes
  sed -r 's/(KEY `time` \(`time`,`level`\))/\/* \1 *\//m' | # remove from freepbx_log
  sed -r 's/AUTO_INCREMENT/AUTOINCREMENT/m' |
  sed -r 's/int\(11\) NOT NULL AUTOINCREMENT/INTEGER/m' |
  sed -r 's/`level` enum\(.+\)/`level` CHAR(20)/m' |
  grep -v LOCK\ TAB > newinstall.sqlite
  
# We should probbaly put the sqlite file into its 
# own directory. Like /etc/freepbx.
sqlite3 /var/www/freepbx.db < newinstall.sqlite
# sqlite needs to create files _in the same directory_ as the .db file
# so that dir needs to have write perms to the web user.
chmod 777 /var/www/freepbx.db
chmod 777 /var/www
