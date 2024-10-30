<?php 	
/*
Plugin Name: CallOnPost
Plugin URI: http://www.wecos.de
Description: "Call On Post" is a Post Notifier as a plugin which will "fetch" automatic the specified URL when a post is published or modified by a user.
Author: Heiko Weber
Author URI: http://www.wecos.de
Version: 1.0
*/

function callonpost_curl($url) {
    static $urls_called = null;
    if (is_null($urls_called)) {
        $urls_called = array();
    }
    if (isset($urls_called[$url])) {
        return;
    }
    $urls_called[$url] = true;
    
    $curlOptions = array(
        CURLOPT_CONNECTTIMEOUT_MS => 1000,
        CURLOPT_FAILONERROR => 1,
        CURLOPT_FOLLOWLOCATION =>  1,
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_HEADER => 1,
        CURLOPT_VERBOSE => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
   );
        
    $curl = NULL;
    try {
        $curl = @curl_init();
        if (@curl_setopt_array($curl, $curlOptions)) {
            @curl_exec($curl);
            if (!@curl_errno($curl)) {
                @curl_getinfo($curl, CURLINFO_HTTP_CODE);
            }
        }
    }
    catch (Exception $e) {}
    if ($curl) {
        @curl_close($curl);
    }
}

function callonpost_fetchUrl($id) {
    // $id not used, maybe future feature ...
    if (function_exists('curl_init')) {
        $options = get_option('callonpost_notification_options');
        if (is_array($options)) {
            if (isset($options['url']) && !empty($options['url'])) {
                callonpost_curl($options['url']);
            }
        }
    }
}

function callonpost_savePost($id) {
    callonpost_fetchUrl($id);
}
add_action('save_post', 'callonpost_savePost');

function callonpost_editPost($id) {
    callonpost_fetchUrl($id);
}
add_action('edit_post', 'callonpost_editPost');

function callonpost_publishPost($id) {
    callonpost_fetchUrl($id);
}
add_action('publish_post', 'callonpost_publishPost');

function callonpost_trashed($id) {
	callonpost_fetchUrl($id);
}
add_action('trashed_post', 'callonpost_trashed'); 

// Settings

function callonpost_notification() {
	add_options_page('Call On Post', 'Call On Post', 'administrator', __FILE__, 'callonpost_display_options_page');
} 
add_action( 'admin_menu', 'callonpost_notification' );

function callonpost_notification_initialize_options() {
	add_settings_section('callonpost_main_section', 'Main Settings', 'callonpost_main_section_cb', __FILE__);
	add_settings_field('url', 'URL to call:', 'callonpost_notification_setting', __FILE__, 'callonpost_main_section');
	register_setting('callonpost_notification_options', 'callonpost_notification_options', 'callonpost_notification_options_validate');	
}
add_action( 'admin_init', 'callonpost_notification_initialize_options' );

function callonpost_notification_options_validate($value) {
    if (!empty($value['url'])) {
        if (@parse_url($value['url']) === false) {
            $value['url'] = '';
        } else {
            $value['url'] = filter_var($value['url'], FILTER_VALIDATE_URL);
        }
    }
    return $value;
}

function callonpost_display_options_page() {
?>
	<div class="wrap">
		<h2>Call On Post Options</h2>		
		<form method="post" action="options.php">
			<?php settings_fields('callonpost_notification_options'); ?>
			<?php do_settings_sections(__FILE__); ?>
			<p class="submit"> <input type="submit" value="Save Changes" class="button-primary" name="submit"> </p>
		</form>
	</div>	
<?php
} 

function callonpost_main_section_cb() {
	echo "These options are designed to specify the URL to call on post changes.";
} 

function callonpost_notification_setting() {	
?>
	<ul>
	<?php
		$options = get_option('callonpost_notification_options');
        $url = isset($options['url']) ? htmlspecialchars($options['url']) : '';
        $html = "<input type='text' name='callonpost_notification_options[url]' value='{$url}' >";
        echo $html;
	?>
	</ul>
<?php
} 