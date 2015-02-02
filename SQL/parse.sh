#!/bin/bash

sed -r 's/(ENGINE|DEFAULT.CHARSET|AUTO_INCREMENT)=\w+//gm' < newinstall.sql |  
  sed -r 's/(PRIMARY KEY \(`modulename`,`featurename`\)),/\1/m' |
  sed -r 's/(KEY `enabled` \(`enabled`\))/\/* \1 *\//m' |
  sed -r 's/AUTO_INCREMENT/AUTOINCREMENT/m' |
  set -r 's/int\(11\) NOT NULL AUTOINCREMENT/INTEGER PRIMARY KEY AUTOINCREMENT/m' |
  grep -v LOCK\ TAB > newinstall.sqlite
  
sqlite3 /var/www/freepbx.db < newinstall.sqlite
