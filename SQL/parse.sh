#!/bin/bash

sed -r 's/(ENGINE|DEFAULT.CHARSET|AUTO_INCREMENT)=\w+//gm' < newinstall.sql |
  sed -r 's/(PRIMARY KEY \(.+\)),/\1/m' |
  sed -r 's/(KEY `enabled` \(`enabled`\))/\/* \1 *\//m' | # remove from featurecodes
  sed -r 's/(KEY `time` \(`time`,`level`\))/\/* \1 *\//m' | # remove from freepbx_log
  sed -r 's/AUTO_INCREMENT/AUTOINCREMENT/m' |
  sed -r 's/int\(11\) NOT NULL AUTOINCREMENT/INTEGER/m' |
  sed -r 's/`level` enum\(.+\)/`level` CHAR(20)/m' |
  grep -v LOCK\ TAB > newinstall.sqlite
  
sqlite3 /var/www/freepbx.db < newinstall.sqlite
