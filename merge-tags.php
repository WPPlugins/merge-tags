<?php
/*
Plugin Name: Merge Tags
Version: 1.1.2
Description: Allows you to combine tags and categories from the tag editing interface
Tags: admin, category, management, tag, term, taxonomy
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/wordpress/merge-tags
Text Domain: merge-tags
Domain Path: /lang

Copyright (C) 2009 scribu.net (scribu AT gmail DOT com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// Init
Merge_Tags::init();

class Merge_Tags {

	function init() {
		add_action('load-edit-tags.php', array(__CLASS__, 'handler'));
		add_action('admin_notices', array(__CLASS__, 'notice'));
		add_action('admin_enqueue_scripts', array(__CLASS__, 'script'));
		add_action('add_tag_form_pre', array(__CLASS__, 'ui'));

		load_plugin_textdomain('merge-tags', '', basename(dirname(__FILE__)) . '/lang');
	}

	function handler() {
		if ( !current_user_can('manage_categories') )
			return;

		if ( $_POST['action'] == 'merge-tag' ) {
			check_admin_referer('merge-tag');
	
			$from_ids = self::_get_term_ids_from_string($_POST['from-tags']);
			$term = $_POST['to-tag'];
		} else {
			$from_ids = $_REQUEST['delete_tags'];
	
			foreach ( array('', '2') as $a )
				if ( $_REQUEST['action' . $a] == 'bulk-merge-tag')
					$term = $_REQUEST['bulk_to_tag' . $a];
	
			if ( $term )
				check_admin_referer('bulk-tags');
		}
	
		if ( empty($from_ids) || empty($term) )
			return;
	
		$location = 'edit-tags.php';
		if ( $referer = wp_get_referer() ) {
			if ( false !== strpos($referer, 'edit-tags.php') )
				$location = $referer;
		}
	
		$taxonomy = $_REQUEST['taxonomy'];
	
		$term = wp_insert_term($term, $taxonomy);
	
		if ( is_wp_error($term) ) {
			wp_redirect(add_query_arg('message', 70, $location));
			exit;
		}

		$term_id = $term['term_id'];

		foreach ( $from_ids as $from_id ) {
			if ( $from_id == $term_id )
				continue;
	
			$ret = wp_delete_term($from_id, $taxonomy, array('default' => $term_id, 'force_default' => true));
	
			if ( is_wp_error($ret) ) {
				wp_redirect(add_query_arg('message', 70, $location));
				exit;
			}
		}
	
		wp_redirect(add_query_arg('message', 80, $location));
		exit;
	}
	
	function notice() {
		global $pagenow, $messages;
		
		if ( 'edit-tags.php' != $pagenow )
			return;
	
		$messages[70] = __('Tags not merged.', 'merge-tags');
		$messages[80] = __('Tags merged.', 'merge-tags');
	}

	function script() {
		global $pagenow;
	
		if ( 'edit-tags.php' != $pagenow )
			return;
	
		$js_dev = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

		wp_enqueue_script('merge-tags', plugins_url("script$js_dev.js",__FILE__), array('suggest'), '1.1.1');

		wp_localize_script('merge-tags', 'mergeTagsL10n', array(
			'action' => esc_attr__('Merge', 'merge-tags'),
			'to_tag' => __('To tag', 'merge-tags'),
			'taxonomy' => strip_tags($_GET['taxonomy'])
		));
	}
	
	function ui() {
		global $taxonomy;
	?>
<div class="form-wrap">
<h3><?php _e('Merge Tags', 'merge-tags'); ?></h3>

<form id="mergetag" method="post" action="edit-tags.php" class="validate">
<input type="hidden" name="action" value="merge-tag" />
<input type="hidden" name="taxonomy" value="<?php echo esc_attr($taxonomy); ?>" />
<?php wp_nonce_field('merge-tag'); ?>

<div class="form-field">
	<label for="from-tags"><?php _e('From tag(s)', 'merge-tags'); ?></label>
	<input id="from-tags" type="text" size="40" name="from-tags"/>
	<p><?php _e('One or more tags to be merged', 'merge-tags'); ?></p>
</div>

<div class="form-field">
	<label for="to-tag"><?php _e('To tag', 'merge-tags'); ?></label>
	<input id="to-tag" type="text" size="40" name="to-tag"/>
	<p><?php _e('The resulting tag', 'merge-tags'); ?></p>
</div>

<p class="submit">
	<input class="button" type="submit" value="<?php esc_attr_e('Merge', 'merge-tags'); ?>" name="submit"/>
</p>
</form>
</div>
	<?php
	}
	
	function _get_term_ids_from_string($string) {
		global $wpdb;
	
		$list = self::_array_to_sql(explode(',', $string));
	
		return $wpdb->get_col("SELECT term_id FROM $wpdb->terms WHERE name IN ($list)");
	}

	function _array_to_sql($array) {
		$result = array();
		foreach ( $array as $item )
		{
			$item = trim($item);
			if ( empty($item) )
				continue;
	
			$result[] = "'" . esc_sql(trim($item)) . "'";
		}
	
		$result = array_unique($result);
		return implode(',', $result);
	}
}

