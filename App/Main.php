<?php

require_once(ABSPATH.'wp-admin/includes/plugin.php');

if(!function_exists('RankScanner_RootDomain')) {
	function RankScanner_RootDomain($URL=FALSE) {
		if(!$URL) { $URL = $_SERVER['HTTP_HOST']; }
		$RootDomain = RankScanner_GetContent('!'.str_replace(array('http://', 'https://', 'www.'), FALSE, RankScanner_strto('lower', $URL)), '!', '/');

		$LongTLDs = array('.co.uk', '.com.au', '.us.com', '.me.uk', '.net.pe', '.com.es', '.com.ar', '.com.au', '.com.br', '.com.hk', '.com.pl', '.com.tr', '.com.tw', '.com.pl', '.com.vn', '.net.au', '.org.uk', '.com.pe', '.org.pe', '.nom.es', '.com.sg', '.de.com', '.org.es', '.edu.in', '.co.in', '.edu.mx', '.edu.pl', '.gov.uk', '.com.id');

		$TheTLD = FALSE; $TempTLD = FALSE;
		foreach($LongTLDs AS $TLD) {
			if(strpos($RootDomain, $TLD)) {
				$TheTLD = $TLD;
				$TempTLD = str_replace($TheTLD, '.'.str_replace('.', 'uuuuuuuuu', RankScanner_GetContent($TheTLD, '.')), $TheTLD);
			}
		}

		$exp = explode('/', $RootDomain);
		$RootDomain = str_replace($exp[0], RankScanner_strlreplace($TheTLD, $TempTLD, $exp[0]), $RootDomain);
		$RootDomain = str_replace(array('æ', 'ø', 'å'), array('aeaeae', 'oeoeoe', 'a1a1a1a1a1a'), $RootDomain);

		if(preg_match('~\.?([0-9a-zA-Z\-]+\.[a-zA-Z]+)$~', $RootDomain, $Match)) {
			$Match[1] = str_replace($TempTLD, $TheTLD, $Match[1]);
			$Match[1] = str_replace(array('aeaeae', 'oeoeoe', 'a1a1a1a1a1a'), array('æ', 'ø', 'å'), $Match[1]);
			return $Match[1];
		} else { return FALSE; }
	}
}
if(!function_exists('RankScanner_strto')) {
	function RankScanner_strto($UpperLower, $String) {
		$UpperLower = strtolower($UpperLower);
		if($UpperLower == 'lower') { return mb_convert_case($String, MB_CASE_LOWER, 'UTF-8'); }
		else if($UpperLower == 'upper') { return mb_convert_case($String, MB_CASE_UPPER, 'UTF-8'); }
	}
}
if(!function_exists('RankScanner_strlreplace')) {
	function RankScanner_strlreplace($search, $replace, $subject) {
		$pos = strrpos($subject, $search);
		if($pos !== false) { $subject = substr_replace($subject, $replace, $pos, strlen($search)); }

		return $subject;
	}
}
if(!function_exists('RankScanner_GetContent')) {
	function RankScanner_GetContent($c, $Start, $End=false){
		$line = 1;
		$len = strlen($Start);
		$pos_start = strpos($c, $Start)+strlen($Start);
		if(!$pos_start) { return false; $pos_start += $len; }

		if(!$End) {
			$pos_end = strpos($c, '\n', $pos_start);
			if(!$pos_end) { $pos_end = strpos($c, '\r\n', $pos_start); }
		} else { $pos_end = strpos($c, $End, $pos_start); }

		if($pos_end) { $r = substr($c, $pos_start, $pos_end-$pos_start); }
		else { $r = substr($c, $pos_start); }

		return $r;
	}
}

add_action('admin_menu','RankScanner_SettingsMenu');

if(!function_exists('RankScanner_SettingsMenu')) {
	function RankScanner_SettingsMenu() {
		add_menu_page('RankScanner Dashboard', 'RankScanner', 'manage_options', 'RankScanner', 'RankScanner_Dashboard', plugin_dir_url( __FILE__ ).'/images/RankScanner_Icon.png', '2.69');
		add_submenu_page('RankScanner', 'Dashboard', 'Dashboard', 'manage_options', 'RankScanner', 'RankScanner_Dashboard');

		$Function = 'RankScanner_Rankings'; #if(!function_exists('RankScanner_Rankings')) { $Function .= '_Missing'; }
		add_submenu_page('RankScanner', 'Rankings', 'Rankings', 'manage_options', $Function, $Function);

		$Function = 'RankScanner_Audit'; #if(!function_exists('RankScanner_Audit')) { $Function .= '_Missing'; }
		add_submenu_page('RankScanner', 'Audit', 'SEO Audit', 'manage_options', $Function, $Function);

		$Function = 'RankScanner_Buzz'; #if(!function_exists('RankScanner_Buzz')) { $Function .= '_Missing'; }
		add_submenu_page('RankScanner', 'Buzz', 'Buzz', 'manage_options', $Function, $Function);
	}
}

add_filter('plugin_action_links', 'RankScanner_PluginPageMenu', 10, 5);
if(!function_exists('RankScanner_PluginPageMenu')) {
	function RankScanner_PluginPageMenu($actions, $plugin_file) {
		static $plugin;

		if(strpos(' '.$plugin_file, 'rankscanner-') == 1) {
			$URL = 'admin.php?page=RankScanner_'; $Name = FALSE;
			if(strpos(' '.$plugin_file, 'rankscanner-seo-audit') == 1) { $URL .= 'Audit'; $Name = 'View SEO Audit'; }
			if(strpos(' '.$plugin_file, 'rankscanner-rank-tracking') == 1) { $URL .= 'Rankings'; $Name = 'Track Rankings'; }
			#if(strpos(' '.$plugin_file, 'rankscanner-buzz') == 1) { $URL .= 'Buzz'; $Name = 'Buzz alerts'; }

			if($Name) { $actions = array_merge(array('settings' => '<a href="'.$URL.'">'.$Name.'</a>'), $actions); }
			$actions = array_merge(array('support' => '<a href="https://wordpress.org/support/plugin/rankscanner-rank-tracking" target="_blank">Support</a>'), $actions);
		}

		return $actions;
	}
}


if(!function_exists('RankScanner_Dashboard')) {
	function RankScanner_Dashboard() {
		echo RankScanner_WriteContent('https://app.rankscanner.com/Domain/'.RankScanner_RootDomain().'/Dashboard/?WP_Version=2');
	}
}

if(!function_exists('RankScanner_Rankings') AND (!is_plugin_active('rankscanner-rank-tracking/index.php') AND $PluginName != 'Rank Tracking')) {
	function RankScanner_Rankings() {
		$ActivateURI = FALSE; if(file_exists(plugin_dir_path(__FILE__).'../../rankscanner-rank-tracking/index.php')) { $ActivateURI = admin_url().'admin.php?page=RankScanner_Rankings&ActivateRankTracking='.time(); }
		$InstallURI = admin_url().'plugin-install.php?tab=search&type=term&s=%22RankScanner-Rank-Tracking%22';
		if($_GET['ActivateRankTracking']) { activate_plugin('rankscanner-rank-tracking/index.php', 'admin.php?page=RankScanner_Rankings'); }

		echo '
<div style="text-align: center; width: 100%; max-width: 900px; margin: 0 auto; padding-top: 100px; line-height: 1.5; position: relative; border-bottom: 1px solid #c0c0c0;">
	<h1 style="margin-bottom: 40px;">Are you aware of your Google positions?</h1>
	<h2 style="margin-bottom: 40px;">The Rank Tracking plugin will automatically check your rankings on google.</h2>
	<a style="font-size: 24px; text-decoration: none; float: right; width: 65%; text-align: center; display: inline-block;" href="';if($ActivateURI){echo $ActivateURI;}else{echo $InstallURI;}echo '">';if($ActivateURI){echo 'Activate';}else{echo 'Install';}echo ' <strong>Rank Tracking</strong> ';if(!$ActivateURI){echo 'plugin';}echo '</a>
	<div style="font-size: 14px; width: 30%; text-align: left; padding-bottom: 20px;">
		Get the SEO rankings of your keywords checked automatically, and see beautiful statistics and history of your keywords’ performance as well as average performance of your entire WordPress blog.<br />
		<ul style="list-style: disc;">
			<li>Automatic checks</li>
			<li>Beautiful statistics</li>
			<li>Crawling top 100 SERP results</li>
			<li>Useful reports</li>
			<li>Track mobile rankings too</li>
			<li>Mail alerts for significant changes</li>
			<li>Failover safety checks</li>
			<li>And <strong>much more</strong></li>
		</ul>
		All <strong>for free!</strong> <a href="';if($ActivateURI){echo $ActivateURI;}else{echo $InstallURI;}echo '">';if($ActivateURI){echo 'Activate';}else{echo 'Install';}echo ' the plugin here</a>
	</div>
	<img src="'.plugin_dir_url( __FILE__ ).'/images/Rank-Tracking.png" style="width: 65%; position: absolute; right: 0%; bottom: 0;">
</div>';
	}
}

if(!function_exists('RankScanner_Audit') AND (!is_plugin_active('rankscanner-seo-audit/index.php') AND $PluginName != 'SEO Audit')) {
	function RankScanner_Audit() {
		$ActivateURI = FALSE; if(file_exists(plugin_dir_path(__FILE__).'../../rankscanner-seo-audit/index.php')) { $ActivateURI = admin_url().'admin.php?page=RankScanner_Audit&ActivateSEOAudit='.time(); }
		$InstallURI = admin_url().'plugin-install.php?tab=search&type=term&s=%22RankScanner-SEO-Audit%22';
		if($_GET['ActivateSEOAudit']) { activate_plugin('rankscanner-seo-audit/index.php', 'admin.php?page=RankScanner_Audit'); }

		echo '
<div style="text-align: center; width: 100%; max-width: 900px; margin: 0 auto; padding-top: 100px; line-height: 1.5;">
	<h1 style="margin-bottom: 40px;">Is your blog optimised for Google?</h1>
	<h2 style="margin-bottom: 40px;">The SEO Audit plugin will let you know if you have any onsite SEO errors or warnings, and tell you exactly how to fix them.</h2>
	<div style="font-size: 14px; width: 30%; float: left; text-align: left;">
		<a style="font-size: 24px; text-decoration: none;" href="';if($ActivateURI){echo $ActivateURI;}else{echo $InstallURI;}echo '">';if($ActivateURI){echo 'Activate';}else{echo 'Install';}echo ' <strong>SEO Audit</strong> ';if(!$ActivateURI){echo 'plugin';}echo '</a><br />
		Request a comprehensive onsite SEO review of your entire website, and see what you are doing wrong in terms of technical aspects, site speed as well as recommendations for content and other important SEO factors.<br />
		<br />
		SEO Audit goes through all your pages and will...
		<ul style="list-style: disc;">
			<li>Find HTML errors</li>
			<li>Review site speed</li>
			<li>Check content quality</li>
			<li>Suggest optimal cache settings</li>
			<li>Recommend optimisations</li>
			<li>... and <strong>much more!</strong></li>
		</ul>
		All <strong>for free!</strong> <a href="';if($ActivateURI){echo $ActivateURI;}else{echo $InstallURI;}echo '">';if($ActivateURI){echo 'Activate';}else{echo 'Install';}echo ' the plugin here</a>
	</div>
	<img src="'.plugin_dir_url( __FILE__ ).'/images/SEO-Audit.png" style="width: 65%; position: absolute; right: 0%;">
</div>';
	}
}

if(!function_exists('RankScanner_Buzz') AND (!is_plugin_active('rankscanner-buzz/index.php') AND $PluginName != 'Buzz')) {
	function RankScanner_Buzz() {
		$ActivateURI = FALSE; if(file_exists(plugin_dir_path(__FILE__).'../../rankscanner-buzz/index.php')) { $ActivateURI = admin_url().'admin.php?page=RankScanner_Buzz&ActivateBuzz='.time(); }
		$InstallURI = admin_url().'plugin-install.php?tab=search&type=term&s=%22RankScanner-Buzz%22';
		if($_GET['ActivateBuzz']) { activate_plugin('rankscanner-buzz/index.php', 'admin.php?page=RankScanner_Buzz'); }

		echo '
<div style="text-align: center; width: 100%; max-width: 900px; margin: 0 auto; padding-top: 100px; line-height: 1.5;">
	<h1 style="margin-bottom: 40px;">Want to know when people talk about your blog?</h1>
	<h2 style="margin-bottom: 40px;">Monitor your online reputation and get alerted immediately when your brand (or custom keywords) are mentioned on the web.</h2>
	<div style="font-size: 14px; width: 30%; float: left; text-align: left;">
		<a style="font-size: 24px; text-decoration: none;" href="';if($ActivateURI){echo $ActivateURI;}else{echo $InstallURI;}echo '">';if($ActivateURI){echo 'Activate';}else{echo 'Install';}echo ' <strong>Buzz</strong> ';if(!$ActivateURI){echo 'plugin';}echo '</a><br />
		With Buzz you can monitor certain keywords and phrases on the web and get alerted (automatically on email) as soon as someone mentions the particular keyword online, whether it is on social media, blogs or other places on the internet.<br />
		<br />
		Buzz monitors the web and will...
		<ul style="list-style: disc;">
			<li>Alert you when mentioned on the web</li>
			<li>Inform of when mentions happened</li>
			<li>Detailed real-time overview in WordPress</li>
			<li>Give you a direct link to the source</li>
			<li>... and <strong>much more!</strong></li>
		</ul>
		All <strong>for free!</strong> <a href="';if($ActivateURI){echo $ActivateURI;}else{echo $InstallURI;}echo '">';if($ActivateURI){echo 'Activate';}else{echo 'Install';}echo ' the plugin here</a>
	</div>
	<img src="'.plugin_dir_url( __FILE__ ).'/images/SEO-Audit.png" style="width: 65%; position: absolute; right: 0%;">
</div>';
	}
}

if(!function_exists('RankScanner_WriteContent')) {
	function RankScanner_WriteContent($URL) {
		return '
<link rel="stylesheet" href="'.plugin_dir_url( __FILE__ ).'/css/RankScanner.css'.'" type="text/css" media="all" />
<script language="javascript" type="text/javascript">
	var rankscanner_hasloaded = false; var rankscanner_firstrun = false;
	function RankScanner_ResizeContent() {
		if(!rankscanner_hasloaded) {
			jQuery("#RankScanner_Content").css("top", "100%");
			window.addEventListener("message", function(event) {
				if(event.origin != \'https://app.rankscanner.com\') { return; }
				else if(isNaN(event.data)) { return; }

				var height = parseInt(event.data);
				if(height > 0) {
					jQuery("#RankScanner_ContentContainer").css("height", height + "px");
					if(!rankscanner_firstrun) {
						jQuery("#RankScanner_Loader").stop().fadeTo(150, 0, function() {
							jQuery("#RankScanner_Content").stop().fadeOut(1, function() {
								jQuery("#RankScanner_Content").css("top", "0");
								jQuery("#RankScanner_Content").stop().fadeIn(250);
								rankscanner_firstrun = true;
							});
						});
					}
				}
			}, false);
			rankscanner_hasloaded = true;
		}
	}
</script>
<div id="RankScanner_Loader">Loading...</div>
<div id="RankScanner_ContentContainer">
	<iframe id="RankScanner_Content" onload="RankScanner_ResizeContent();" src="'.$URL.'" frameborder="0" scrolling="no" height="100%" width="100%"></iframe>
</div>';
	}
}

?>