<?php
namespace PressForward\Core\Admin;


class EditPost {

	function __construct() {
		$this->post_type = 'post';
		//Modify the Singleton Edit page.
		add_action( 'post_submitbox_misc_actions', array( $this, 'posted_submitbox_pf_actions' ) );
		add_action( 'save_post', array( $this, 'save_submitbox_pf_actions' ) );

	}

	function posted_submitbox_pf_actions(){
		global $post;
		$check = pressforward('controller.metas')->get_post_pf_meta($post->ID, 'item_link', true);
		if ( empty($check) ){
			return;
		}
		$value = pressforward('controller.metas')->get_post_pf_meta($post->ID, 'pf_forward_to_origin', true);
		if ( empty($value) ){

			$option_value = get_option('pf_link_to_source');
				if ( empty($option_value) ){
					$value = 'no-forward';
				} else {
					$value = 'forward';
				}
		}

		echo '<div class="misc-pub-section misc-pub-section-last">
				<label>
				<select id="pf_forward_to_origin_single" name="pf_forward_to_origin">
				  <option value="forward"'.( 'forward' == $value ? ' selected ' : '') .'>Forward</option>
				  <option value="no-forward"'.( 'no-forward' == $value ? ' selected ' : '') .'>Don\'t Forward</option>
				</select><br />
				to item\'s original URL</label></div>';
	}

	function save_submitbox_pf_actions( $post_id )
	{
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){ return $post_id; }
		if ( !current_user_can( 'edit_page', $post_id ) ){ return $post_id; }
		#var_dump($_POST['pf_forward_to_origin']); die();
		#$current = pressforward('controller.metas')->get_post_pf_meta();
			if ( !array_key_exists('pf_forward_to_origin', $_POST) ) {

			} else {
				pressforward('controller.metas')->update_pf_meta($post_id, 'pf_forward_to_origin', $_POST['pf_forward_to_origin']);
			}

		return $post_id;
	}


}
