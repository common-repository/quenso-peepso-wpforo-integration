<?php
/*
 * Plugin Name: PeepSo - wpForo Integration by Quenso
 * Plugin URI: https://quenso.de/plugin/peepso-wpforo-integration/
 * Description: Integrate wpForo in your PeepSo community.
 * Author: Quenso
 * Author URI: https://quenso.de
 * Version: 1.0.4
 * Copyright: (c) 2020 Marcel Hellmund, All Rights Reserved.
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: qnso-ps-wpf
 * Domain Path: /language
 *
 * We are Open Source. You can redistribute and/or modify this software under the terms of the GNU General Public License (version 2 or later)
 * as published by the Free Software Foundation. See the GNU General Public License or the LICENSE file for more details.
 * This software is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
 */
if (!defined('ABSPATH')) exit;

define('PEEPSOWPFOROFREE_FILE', __FILE__);

class PeepSowpForoFree
{
	private static $_instance = NULL;
	
	// Define plugin data
	const PLUGIN_NAME				= 'PeepSo - wpForo Integration';
	const PLUGIN_VERSION			= '1.0.4';
	const PLUGIN_RELEASE			= ''; //ALPHA1, BETA1, RC1, '' for STABLE
	
	// Define PeepSo compatibility
	const READY_VERSION				= '2.7.8';
	const READY_RELEASE				= ''; //ALPHA1, BETA1, RC1, '' for STABLE
	const MODULE_ID					= 10547;
	
	// Define post meta
    const POST_META					= 'peepsowpforo_type';
    const POST_META_POST			= 'peepsowpforo_type_post';
	const POST_META_POST_URL		= 'peepsowpforo_type_post_url';
    const POST_META_TOPIC			= 'peepsowpforo_type_topic';
	const POST_META_PARENT_ID		= 'peepsowpforo_parent_id';
	
	private function __construct()
	{		
		// Admin
        if (is_admin()) {
			add_action('admin_init', array(&$this, 'check_required'));
			add_action('admin_init', array(&$this, 'pro'));
			
			// Register config tab in PeepSo config
			add_filter('peepso_admin_config_tabs', array(&$this, 'config_tab'));
        } else {
            add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
        }
		
		if (self::ready()) {
			//Register hook for Activation / Deactivation
			register_activation_hook(__FILE__, array(&$this, 'activate'));
			register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));
			
			add_action('peepso_init', array(&$this, 'init'));
			add_action('plugins_loaded', array(&$this, 'load_textdomain'));
		}
	}

	/************************************************************************
	 * Load Translations
	 ************************************************************************/
	public function load_textdomain()
    {
        $path = str_ireplace(WP_PLUGIN_DIR, '', dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR;
        load_plugin_textdomain('qnso-ps-wpf', FALSE, $path);
    }
	
	/************************************************************************
	 * Check for ready status
	 ************************************************************************/
    public static function ready()
	{
		if (class_exists('PeepSo') && self::READY_VERSION.self::READY_RELEASE < PeepSo::PLUGIN_VERSION.PeepSo::PLUGIN_RELEASE){
			add_action('admin_notices', function(){
				?>
				<div class="notice notice-warning qnso-ps-wpf">
					<strong>
						<?php echo sprintf(__('The version of %1$s plugin hasn&rsquo;t been testet with actual version of PeepSo and can cause errors. Please check for updates on %1$s.', 'qnso-ps-wpf'), self::PLUGIN_NAME, PeepSo::PLUGIN_VERSION.PeepSo::PLUGIN_RELEASE);?>
					</strong><br/><?php echo self::PLUGIN_NAME?><small style="opacity:0.5">(<?php echo sprintf(__('last testet: %s', 'qnso-ps-wpf'), self::READY_VERSION); ?>)</small>, <?php echo PeepSo::PLUGIN_NAME?><small style="opacity:0.5">(<?php echo PeepSo::PLUGIN_VERSION.PeepSo::PLUGIN_RELEASE ?>)</small>
				</div>
				<?php
            });
		}
		
		if (!class_exists('PeepSo') || !class_exists('wpForo')) {
			return FALSE;
		}
		
		return TRUE;		
    }
	
	/************************************************************************
	 * Make sure required plugins are activated
	 ************************************************************************/
	public function check_required ()
	{
		if (!class_exists('PeepSo') || !class_exists('wpForo')) {

			add_action('admin_notices', function(){
                ?>
                <div class="error peepso">
                    <strong>
                        <?php echo sprintf(__('The %s plugin requires the PeepSo and wpForo plugins to be installed and activated.', 'qnso-ps-wpf'), self::PLUGIN_NAME);?>
                    </strong>
                </div>
                <?php
            });

			unset($_GET['activate']);
			deactivate_plugins(plugin_basename(__FILE__));
			return FALSE;
		}
		
		return TRUE;
	}		

	public static function get_instance()
	{
		if (NULL === self::$_instance) {
			self::$_instance = new self();
		}
		return (self::$_instance);
	}
	
		public function pro ()
	{
		add_action( 'plugin_action_links_'. plugin_basename( __FILE__ ), function($settings) {
			$admin_anchor = sprintf ('<a target="_blank" href="https://quenso.de/plugin/peepso-wpforo-integration-pro/" title="%s" style="font-weight:bold;color:darkred;">Go Pro</a>', __('Get PRO version of this plugin', 'qnso-ps-wpf'));
			if (! is_array( $settings  )) {
				return array( $admin_anchor );
			} else {
				return array_merge( array( $admin_anchor ), $settings) ;
			}
		});
	}

	public function init()
	{
		// Autoload classes
		$classes_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR;
		foreach (new DirectoryIterator(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes') as $file) {
			if ($file->isDot()) continue;
				
			require_once($classes_path . $file->getFilename());
		}
		
		PeepSoTemplate::add_template_directory(plugin_dir_path(__FILE__));
			
		// Activity stream and notifications
		add_action('wpforo_after_add_post', array(&$this, 'topic_new_post'), 10, 2);
		add_action('wpforo_after_add_topic', array(&$this, 'new_topic'), 10, 1);
		add_action('wpforo_after_delete_post', function ($post) { self::delete_post_data($post['postid']); });
		add_action('wpforo_after_delete_topic', function ($topic) { self::delete_topic_data($topic['topicid']); });
		add_action('wpforo_topic_status_update', array(&$this, 'status_topic'), 10, 2);
		add_action('wpforo_post_status_update', array(&$this, 'status_post'), 10, 2);
		add_action('peepso_profile_notification_link', array(&$this, 'filter_profile_notification_link'), 10, 2);
		add_filter('peepso_activity_stream_action', array(&$this, 'activity_stream_action'), 10, 2);
		add_filter('peepso_profile_alerts', array(&$this, 'profile_alerts'), 10, 1);
	}

	/************************************************************************
	 * Enqueue assets
	 ************************************************************************/
	public function enqueue_scripts()
	{
		wp_enqueue_style('peepsowpforo', plugin_dir_url(__FILE__) . 'assets/css/style.css', array('peepso'), self::READY_VERSION, 'all');
    }
	
	/************************************************************************
	 * Plugin Activation and Deactivation
	 ************************************************************************/
	public function activate()
	{
		if (!self::ready()) {
            return (FALSE);
        }

        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'activate.php');
        $install = new PeepSowpForoFreeInstall();
        $res = $install->plugin_activation();
        if (FALSE === $res) {
            // error during installation - disable
            deactivate_plugins(plugin_basename(__FILE__));
        }
		
        return (TRUE);
	}
	
	public function deactivate()
	{
		require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'deactivate.php');
		PeepSowpForoFreeUninstall::plugin_deactivation();
	}
	
	/************************************************************************
	 * Create admin configuration tab
	 ************************************************************************/
	public function config_tab ($tabs) 
	{
		$tabs['wpforo'] = array(
			'label' => 'wpForo Integration',
			'icon' => plugin_dir_url(__FILE__) . 'assets/img/wpf-icon.png',
			'tab' => 'wpforo',
			'function' => 'PeepSowpForoFreeConfigSection',
		);

		return $tabs;
	}
	
	/************************************************************************
	 * Send notification to topic author and subscribers for new post 
	 * Create activity item for new post
	 ************************************************************************/
	public function topic_new_post($post, $topic)
    {
		if( !$post['status'] && !$post['private']){
			// Create notifications
			if(1 == PeepSo::get_option('wpforo_topic_new_post_notification',1)) {
				$PeepSoNotifications = new PeepSoNotifications();
				
				// Send notification to topic author
				$title = sprintf(__('replied to your topic: %s', 'qnso-ps-wpf'), $topic['title']);
				
				// Don't send notification if post auhor is topic author
				if (get_current_user_id() != $topic['userid']) {				
					$PeepSoNotifications->add_notification($post['userid'], $topic['userid'], $title, 'wpforo_topic_new_post', self::MODULE_ID, $post['postid']);
				}
				
				// Send notification to topic subscribers
				global $wpdb;
				
				$topic_sub_ids = $wpdb->get_col("SELECT userid FROM {$wpdb->prefix}wpforo_subscribes WHERE itemid = {$topic['topicid']} AND type = 'topic'");
				$forums_sub_ids = $wpdb->get_col("SELECT userid FROM {$wpdb->prefix}wpforo_subscribes WHERE (itemid = {$post['forumid']} OR itemid = 0) AND type = 'forums'");
				$forums_topics_sub_ids = $wpdb->get_col("SELECT userid FROM {$wpdb->prefix}wpforo_subscribes WHERE (itemid = {$post['forumid']} OR itemid = 0) AND type = 'forums-topics'");
				
				$sub_ids = array_unique(array_merge($topic_sub_ids, $forums_sub_ids, $forums_topics_sub_ids), SORT_REGULAR);
				foreach ($sub_ids as $sub_id) {
					$title = sprintf(__('replied to a subscribed topic: %s', 'qnso-ps-wpf'), $topic['title']);
					
					// Don't send notification if subscriber is post or topic author
					if (get_current_user_id() != $sub_id && $sub_id != $topic['userid']) {
						$PeepSoNotifications->add_notification($post['userid'], $sub_id, $title, 'wpforo_sub_topic_new_post', self::MODULE_ID, $post['postid']);
					}
				}				
			}

			// Create activity item
			if(1 == PeepSo::get_option('wpforo_topic_new_post_to_stream',1)) {
				$this->parent_id = $post['postid'];
				add_filter('peepso_activity_allow_empty_content', array(&$this, 'activity_allow_empty_content'), 10, 1);
				
				// Show new post content
				$content = $post['body'];
				if (strlen($post['body']) > PeepSo::get_option('wpforo_replies_char_cut')) {
					$content = substr($post['body'], 0, PeepSo::get_option('wpforo_replies_char_cut')) . '...';
				}
				if(0 == PeepSo::get_option('wpforo_topic_new_post_to_stream_content',0)) {
					$content = '';
				}
				
				$extra = array(
					'module_id' => self::MODULE_ID,
					'act_access' => PeepSo::ACCESS_PUBLIC,
					'post_date_gmt' => date( 'Y-m-d H:i:s', current_time( 'timestamp', 1 ))
				);
				
				$peepso_activity = PeepSoActivity::get_instance();
				$act_id = $peepso_activity->add_post(get_current_user_id(), get_current_user_id(), $content, $extra);
				// Add post meta
				add_post_meta($act_id, self::POST_META, self::POST_META_POST, true);
				add_post_meta($act_id, self::POST_META_PARENT_ID, $post['postid']);
				add_post_meta($act_id, self::POST_META_POST_URL, $post['posturl']);
				
				remove_filter('peepso_activity_allow_empty_content', array(&$this, 'activity_allow_empty_content'));
			}
		}
	}
	
	/************************************************************************
	 * Send notification to subscribers for new topic
	 * Create activity item for new topic
	 ************************************************************************/
	public function new_topic($topic)
    {
		if( !$topic['status'] && !$topic['private']){
			$PeepSoNotifications = new PeepSoNotifications();
			
			// Get first post
			$post = WPF()->post->get_post($topic['first_postid']);
			
			// Create notifications
			if(1 == PeepSo::get_option('wpforo_new_topic_notification',1)) {
				// Send notification to topic subscribers
				global $wpdb;
				
				$forums_sub_ids = $wpdb->get_col("SELECT userid FROM {$wpdb->prefix}wpforo_subscribes WHERE (itemid = {$topic['forumid']} OR itemid = 0) AND type = 'forums'");
				$forums_topics_sub_ids = $wpdb->get_col("SELECT userid FROM {$wpdb->prefix}wpforo_subscribes WHERE (itemid = {$topic['forumid']} OR itemid = 0) AND type = 'forums-topics'");
				
				$sub_ids = array_unique(array_merge($forums_sub_ids, $forums_topics_sub_ids), SORT_REGULAR);
				foreach ($sub_ids as $sub_id) {
					$title = sprintf(__('created a topic in a subscribed forum: %s', 'qnso-ps-wpf'), $topic['title']);
					
					// Don't send notification if subscriber is topic author
					if (get_current_user_id() != $sub_id) {
						$PeepSoNotifications->add_notification($topic['userid'], $sub_id, $title, 'wpforo_sub_new_topic', self::MODULE_ID, $post['postid']);
					}
				}				
			}

			// Create activity item
			if(1 == PeepSo::get_option('wpforo_new_topic_to_stream',1)) {
				$this->parent_id = $topic['topicid'];
				add_filter('peepso_activity_allow_empty_content', array(&$this, 'activity_allow_empty_content'), 10, 1);
				
				// Show first post content
				$content = $post['body'];
				if (strlen($post['body']) > PeepSo::get_option('wpforo_replies_char_cut')) {
					$content = substr($post['body'], 0, PeepSo::get_option('wpforo_replies_char_cut')) . '...';
				}
				if(0 == PeepSo::get_option('wpforo_new_topic_to_stream_content',0)) {
					$content = '';
				}
				
				$extra = array(
					'module_id' => self::MODULE_ID,
					'act_access' => PeepSo::ACCESS_PUBLIC,
					'post_date_gmt' => date( 'Y-m-d H:i:s', current_time( 'timestamp', 1 ))
				);
				
				$peepso_activity = PeepSoActivity::get_instance();
				$act_id = $peepso_activity->add_post($topic['userid'], $topic['userid'], $content, $extra);
				// Add post meta
				add_post_meta($act_id, self::POST_META, self::POST_META_TOPIC, true);
				add_post_meta($act_id, self::POST_META_PARENT_ID, $topic['first_postid']);
				add_post_meta($act_id, self::POST_META_POST_URL, $topic['topicurl']);
				
				remove_filter('peepso_activity_allow_empty_content', array(&$this, 'activity_allow_empty_content'));
			}
		}
	}
	
	/************************************************************************
	 * Create / delete activity item for topic based on status
	 ************************************************************************/
	public function status_topic ($topicid, $status)
	{
		$topic = WPF()->topic->get_topic($topicid);
		$topic['status'] = $status;
        $topic['topicurl'] = WPF()->topic->get_topic_url($topicid);
		
		if ($status) {
			self::delete_topic_data($topicid);
		} else {			
			self::new_topic($topic);
		}
	}
	
	/************************************************************************
	 * Create / delete activity item for post based on status
	 ************************************************************************/
	public function status_post ($postid, $status)
	{
		$post = WPF()->post->get_post($postid);
		$post['status'] = $status;
        $post['posturl'] = WPF()->post->get_post_url($postid);
		$topic = WPF()->post->get_post($post['topicid']);
		
		if ($status) {
			self::delete_post_data($postid);
		} else {			
			self::topic_new_post($post, $topic);
		}
	}
	
	/************************************************************************
	 * Delete activities and notifications for post
	 ************************************************************************/
	public function delete_post_data ($forum_post_id)
	{
		global $wpdb;
		// Delete post notification
		$wpdb->delete($wpdb->prefix . PeepSoNotifications::TABLE, array('not_external_id' => $forum_post_id));
		// Delete post activity
		$post_id = $wpdb->get_var("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '" . self::POST_META_PARENT_ID . "' AND meta_value = {$forum_post_id}");
		$wpdb->delete($wpdb->prefix . PeepSoActivity::TABLE_NAME, array('act_external_id' => $post_id));
		wp_delete_post($post_id, true);
	}
	
	/************************************************************************
	 * Delete activities and notifications for topics
	 ************************************************************************/
	public function delete_topic_data ($forum_topic_id)
	{
		$posts = WPF()->post->get_posts(array('topicid' => $forum_topic_id));
		
		foreach ($posts as $post) {
			$forum_post_id = $post['postid'];
			self::delete_post_data ($forum_post_id);
		}
	}
	
	/************************************************************************
     * Allow empty activity item content
     ************************************************************************/
    public function activity_allow_empty_content($allowed)
    {
        if(isset($this->parent_id)) {
            $allowed = TRUE;
        }

        return ($allowed);
    }
	
	/************************************************************************
	 * Change activity stream actions
	 ************************************************************************/
	public function activity_stream_action($action, $post)
    {
        if (self::MODULE_ID === intval($post->act_module_id)) {
            $post_type = get_post_meta($post->ID, self::POST_META, true);
			
			$forum_post_id = get_post_meta($post->ID, self::POST_META_PARENT_ID, true);
			$forum_post_link = get_post_meta($post->ID, self::POST_META_POST_URL, true);
			
			global $wpdb;
			
			$forum_topic_id = $wpdb->get_var("SELECT topicid FROM {$wpdb->prefix}wpforo_posts WHERE postid = {$forum_post_id}");
			$forum_topic_name = $wpdb->get_var("SELECT title FROM {$wpdb->prefix}wpforo_topics WHERE topicid = {$forum_topic_id}");
			// If type is new forum post
            if($post_type === self::POST_META_POST) {
                $action = sprintf(__('replied to a topic: <a href="%1$s">%2$s</a>', 'qnso-ps-wpf'), $forum_post_link, $forum_topic_name);
            }
			// If type is new topic
			else if($post_type === self::POST_META_TOPIC) {
				$action = sprintf(__('created a new topic: <a href="%1$s">%2$s</a>', 'qnso-ps-wpf'), $forum_post_link, $forum_topic_name);
            }
		// Check for reposted	
        } else if (FALSE === empty($post->act_repost_id)) {
            $peepso_activity = PeepSoActivity::get_instance();
            $repost_act = $peepso_activity->get_activity($post->act_repost_id);

            // fix "Trying to get property of non-object" errors
            if (is_object($repost_act) && self::MODULE_ID === intval($repost_act->act_module_id)) {
                $action = __('shared a forum activity', 'qnso-ps-wpf');
            }
        }
		
		return ($action);
    }
	
	/************************************************************************
	 * Add profile alerts
	 ************************************************************************/
	public function profile_alerts($alerts)
    {
		$items_topic_new_post = array(
			array(
				'label' => __('Someone replies to your topic', 'qnso-ps-wpf'),
				'setting' => 'wpforo_topic_new_post',
				'loading' => TRUE,
			),
			array(
				'label' => __('Someone replies to a subscribed topic', 'qnso-ps-wpf'),
				'setting' => 'wpforo_sub_topic_new_post',
				'loading' => TRUE,
			));
	
		$items_new_topic = array(
			array(
				'label' => __('Someone creates a topic in a subscribed forum', 'qnso-ps-wpf'),
				'setting' => 'wpforo_sub_new_topic',
				'loading' => TRUE,
			));
		
		if (0 == PeepSo::get_option('wpforo_topic_new_post_notification',1) && 0 == PeepSo::get_option('wpforo_new_topic_notification',1)){
			return ($alerts);
		} elseif (0 == PeepSo::get_option('wpforo_topic_new_post_notification',1)) {
			$alerts['wpforo'] = array(
                'title' => __('Forum', 'qnso-ps-wpf'),
                'items' => $items_new_topic,
			);
		} elseif (0 == PeepSo::get_option('wpforo_new_topic_notification',1)) {
			$alerts['wpforo'] = array(
                'title' => __('Forum', 'qnso-ps-wpf'),
                'items' => $items_topic_new_post,
			);
		} else {
			$alerts['wpforo'] = array(
                'title' => __('Forum', 'qnso-ps-wpf'),
                'items' => array_merge($items_topic_new_post, $items_new_topic),
			);
		}
		
        return ($alerts);
    }
	
	/************************************************************************
	 * Change notification links to forum post links
	 ************************************************************************/
	public function filter_profile_notification_link($link, $note_data)
    {
        $not_types = array(
            'wpforo_topic_new_post',
			'wpforo_sub_topic_new_post',
			'wpforo_sub_new_topic',
			'wpforo_like_mention',
        );

		global $wpdb;
		
		$forum_post_id = $note_data['not_external_id'];
		$post_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d", self::POST_META_PARENT_ID, $forum_post_id));
		$not_type = $note_data['not_type'];

        if (in_array($not_type, $not_types)) {
            $link = get_post_meta($post_id, self::POST_META_POST_URL, true);
        }
        return $link;
    }
}

PeepSowpForoFree::get_instance();

// EOF