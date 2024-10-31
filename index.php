<?php

/**
	 * Plugin Name: RankScanner: SEO Audit
	 * Plugin URI: http://www.rankscanner.com/wordpress/
	 * Description: SEO Audit is the best onsite SEO review tool. Have your blog crawled and checked for speed errors, technical warnings, SEO recommendations and much more.
	 * Version: 1.2.1
	 * Author: RankScanner
	 * Author URI: http://www.rankscanner.com/
	 * License: GPL2
 */
 
$PluginName = 'SEO Audit';

if(!function_exists('RankScanner_Audit')) {
	function RankScanner_Audit() {
		echo RankScanner_WriteContent('https://app.rankscanner.com/Domain/'.RankScanner_RootDomain().'/Audit/?WP_Version=2');
	}
}

require_once 'App/Main.php';

 ?>