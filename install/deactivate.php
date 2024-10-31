<?php

/*
 * Performs deactivation process
 */
class PeepSowpForoFreeUninstall
{
	/************************************************************************
	 * Called when plugin is deactivated
	 ************************************************************************/
	public static function plugin_deactivation()
	{
		// Only uninstall if option is enabled
		if (1 === intval(PeepSo::get_option('wpforo_delete_on_deactivate', 0))) {
			self::remove_activities();
			self::remove_notifications();

			// the is done last, in case above processes require any settings
			self::remove_settings();
		}
	}


	/************************************************************************
	 * Remove wpForo activities in PeepSo
	 ************************************************************************/
	private static function remove_activities()
	{
		global $wpdb;	
		
		$MODULE_ID = PeepSowpForo::MODULE_ID;
		
		$sql = "DELETE FROM `{$wpdb->prefix}peepso_activities` WHERE `act_MODULE_ID` = {$MODULE_ID} OR `act_comment_MODULE_ID` = {$MODULE_ID}";
		$wpdb->query($wpdb->prepare($sql));
	}
	
	/************************************************************************
	 * Remove wpForo notifications in PeepSo
	 ************************************************************************/
	private static function remove_notifications()
	{
		global $wpdb;	
		
		$MODULE_ID = PeepSowpForo::MODULE_ID;
		
		$sql = "DELETE FROM `{$wpdb->prefix}peepso_notifications` WHERE `not_MODULE_ID` = {$MODULE_ID}";
		$wpdb->query($wpdb->prepare($sql));
	}
	
	
	/************************************************************************
	 * Removes all configuration settings for wpForo in PeepSo
	 ************************************************************************/
	private static function remove_settings()
	{
		// remove settings
		$settings = PeepSoConfigSettings::get_instance();
		$options = array(
			'wpforo_add_toolbar', 'wpforo_add_navigation_forum', 'wpforo_add_navigation_subscriptions', 'wpforo_replies_char_cut',
			'wpforo_modify_profile_url', 'wpforo_change_avatars', 'wpforo_add_profile_navigation', 'wpforo_posts_per_page',
			'wpforo_topic_new_post_notification', 'wpforo_topic_new_post_to_stream', 'wpforo_topic_new_post_to_stream_content', 'wpforo_new_topic_notification', 'wpforo_new_topic_to_stream', 'wpforo_new_topic_to_stream_content', 'wpforo_like_mention_notification',
			'wpforo_delete_on_deactivate'
		);
		
		$settings->remove_option($options);
	}

}

// EOF
