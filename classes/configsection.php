<?php

class PeepSowpForoFreeConfigSection extends PeepSoConfigSectionAbstract
{
	/************************************************************************
	 * Add config section
	 ************************************************************************/
	public function register_config_groups()
	{
		$this->context='left';
		$this->general();
		$this->go_pro();
		
		$this->context='right';
		$this->actions();
		$this->uninstall();
	}
	
	private function general()
	{		
		// Cutting preview text
		$this->args('validation', array('numeric'));
		$this->args('descript', __('Number of characters to show before cutting text in profiles and activities','qnso-ps-wpf'));
		$this->set_field('wpforo_replies_char_cut', __('Character to show', 'qnso-ps-wpf'), 'text');
		
		$this->set_group('peepso_wpforo_general',	__('General', 'qnso-ps-wpf'));
	}
	
	private function go_pro()
	{		
		$this->set_field('go_pro_message', sprintf('<strong>%s:</strong><ul><li>- %s</li><li>- %s</li><li>- %s</li><li>- %s</li><li>- %s</li><li>- %s</li><li>- %s</li></ul>', __('All Premium Features', 'qnso-ps-wpf'), __('Redirect forum profiles to PeepSo profiles', 'qnso-ps-wpf'), __('Show forum stats of members inside their PeepSo profile', 'qnso-ps-wpf'), __('Use PeepSo avatars inside your forum', 'qnso-ps-wpf'), __('Member can change their forum notification settings inside their PeepSo profiles', 'qnso-ps-wpf'), __('Fully integrates subscription settings in your members profiles', 'qnso-ps-wpf'), __('Forum related links can be shown in the navigation of PeepSo', 'qnso-ps-wpf'), __('Show the PeepSo toolbar in forum pages to make your forum and PeepSo one big community', 'qnso-ps-wpf')), 'message');
		$this->set_field('go_pro_button', '<a target="_blank" style="text-decoration: none; font-weight: bold; color: #fff;" href="https://quenso.de/plugin/peepso-wpforo-integration-pro/"><div style="margin: 0px 20px; text-transform: uppercase; display: inline-block; border: none; background-color: #d24842; padding: 15px 32px; text-align: center; font-size: 16px;">Go PRO now!</div></a>', 'message');
		
		$this->set_group('peepso_asgarosforum_go_pro', __('Premium Features', 'qnso-ps-wpf'), __('Get the PRO version to use all features of this integration', 'qnso-ps-wpf'));
	}
	
	private function actions()
	{
		// New post notification
		$this->args('descript', __('Send notifications to the author and subscribers if someone replies to a topic.','qnso-ps-wpf'));
		$this->set_field('wpforo_topic_new_post_notification', __('New post notifications', 'qnso-ps-wpf'), 'yesno_switch');
		
		// New post in activity stream
		$this->args('descript', __('Create an activity if someone replies to a topic.','qnso-ps-wpf'));
		$this->set_field('wpforo_topic_new_post_to_stream', __('New post activity', 'qnso-ps-wpf'), 'yesno_switch');
		
		// New post in activity stream content
		$this->args('descript', __('Show content of the new post in activity.','qnso-ps-wpf'));
		$this->set_field('wpforo_topic_new_post_to_stream_content', __('New post activity shows content', 'qnso-ps-wpf'), 'yesno_switch');
		
		// New topic notification
		$this->args('descript', __('Send notifications to subscribers if someone creates a new topic.','qnso-ps-wpf'));
		$this->set_field('wpforo_new_topic_notification', __('New topic notifications', 'qnso-ps-wpf'), 'yesno_switch');
		
		// New topic in activity stream
		$this->args('descript', __('Create an activity if someone creates a new topic.','qnso-ps-wpf'));
		$this->set_field('wpforo_new_topic_to_stream', __('New topic activity', 'qnso-ps-wpf'), 'yesno_switch');
		
		// New topic in activity stream content
		$this->args('descript', __('Show content of the first topic post in activity.','qnso-ps-wpf'));
		$this->set_field('wpforo_new_topic_to_stream_content', __('New topic activity shows content', 'qnso-ps-wpf'), 'yesno_switch');
		
		$this->set_group('peepso_wpforo_actions',	__('Activity stream and notifications', 'qnso-ps-wpf'));
	}
	
	private function uninstall()
	{
		// Enable PeepSo toolbar
		$this->args('descript', __('Clean up all wpForo activities, notifications and config settings in PeepSo on deactivation. </br><b><font color="red">Be careful!</b> All data will be deleted after deactivation!</font>','qnso-ps-wpf'));
        $this->set_field('wpforo_delete_on_deactivate', __('Clean up PeepSo', 'qnso-ps-wpf'), 'yesno_switch');
		
		$this->set_group('peepso_wpforo_uninstall',	__('Uninstall', 'qnso-ps-wpf'));
	}
}