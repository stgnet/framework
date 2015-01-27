<?php

class FactoryReset {

	public $db = false;

	public function findInstallSQL() {
		// Returns the 'best' location of the 'newinstall.sql' file. 
		// First, check the current framework
		if (file_exists("/var/www/html/admin/modules/framework/SQL/newinstall.sql" )) {
			return "/var/www/html/admin/modules/framework/SQL/newinstall.sql";
		}

		// Not there. Try the best one.
		$vers = $this->findBestUserSrcFreePBX();
		if (file_exists("/usr/src/freepbx-$vers/SQL/newinstall.sql")) {
			return "/usr/src/freepbx-$vers/SQL/newinstall.sql";
		}
		
		// Huh. Try the first?
		$checks = glob("/usr/src/freepbx-*/SQL/newinstall.sql");
		if (isset($checks[0])) {
			return $checks[0];
		}

		throw new \Exception("Unable to find newinstall.sql");
	}

	public function findBestUsrSrcFreePBX() {
		$best = false;
		$versions = glob("/usr/src/freepbx-*");
		foreach ($versions as $v) {
			list($name, $vers) = explode('-', $v);
			if (!$best) {
				$best = $vers;
			} else {
				if (version_compare($vers, $best, "gt")) {
					$best = $vers;
				}
			}
		}
		return $best;
	}




	public function parseFPBXConf() {
		// Open freepbx.conf, but DON'T include it. Just grab the settings
		// out of it, and return it.
		if (!file_exists("/etc/freepbx.conf")) {
			throw new \Exception("No /etc/freepbx.conf");
		}

		$conf = file("/etc/freepbx.conf");
		$retarr = array();
		foreach ($conf as $line) {
			if (preg_match("/amp_conf\['(.+)'\].+'(.+)'/", $line, $out)) {
				$retarr[$out[1]] = $out[2];
			}
		}
		if (!isset($retarr['AMPDBPASS'])) {
			throw new \Exception("Couldn't find AMPDBPASS");
		}
		return $retarr;
	}

	public function validateDB(&$conf) {
		if (!is_array($conf)) {
			throw new \Exception("No conf");
		}
		$dsn = $conf['AMPDBENGINE'].":host=".$conf['AMPDBHOST'].";dbname=".$conf['AMPDBNAME'];

		$this->db = new PDO($dsn, $conf['AMPDBUSER'], $conf['AMPDBPASS']);
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public function dropAllTables() {
		if (!is_object($this->db)) {
			throw new \Exception("Can't talk to DB");
		}
		$res = $this->db->query("SHOW TABLES;")->fetchAll();
		foreach ($res as $table) {
			$this->db->query("DROP TABLE ".$table[0]);
		}
	}
}

