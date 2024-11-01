<?php
/*
Plugin Name: Wp Bangla Code detector
Plugin URI: http://ranapatwary.co.cc/contact-me
Description: Detect and recover wordpress themes code problems
Author:Anowar Hossain Rana Patwary
Version: 2.02
Author URI: http://ranapatwary.co.cc/about
*/

/*  Copyright 2011 Anowar hossain Rana Patwary (BDTUNE Software Ltd.) - (email : ahrnetwork@gmail.com)
              +8801817633732

                      এই প্লাগইন একদম ফ্রি
                 দয়াকরে অনুমতি ছাড়া কোন কোড় পরিবর্তন করবেন না
				 GNU General Public License 
                  তাতে পরবর্তী ভার্সন ব্যাবহার কার যাবে না 
				  এটি বিডিটিউন সফ্টওয়্যার লি. এর সম্পত্তি
				  ওয়ার্ডপ্রেস অর্গানাইজেশন এর কাছে লাইসেন্স ও ক্রিয়েটিভ কমন এ এর ভার্সন কোড দেওয়া হয়েছে
				  www.wordpress.org/support/profile/ranapatwary
				  www.ranapatwary.co.cc/contact-me
				  (c) 2011 বিডিটিউন কতৃপক্ষ এর সর্বসস্ত্ব সংরক্ষন করে

    
*/

function bcd_check_theme($template_files, $theme_title) {
	$static_count = 0;
	foreach ($template_files as $tfile)
	{	
		/*
		 * Check for base64 Encoding
		 * Here we check every line of the file for base64 functions.
		 * 
		 */
			
		$lines = file($tfile, FILE_IGNORE_NEW_LINES); // Read the theme file into an array

		$line_index = 0;
		$is_first = true;
		foreach($lines as $this_line)
		{
			if (stristr ($this_line, "base64")) // Check for any base64 functions
			{
				if ($is_first) {
						$bad_lines .= bcd_make_edit_link($tfile, $theme_title); 
						$is_first = false;
					}
				$bad_lines .= "<div class=\"bcd-bad\"><strong>Line " . ($line_index+1) . ":</strong> \"" . trim(htmlspecialchars(substr(stristr($this_line, "base64"), 0, 45))) . "...\"</div>";
			}
			$line_index++;
		}
		
		/*
		 * Check for Static Links
		 * Here we utilize a regex to find HTML static links in the file.
		 * 
		 */

		$file_string = file_get_contents($tfile);

		$url_re='([[:alnum:]\-\.])+(\\.)([[:alnum:]]){2,4}([[:blank:][:alnum:]\/\+\=\%\&\_\\\.\~\?\-]*)';
		$title_re='[[:blank:][:alnum:][:punct:]]*';	// 0 or more: any num, letter(upper/lower) or any punc symbol
		$space_re='(\\s*)'; 
				
		if (preg_match_all ("/(<a)(\\s+)(href".$space_re."=".$space_re."\"".$space_re."((http|https|ftp):\\/\\/)?)".$url_re."(\"".$space_re.$title_re.$space_re.">)".$title_re."(<\\/a>)/is", $file_string, $out, PREG_SET_ORDER))
		{
			$static_urls .= bcd_make_edit_link($tfile, $theme_title); 
									  
			foreach( $out as $key ) {
				$static_urls .= "<div class=\"bcd-ehh\">";
				$static_urls .= htmlspecialchars($key[0]);
				$static_urls .= "</div>";
				$static_count++;
			}			  
		}  
	} // End for each file in template loop
	
	// Assemble the HTML results for the completed scan of the current theme
	if (!isset($bad_lines)) {
		$summary = '<span class="bcd-good-notice">কোন সমস্যা পাওয়া যায়নি!</span>';
	} else {
		$summary = '<span class="bcd-bad-notice">গোপনীয় কোড যা Decode/Encode করা!</span>';
	}
	if(isset($static_urls)) {
		$summary .= '<span class="bcd-ehh-notice"><strong>'.$static_count.'</strong> অতিরিক্ত লিন্ক /link পাওয়া গেছে...</span>';
	}
	
	return array('summary' => $summary, 'bad_lines' => $bad_lines, 'static_urls' => $static_urls, 'static_count' => $static_count);

}


function bcd_make_edit_link($tfile, $theme_title) {
	// Assemble the HTML links for editing files with the built-in WP theme editor
	
	if ($GLOBALS['wp_version'] >= "2.9") {
		return "<div class=\"file-path\"><a href=\"theme-editor.php?file=/" . substr(stristr($tfile, "themes"), 0) . "&amp;theme=" . urlencode($theme_title) ."&amp;dir=theme\">" . substr(stristr($tfile, "wp-content"), 0) . " [ঠিক করতে চাই]</a></div>";	
	} elseif ($GLOBALS['wp_version'] >= "2.6") {
		return "<div class=\"file-path\"><a href=\"theme-editor.php?file=/" . substr(stristr($tfile, "themes"), 0) . "&amp;theme=" . urlencode($theme_title) ."\">" . substr(stristr($tfile, "wp-content"), 0) . " [ঠিক করতে চাই]</a></div>";
	} else {
		return "<div class=\"file-path\"><a href=\"theme-editor.php?file=" . substr(stristr($tfile, "wp-content"), 0) . "&amp;theme=" . urlencode($theme_title) ."\">" . substr(stristr($tfile, "wp-content"), 0) ." [ঠিক করতে চাই]</a></div>";
	}
	
}

function bcd_get_template_files($template) {
	// Scan through the template directory and add all php files to an array
	
	$theme_root = get_theme_root();
	
	$template_files = array();
	$template_dir = @ dir("$theme_root/$template");
	if ( $template_dir ) {
		while(($file = $template_dir->read()) !== false) {
			if ( !preg_match('|^\.+$|', $file) && preg_match('|\.php$|', $file) )
				$template_files[] = "$theme_root/$template/$file";
		}
	}

	return $template_files;
}

function bcd_init() {
	if ( function_exists('add_submenu_page') )
		$page = add_submenu_page('themes.php',__('bcd'), __('bcd'), '10', 'bcd.php', 'bcd');
}

add_action('admin_menu', 'bcd_init');

function bcd() {

	?>
<script type="text/javascript">
	function toggleDiv(divid){
	  if(document.getElementById(divid).style.display == 'none'){
		document.getElementById(divid).style.display = 'block';
	  }else{
		document.getElementById(divid).style.display = 'none';
	  }
	}
</script>	
<h2>
</h3>
    <?php _e('Detect and recover wordpress themes code problems  ('); ?>
 <?php _e('By Anowar Hossain Rana Patwary)'); ?>
	</h2>
	</h3>
<div class="pinfo">
    এই প্লাগইন আপনার Wordpress Themes এ থাকা সব সমস্যা দেখাবে (  আপলোড় করে Page refresh করুন) [ চেক করার পর এটি আবার রিমোভ করতে হবে ] -আমাকে জানাবেন যদি কাজ না করে.<br/>
    আরো বিস্তারিত জানতে ও জানাতে ভিজিট করুন অথবা মেইল ahrnetwork@gmail.com   <a href="http://ranapatwary.co.cc/about">About Me</a><br/>
	মন্তব্য,উ্যসাহ এবং আর আরো কিভাবে সুন্দর করা যায় জানাতে <a href="http://ranapatwary.co.cc/contact-me">Contact</a>.
</div>
<div id="wrap">
    <?php
	$themes = get_themes();
	$theme_names = array_keys($themes);
	natcasesort($theme_names);
	foreach ($theme_names as $theme_name) {
		$template_files = bcd_get_template_files($themes[$theme_name]['Template']);
		$title = $themes[$theme_name]['Title'];
		$version = $themes[$theme_name]['Version'];
		$author = $themes[$theme_name]['Author'];
		$screenshot = $themes[$theme_name]['Screenshot'];
		$stylesheet_dir = $themes[$theme_name]['Stylesheet Dir'];
		
		if ($GLOBALS['wp_version'] >= "2.9") {
			$theme_root_uri = $themes[$theme_name]['Theme Root URI'];
			$template = $themes[$theme_name]['Template'];
		}

		$results = bcd_check_theme($template_files, $title);
	?>
    <div id="bcdthemes">
        <?php if ( $screenshot ) : 
			if ($GLOBALS['wp_version'] >= "2.9") : ?>
				<img src="<?php echo $theme_root_uri.'/'.$template.'/'.$screenshot.'"'."alt=\"$title Screenshot\""; ?> />
			<?php else : ?>
				<img src="<?php echo get_option('siteurl') . '/wp-content' . str_replace('wp-content', '', $stylesheet_dir) . '/' . $screenshot.'"'."alt=\"$title Screenshot\""; ?> />			
			<?php endif; ?>
        <?php else : ?>
        	<div class="bcdnoimg">No Screenshot Found</div>
        <?php endif; ?>

		<?php echo '<div class="t-info">'."<strong>$title</strong> $version ডিজাইনার $author"; ?>
		
		<?php if ($results['bad_lines'] != '' || $results['static_urls'] != '') : ?>
			<input type="button" value="Details" class="button-primary" id="details" name="details" onmousedown="toggleDiv('<?php echo $title; ?>');" href="javascript:;"/>
		<?php endif; ?>
			</div>
			
		<?php echo $results['summary']; ?>	
			
        <div class="bcdresults" id="<?php echo $title; ?>" style="display:none;">
            <?php echo $results['bad_lines'].$results['static_urls']; ?>
        </div>
		
    </div>
		
    <?php
	}
	echo '</div>';
}

// CSS to format results of themes check
function bcd_css() {
echo '
<style type="text/css">
<!--

#wrap {
	background-color:#FFF;
	margin-right:5px;
}

.bcd-bad,.bcd-ehh {
	border:1px inset #000;
	font-family:"Courier New", Courier, monospace;
	margin-bottom:10px;
	margin-left:10px;
	padding:5px;
	width:90%;
}

.bcd-bad {
	background:#FFC0CB;
}

.bcd-ehh {
	background:#FFFEEB;
}

span.bcd-good-notice, span.bcd-bad-notice, span.bcd-ehh-notice {
	float:left;
	font-size:120%;
	margin: 25px 10px 0 0;
	padding:10px;
}

span.bcd-good-notice {
	background:#3fc33f;
	border:1px solid #000;
	width:90px;
	vertical-align: middle;
}

span.bcd-bad-notice {
	background:#FFC0CB;
	border:1px solid #000;
	width:195px;
}

span.bcd-ehh-notice {
	background:#FFFEEB;
	border:1px solid #ccc;
	width:210px;
}

.file-path {
	color:#666;
	font-size:12px;
	padding-bottom:1px;
	padding-top:5px;
	text-align:right;
	width:92%;
}

.file-path a {
	text-decoration:none;
}

.pinfo {
	background:#DCDCDC;
	margin:5px 5px 40px;
	padding:5px;
}

#bcdthemes {
	border-top:1px solid #ccc;
	margin:10px;
	min-height:100px;
	padding-bottom:20px;
	padding-top:20px;
}

#bcdthemes img,.bcdnoimg {
	border:1px solid #000;
	color:#DCDCDC;
	float:left;
	font-size:16px;
	height:75px;
	margin:10px;
	text-align:center;
	width:100px;
}

.bcdresults {
	clear:left;
	margin-left:130px;
	
}
-->
</style>
';
	}

add_action('admin_head', 'bcd_css');
    ?>