<?php
/**
 * Purchase theme verification
 *
 * @package Floris
 */

// Setup Ajax action hook

add_action( 'admin_footer', 'start_verification' );

function start_verification(){
	$whitelist = array( '127.0.0.1', '::1' );
    if( !in_array( $_SERVER['REMOTE_ADDR'], $whitelist) ){
		if (!get_option( 'trial_period' )) {
			update_option( 'trial_period', date("Y-m-d"));
		}
		if (get_option( 'enable_full_version' )) {
			$content = __('The license is verified.','floris');
	    }else{
	    	$content = __('The license is not verified.','floris');
	    }
	    echo "<script> jQuery('#info-verification_status p').html('$content');
	        jQuery('#info-verification_status').show('fast'); </script>";
	    if (get_option( 'enable_full_version' )) {
			echo "<script> setTimeout(function(){jQuery('#validation_activate').click();},3000); </script>";
		}
	    if (trial_period() <= 7) {
	    	if (trial_period() == 7) {
	    		$count = __('last', 'floris');
	    	}else{
	    		$count = 7-trial_period();
	    	}
	    	$popup_content = __('Dear customer, thank you for using Floris theme! Please enter purchase code to register your copy. <br/><b>'.$count.' day(s)</b>  trial period left. <br/><p align="center"><a href="https://www.youtube.com/watch?v=nzBQf3nnJA8" target="_blank">how to obtain purchase code?</a></p><br/><p style="color:red;">Please note, all settings will be reset to default after trial period expiration!</p>','floris');
	    }else{
	    	$popup_content = __('Dear customer, the trial period has  expired. Please register to proceed using Floris theme. <br/><p align="center"><a href="https://www.youtube.com/watch?v=nzBQf3nnJA8" target="_blank">how to obtain purchase code?</a></p><br/><p style="color:red;">Please note, all settings will be reset to default after trial period expiration!</p>','floris');
	    }
	    if (get_admin_page_title() == 'Theme Options' && !get_option( 'enable_full_version' )) {
	    	echo 	'<div class="popup-license" data-remodal-id="popup_license" role="dialog" aria-labelledby="modal1Title" aria-describedby="modal1Desc">
					  <button data-remodal-action="close" class="remodal-close" aria-label="Close"></button>
					  <div>
					    <h2 id="modal1Title">Theme registration</h2>
					    <p id="modal1Desc">'.
					      $popup_content
					    .'</p>
					  </div>
					  <br>
					  <button data-remodal-action="confirm" class="remodal-confirm">Register now</button>
					  <button data-remodal-action="cancel" class="remodal-cancel">Remind me later</button>
					</div>';
			echo '<script type="text/javascript" src="'. FLORIS_THEME_DIRURI . 'assets/js/remodal.js"></script>';
	    	echo "<script> var inst = jQuery('[data-remodal-id=popup_license]').remodal(); setTimeout(function(){ inst.open(); }, 2500); </script>";
	    }
	}
}

add_action( 'wp_ajax_floris_theme_verification', 'floris_theme_verification' );

function floris_theme_verification() {
	if($_POST['purchase_code'] !== floris_get_option( 'purchase_code_verification')){
		echo __('Could you save the changes at first.', 'floris');
	}else{
		if (function_exists('curl_version')) {
			$code_to_verify = floris_get_option( 'purchase_code_verification'); 
			$verify = $_POST['verify']; 
			$path = $_SERVER['HTTP_HOST'];
			$agent = $_SERVER['HTTP_USER_AGENT'];
			$email = wp_get_current_user()->data->user_email;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://verify.azelab.com/index.php?p_code='.$code_to_verify.'&path='.$path.'&email='.$email.'&removed_status='.$verify.'&agent='.$agent);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output = json_decode(curl_exec($ch), true);
			curl_close($ch);
			if (is_null($output['result'])) {
				$content = __('Something wrong. Could you try register the purchase code later.', 'floris');
			}elseif ($output['result'] == 'access_success') {
				$content = __('Dear '.$output['user'].', the theme was successfully activated. Thank you.', 'floris');
				update_option( 'enable_full_version', 1);
			}elseif($output['result'] == 'access_denied'){
				if ($output['reason'] == 'wrong_p_code') {
					$content = __('The purcahase code is wrong.', 'floris');
					update_option( 'enable_full_version', 0);
				}elseif ($output['reason'] == 'code_registered') {
					$content = __('The purchase code already has been registered. Could you deregister purchase code on the another domain and try again.', 'floris');
					update_option( 'enable_full_version', 0);
				}elseif ($output['reason'] == 'db_error') {
					$content = __('Something wrong. Could you try register the purchase code later.', 'floris');
				}
			}elseif($output['result'] == 'remove_success'){
				$content = __('Dear '.$output['user'].', the theme was successfully deactivated. Thank you.', 'floris');
				update_option( 'enable_full_version', 0);
			}
		}else{
			$content = __('Please enable Curl on your hosting server. It is necessary for license verification.', 'floris');
		}
		echo $content;
	}
	die();
}

function trial_period(){
	$datetime1 = new DateTime(get_option( 'trial_period' ));
    $datetime2 = new DateTime(date("Y-m-d"));
    $interval = $datetime1->diff($datetime2);
    return $interval->d;
}