<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * Object wrapper for the less compiler in FreePBX
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */

require_once dirname(dirname(__FILE__)).'/less/Less.php';

class Less extends Less_Parser {
	public function __construct($freepbx = null, $env = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;

		parent::__construct($env);
	}

	/**
	 * Generate all FreePBX Main Style Sheets
	 * @param {array} $variables = array() Array of variables to override
	 */
	public function generateMainStyles($variables = array()) {
		global $amp_conf;
		$less_rel = '/admin/assets';
		$less_path = $amp_conf['AMPWEBROOT'].'/admin/assets/less';

		$varOverride = $this->FreePBX->Hooks->processHooks($variables);
		if(!empty($varOverride)) {
			foreach($varOverride as $o) {
				$variables = array_merge($o, $variables);
			}
		}

		//compile these all into one giant file so that variables cross
		$less_dirs = array("bootstrap","freepbx","font-awesome");
		$out = array();
		$out['compiled_less_files'] = array();
		foreach($less_dirs as $dir) {
			$path = $less_path."/".$dir;
			if (is_dir($path)) {
				$files[$path."/".$dir.".less"] = $less_rel;
			}
		}

		\Less_Cache::$cache_dir = $less_path.'/cache';
		$filename = \Less_Cache::Get( $files, array('compress' => true), $variables );
		$out['compiled_less_files'][] = 'cache/'.$filename;

		$extra_less_dirs = array("buttons");
		$out['extra_compiled_less_files'] = array();
		foreach($extra_less_dirs as $dir) {
			$path = $less_path."/".$dir;
			if (is_dir($path)) {
				$file = $this->getCachedFile($path,$less_rel, $variables);
				$out['extra_compiled_less_files'][$dir] = $dir.'/cache/'.$file;
			}
		}
		return $out;
	}

	/**
	 * Generate Individual Module Style Sheets
	 * @param {string} $module    The module name
	 * @param {array} $variables =             array() Array of variables to override
	 */
	public function generateModuleStyles($module, $variables = array()) {
		global $amp_conf;
		$less_rel = '/admin/assets/' . $module;
		$less_path = $amp_conf['AMPWEBROOT'] . '/admin/modules/' . $module . '/assets/less';
		$files = array();
		if(file_exists($less_path)) {
			$varOverride = $this->FreePBX->Hooks->processHooks($variables);
			if(!empty($varOverride)) {
				foreach($varOverride as $o) {
					$variables = array_merge($o, $variables);
				}
			}

			$files[] = $this->getCachedFile($less_path,$less_rel,$variables);
		}
		return $files;
	}

	/**
	 * Parse a Directory to find the appropriate less files
	 *
	 * If a bootstrap.less file exists then parse that only (looking for imports)
	 * Otherwise just find the files to parse at the same time. This will return
	 * the generated CSS however it's highly advisable you end up using getCacheFile
	 *
	 * @param string $dir The directory housing the less files
	 * @param string $uri_root The uri root of the web request
	 * @return string The CSS file output
	 */
	public function parseDir($dir, $uri_root = '') {
		//Load bootstrap only if it exists as this will tell us the correct load order
		if(!file_exists($dir.'/cache')) {
			if(!mkdir($dir.'/cache')) {
				die_freepbx('Can Not Create Cache Folder at '.$dir.'/cache');
			}
		}
		$this->SetOption('cache_dir',$dir.'/cache');
		$this->SetOption('compress',true);
		$basename = basename($dir);
		if(file_exists($dir."/bootstrap.less")) {
			$this->parseFile($dir."/bootstrap.less", $uri_root);
		} elseif(file_exists($dir."/".$basename.".less")) {
			$this->parseFile($dir."/".$basename.".less", $uri_root);
		} else {
			//load them all randomly. Probably in alpha order
			foreach(glob($dir."/*.less") as $file) {
				$this->parseFile($file, $uri_root);
			}
		}
		return $this->getCss();
	}

	/**
	 * Generates and Gets the Cached files
	 *
	 * This will generated a compiled less file into css format
	 * but it will cache it so that it doesnt happen unless the file has changed
	 *
	 * @param string $dir The directory housing the less files
	 * @param string $uri_root The uri root of the web request
	 * @param array $variables Array of variables to override
	 * @return string the CSS filename
	 */
	public function getCachedFile($dir, $uri_root = '', $variables = array()) {
		if(!file_exists($dir.'/cache')) {
			if(!mkdir($dir.'/cache')) {
				die_freepbx('Can Not Create Cache Folder at '.$dir.'/cache');
			}
		}
		\Less_Cache::$cache_dir = $dir.'/cache';
		$files = array();
		$basename = basename($dir);
		if(file_exists($dir."/bootstrap.less")) {
			$files = array( $dir."/bootstrap.less" => $uri_root );
			$filename = \Less_Cache::Get( $files, array('compress' => true), $variables );
		} elseif(file_exists($dir."/".$basename.".less")) {
			$files = array( $dir."/".$basename.".less" => $uri_root );
			$filename = \Less_Cache::Get( $files, array('compress' => true), $variables );
		} else {
			//load them all randomly. Probably in alpha order
			foreach(glob($dir."/*.less") as $file) {
				$files[$file] = $uri_root;
			}
			uksort($files, "strcmp");
			$filename = \Less_Cache::Get( $files, array('compress' => true), $variables );
		}
		return $filename;
	}
}