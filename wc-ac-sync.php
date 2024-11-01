<?php
/*
Plugin Name: SYNC AC with WP 
Plugin URI: https://tooltips.org/
Description: Integrates Wordpress with ActiveCampaign by adding new users information to  contact on ActiveCampaign, when a new user registered.
Version: 1.3.1
Author: Tomas Zhu
Author URI: https://tooltips.org/
Text Domain: SYNCWPTOAC
License: GPL3
*/
/*  Copyright 2016 - 2022 Tomas Zhu
 This program comes with ABSOLUTELY NO WARRANTY;
 https://www.gnu.org/licenses/gpl-3.0.html
 https://www.gnu.org/licenses/quick-guide-gplv3.html
 */
if (!defined('ABSPATH')) exit();

function tomas_register_status_change($user_id) 
{
    $disablewptoacentiresite = get_option('disablewptoacentiresite');
    if ($disablewptoacentiresite == 'NO')
    {
        return;
    }
    
	if (empty($user_id))
	{
		return;
	}

	$user_info = get_userdata($user_id);
	$first_name = $user_info->first_name;
	$last_name = $user_info->last_name;
	$user_email = $user_info->user_email;

	$ac_list_id = get_option('ac_list_id');
	$ac_url = get_option('ac_url');
	$ac_api_key = get_option('ac_api_key');

	$ac_default_tag = array(
			'title'             => __( 'Default Tag', 'SYNCWPTOAC' ),
			'type'              => 'text',
			'description'       => __( 'The default tags will always be added after user registered.', 'SYNCWPTOAC' ),
			'desc_tip'          => true
	);

	$default_tags = implode(',',array_map('trim', explode(',', $ac_default_tag)));

	require_once(dirname(__FILE__).'/activecampaign-api-php/includes/ActiveCampaign.class.php');

	$connect_wptoac_sync = new ActiveCampaign($ac_url, $ac_api_key);
	
	if (!(int)$connect_wptoac_sync->credentials_test()) 
	{
		return;
	}

	$contact = array(
			'email' 				=> $user_email,
			'first_name'			=> $first_name,
			'last_name' 			=> $last_name,
			"p[{$ac_list_id}]"      => $ac_list_id,
			"status[{$ac_list_id}]" => 1,
			'tags' 					=> $default_tags
	);
	$contact_sync = $connect_wptoac_sync->api('contact/sync', $contact);
}

function tomas_wp_ac_option_menu()
{
	add_menu_page(__('WP TO AC', 'SYNCWPTOAC'), __('WP TO AC', 'SYNCWPTOAC'), 'manage_options', 'wptoacmenu', 'tomas_wptoac_setting');
	add_submenu_page('wptoacmenu', __('WP TO AC','SYNCWPTOAC'), __('WP TO AC','SYNCWPTOAC'), 'manage_options', 'wptoacmenu');
}

add_action('user_register', 'tomas_register_status_change', 10, 1);
add_action('admin_menu', 'tomas_wp_ac_option_menu');

function tomas_wptoac_setting()
{
	global $wpdb;
	$ac_list_id = get_option('ac_list_id');
	$ac_url = get_option('ac_url');
	$ac_api_key = get_option('ac_api_key');

	if (isset($_POST['syncwptoacsubmitnew']))
	{
		check_admin_referer( 'tomas_insert_ac_sync_wp' );
		if (isset($_POST['ac_list_id']))
		{
			$ac_list_id = sanitize_text_field($_POST['ac_list_id']);
		}

		update_option('ac_list_id',$ac_list_id);
		
		if (isset($_POST['ac_url']))
		{
			$ac_url = sanitize_text_field($_POST['ac_url']);
		}
		
		update_option('ac_url',$ac_url);
		

		if (isset($_POST['ac_api_key']))
		{
			$ac_api_key = sanitize_text_field($_POST['ac_api_key']);
		}
		
		update_option('ac_api_key',$ac_api_key);

		
		if (isset($_POST['disablewptoacentiresite']))
		{
		    $disablewptoacentiresite = sanitize_text_field($_POST['disablewptoacentiresite']);
		    update_option('disablewptoacentiresite',$disablewptoacentiresite);
		}
		$disablewptoacentiresite = get_option('disablewptoacentiresite');
		
		$syncwptoacMessageString =  __( 'Your changes has been saved.', 'SYNCWPTOAC' );
		tomas_wp_to_ac_message($syncwptoacMessageString);
	}
	echo "<br />";

	$saved_register_page_url = get_option('syncwptoacregisterpageurl');
	?>

<div style='margin:10px 5px;'>
<div style='padding-top:5px; font-size:22px;'> </>WP TO AC Setting:</div>
</div>
<div style='clear:both'></div>		
		<div class="wrap">
			<div id="dashboard-widgets-wrap">
			    <div id="dashboard-widgets" class="metabox-holder">
					<div id="post-body">
						<div id="dashboard-widgets-main-content">
							<div class="postbox-container" style="width:90%;">
								<div class="postbox">
									<h3 class='hndle' style='margin:20px 20px;'><span>
									<?php 
											echo  __( 'Option Panel:', 'SYNCWPTOAC' );
									?>
									</span>
									</h3>
								
									<div class="inside" style='padding-left:10px;'>
										<form id="syncwptoacform" name="syncwptoacform" action="" method="POST">
										<table id="syncwptoactable" width="100%">
										<tr>
										<td width="30%" style="padding: 20px;">
										<?php 
											echo  __( 'AC LIST ID:', 'SYNCWPTOAC' );
										?>
										</td>
										<td width="70%" style="padding: 20px;">
										<input type="text" id="syncwptoacregisterpageurl" name="ac_list_id"  style="width:500px;" size="70" value="<?php  echo $ac_list_id; ?>">
										</td>
										</tr>
										
										<tr>
										<td width="30%" style="padding: 20px;">
										<?php 
											echo  __( 'AC URL:', 'SYNCWPTOAC' );
										?>
										</td>
										<td width="70%" style="padding: 20px;">
										<input type="text" id="syncwptoacregisterpageurl" name="ac_url"  style="width:500px;" size="70" value="<?php  echo $ac_url; ?>">
										</td>
										</tr>
										
										<tr>
										<td width="30%" style="padding: 20px;">
										<?php 
											echo  __( 'AC API Key:', 'SYNCWPTOAC' );
										?>
										</td>
										<td width="70%" style="padding: 20px;">
										<input type="text" id="syncwptoacregisterpageurl" name="ac_api_key"  style="width:500px;" size="70" value="<?php  echo $ac_api_key; ?>">
										</td>
										</tr>										
										
										<tr>
										<td width="30%" style="padding: 20px;">
										<?php 
											echo  __( 'Enable / Disable Sync WP with AC:', 'SYNCWPTOAC' );
											$disablewptoacentiresite = get_option('disablewptoacentiresite');
											
										?>
										</td>
										<td width="70%" style="padding: 20px;">
										<select id="disablewptoacentiresite" name="disablewptoacentiresite" style="width:400px;">
										<option id="selectdisablewptoacentiresiteoption" value="YES" <?php if ($disablewptoacentiresite == 'YES') echo "selected";   ?>> <?php echo __( 'Sync WP to AC in My Site', 'wordpress-tooltips' ); ?> </option>
										<option id="selectdisablewptoacentiresiteoption" value="NO" <?php if ($disablewptoacentiresite == 'NO') echo "selected";   ?>>   <?php echo __( 'Stop Sync WP to AC in My Site', 'wordpress-tooltips' ); ?> </option>
										</select> 
										</td>
										</tr>										
																				
										</table>
										<br />
										<?php wp_nonce_field('tomas_insert_ac_sync_wp'); ?>
										<input type="submit" id="syncwptoacsubmitnew" name="syncwptoacsubmitnew" value=" Submit " style="margin:1px 20px;">
										</form>
										
										<br />
									</div>
								</div>
							</div>
						</div>
					</div>
		    	</div>
			</div>
		</div>
		<div style="clear:both"></div>
		<br />
		
<?php
}				

function tomas_wp_to_ac_message($p_message)
{
			echo "<div id='message' class='updated fade' style='line-height: 30px;margin-left: 0px;'>";
			echo $p_message;
			echo "</div>";
}
?>