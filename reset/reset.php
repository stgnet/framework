<?php

include 'FactoryReset.class.php';

$r = new FactoryReset();


// Grab our current conf
$conf = $r->parseFPBXConf();

// Try to connect with our known DB credentials
$r->validateDB($conf);

// OK, now we need to find our newinstall.sql file
$sql = $r->findInstallSql();

// If we made it to here, we're good to go.
// Drop all the tables in our database
$r->dropAllTables();

// Load our newinstall.sql into the database
$cmd = "mysql ".$conf['AMPDBNAME']." -u".$conf['AMPDBUSER']." -p".$conf['AMPDBPASS']." < $sql";
exec($cmd, $out, $ret);
if ($ret != 0) {
	throw new \Exception("Unable to import DB - ".json_encode($out));
}

// Delete any session files
$sessions = glob("/var/lib/php/session/*");
foreach ($sessions as $f) {
	unlink($f);
}

// Now, we can reinstall everything. This takes some time.
$v = $r->findBestUsrSrcFreePBX();
chdir("/usr/src/freepbx-$v");

`yes | ./install_amp --installdb`;





