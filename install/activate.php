<?php
require_once(PeepSo::get_plugin_dir() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'install.php');

class PeepSowpForoFreeInstall extends PeepSoInstall
{

	/************************************************************************
	 * Set default settings
	 ************************************************************************/
	protected $default_config = array(
	);

	public function plugin_activation( $is_core = FALSE )
	{
		$settings = PeepSoConfigSettings::get_instance();
		$settings->set_option('wpforo_replies_char_cut', 600);
		$settings->set_option('wpforo_topic_new_post_notification', 1);
		$settings->set_option('wpforo_topic_new_post_to_stream', 1);
		$settings->set_option('wpforo_topic_new_post_to_stream_content', 0);
		$settings->set_option('wpforo_new_topic_notification', 1);
		$settings->set_option('wpforo_new_topic_to_stream', 1);
		$settings->set_option('wpforo_new_topic_to_stream_content', 0);
		$settings->set_option('wpforo_delete_on_deactivate', 0);

		parent::plugin_activation($is_core);

		return (TRUE);
	}	
}