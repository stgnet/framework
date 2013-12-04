<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

// HELPER FUNCTIONS:

function framework_print_errors($src, $dst, $errors) {
	out("error copying files:");
	out(sprintf(_("'cp -rf' from src: '%s' to dst: '%s'...details follow"), $src, $dst));
	freepbx_log(FPBX_LOG_ERROR, sprintf(_("framework couldn't copy file to %s"),$dst));
	foreach ($errors as $error) {
		out("$error");
		freepbx_log(FPBX_LOG_ERROR, _("cp error output: $error"));
	}
}

global $amp_conf;
global $asterisk_conf;

// default php will check local path, or should we add that in?
include "libfreepbx.install.php";

$debug = false;
$dryrun = false;

/** verison_compare that works with freePBX version numbers
 *  included here because there are some older versions of functions.inc.php that do not have
 *  it included as it was added during 2.3.0beta1
 */
if (!function_exists('version_compare_freepbx')) {
	function version_compare_freepbx($version1, $version2, $op = null) {
		$version1 = str_replace("rc","RC", strtolower($version1));
		$version2 = str_replace("rc","RC", strtolower($version2));
		if (!is_null($op)) {
			return version_compare($version1, $version2, $op);
		} else {
			return version_compare($version1, $version2);
		}
	}
}

/*
 * Framework install script
 */

	$base_source = dirname(__FILE__) . "/amp_conf";
	$htdocs_source = $base_source . "/htdocs/*";
	$bin_source = $base_source . "/bin/*";
	$agibin_source = $base_source . "/agi-bin/*";

	if (file_exists('/etc/pbx/aver.flag')) {
		out(_("PIAF system detected - removing index.php from package"));
		if (unlink($base_source . "/htdocs/index.php")) {
			out(_("ok"));
		} else {
			out(_("failed to remove"));
		}
	}

	if (!file_exists(dirname($htdocs_source))) {
    out(sprintf(_("No directory %s, install script not needed"),dirname($htdocs_source)));
    return true;
  }

	// These are required by libfreepbx.install.php library for upgrade routines
	//
	define("UPGRADE_DIR", dirname(__FILE__)."/upgrades/");
	define("MODULE_DIR",  $amp_conf['AMPWEBROOT'].'/modules/');

	$htdocs_dest = $amp_conf['AMPWEBROOT'];
	$bin_dest    = isset($amp_conf['AMPBIN']) ? $amp_conf['AMPBIN'] : '/var/lib/asterisk/bin';
	$agibin_dest = isset($asterisk_conf['astagidir']) ? $asterisk_conf['astagidir']:'/var/lib/asterisk/agi-bin';

	$msg = _("installing files to %s..");

	$out = array();
	outn(sprintf($msg, $htdocs_dest));
	exec("cp -rf $htdocs_source $htdocs_dest 2>&1",$out,$ret);
	if ($ret != 0) {
		framework_print_errors($htdocs_source, $htdocs_dest, $out);
		out(_("done, see errors below"));
	} else {
		out(_("done"));
	}


	unset($out);
	outn(sprintf($msg, $bin_dest));
	exec("cp -rf $bin_source $bin_dest 2>&1",$out,$ret);
	if ($ret != 0) {
		framework_print_errors($bin_source, $bin_dest, $out);
		out(_("done, see errors below"));
	} else {
		out(_("done"));
	}

	unset($out);
	outn(sprintf($msg, $agibin_dest));
	exec("cp -rf $agibin_source $agibin_dest 2>&1",$out,$ret);
	if ($ret != 0) {
		framework_print_errors($agibin_source, $agibin_dest, $out);
		out(_("done, see errors below"));
	} else {
		out(_("done"));
	}

  /*TODO: (Requirment for #4733)
   *
   * 1. Update publish.pl to grab a copy of amportal and put it somehwere.
   * 2. If we have access to do an md5sum on AMPSBIN/amportal do it and
   *    compare to the local copy.
   * 3. If the md5sum is different or we couldn't check, put amportal in AMPBIN
   * 4. If we decided they need a new one, then write out a message that they
   *    should run amportal to update it.
   */

	if (function_exists('upgrade_all')) {
		upgrade_all(getversion());
    // We run this each time so that we can add settings if need be
    // without requiring a major version bump
    //
    freepbx_settings_init(true);
	} else {
		out("[ERROR] Function: 'upgrade_all' not present, libfreepbx.install.php seems not to be installed");
	}

	// We now delete the files, this makes sure that if someone had an unprotected system where they have not enabled
	// the .htaccess files or otherwise allowed direct access, that these files are not around to possibly cause problems
	//
	out(_("framework file install done, removing packages from module"));

	$rem_files[] = $base_source;
	$rem_files[] = dirname(__FILE__) . "/upgrades";
	$rem_files[] = dirname(__FILE__) . "/libfreepbx.install.php";
	 
	foreach ($rem_files as $target) {
		unset($out);
		exec("rm -rf $target 2>&1",$out,$ret);
		if ($ret != 0) {
			out(sprintf(_("an error occured removing the packaged file/directory: %s"), $target));
		} else {
			out(sprintf(_("file/directory: %s removed successfully"), $target));
		}
	}

	if (!$amp_conf['DISABLE_CSS_AUTOGEN'] && !function_exists('compress_framework_css')) {
		function compress_framework_css() {
			global $amp_conf;
			$mainstyle_css      = $amp_conf['BRAND_CSS_ALT_MAINSTYLE'] 
                           ? $amp_conf['BRAND_CSS_ALT_MAINSTYLE'] 
                           : 'assets/css/mainstyle.css'; 
			$wwwroot            = $amp_conf['AMPWEBROOT'] . "/admin";
			$mainstyle_css_gen  = $wwwroot . '/' . $amp_conf['mainstyle_css_generated'];
			$mainstyle_css      = $wwwroot . '/' . $mainstyle_css;
			$new_css            = file_get_contents($mainstyle_css) 
                           . file_get_contents($wwwroot . '/' . $amp_conf['JQUERY_CSS']);
			$new_css            = CssMin::minify($new_css);
			$new_md5            = md5($new_css);
			$gen_md5            = file_exists($mainstyle_css_gen) ? md5(file_get_contents($mainstyle_css_gen)) : '';

    
			//regenerate if hashes dont match
			if ($new_md5 != $gen_md5) {
				$ms_path = dirname($mainstyle_css);

				// it's important for filename tp unique 
				//because that will force browsers to reload vs. caching it
				$mainstyle_css_generated = $ms_path.'/mstyle_autogen_' . time() . '.css';
				$ret = file_put_contents($mainstyle_css_generated, $new_css);

				// Now assuming we write something reasonable, we need to save the generated file name and mtimes so
				// next time through this ordeal, we see everything is setup and skip all of this.
				// 
				// we skip this all this if we get back false or 0 (nothing written) in which case we will use the original
				// We need to set the value in addition to defining the setting 
				//since if already defined the value won't be reset.
				if ($ret) {
					$freepbx_conf =& freepbx_conf::create();
					$val_update['mainstyle_css_generated'] = str_replace($wwwroot . '/', '', $mainstyle_css_generated);
           
					// Update the values (in case these are new) and commit
					$freepbx_conf->set_conf_values($val_update, true, true);


					// If it is a regular file (could have been first time and previous was blank then delete old
					if (is_file($mainstyle_css_gen) && !unlink($mainstyle_css_gen)) {
						freepbx_log(FPBX_LOG_WARNING,
						sprintf(_('failed to delete %s from assets/css directory after creating updated CSS file.'), $mainstyle_css_generated_full_path));
					}
				}
			}
		}
	}

	if (!$amp_conf['DISABLE_CSS_AUTOGEN']) {
		compress_framework_css(); 
	}
?>