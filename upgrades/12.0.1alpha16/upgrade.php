<?php
global $db;

$row = $db->getRow('SELECT * FROM featurecodes LIMIT 1', DB_FETCHMODE_ASSOC);
if (!isset($row['helptext'])) {
	out("Adding helptext to featurecodes table");
	$sql = "ALTER TABLE `featurecodes` ADD COLUMN `helptext` varchar (250) NOT NULL AFTER `description`";
	$db->query($sql);
}

