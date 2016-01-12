<?php

function get_the_source_title($id = false){
	#$st = pressforward()->metas->retrieve_meta(get_the_ID(), 'source_title');
	if (!$id){
		$id = get_the_ID();
	}
	if ( empty($id) ){
		throw new Exception("get_source_title could not find an ID.", 1);
		return;
	}
	$parent_id = get_post_ancestors($id);
	if (empty($parent_id[0])){ return __('Bookmarklet', 'pf'); }
	$parent = get_post($parent_id[0]);
	if ( empty($parent) ){
		pf_log("get_source_title could not find a post object checking with the ID of ".$parent_id[0]);
		return __('Unknown Feed', 'pf');
	}
	$st = $parent->post_title;
	return $st;
}

function the_source_title(){
	echo get_the_source_title();
}

function get_the_original_post_date(){
	$opd = pressforward()->metas->retrieve_meta(get_the_ID(), 'item_date');
	return $opd;
}

function the_original_post_date(){
	echo get_the_original_post_date();
}

function get_the_item_author($id = false){
	if (!$id){
		$id = get_the_ID();
	}
	$ia = pressforward()->metas->retrieve_meta($id, 'item_author');
	return $ia;
}

function the_item_author(){
	echo get_the_item_author();
}

function get_the_item_link($id = false){
	if ( !$id ){
		$id = get_the_ID();
	}
	$m = pressforward()->metas->retrieve_meta($id, 'item_link');
	return $m;
}

function the_item_link(){
	echo get_the_item_link();
}

function get_the_item_feat_image(){
	$m = pressforward()->metas->retrieve_meta(get_the_ID(), 'item_feat_img');
	return $m;
}

function the_item_feat_image(){
	echo get_the_item_feat_image();
}

function get_the_item_tags(){
	$m = pressforward()->metas->retrieve_meta(get_the_ID(), 'item_tags');
	return $m;
}

function the_item_tags(){
	echo get_the_item_tags();
}

function get_the_repeats(){
	$m = pressforward()->metas->retrieve_meta(get_the_ID(), 'source_repeat');
	return $m;
}

function the_item_repeats(){
	echo get_the_repeats();
}

function get_the_nomination_count(){
	$m = pressforward()->metas->retrieve_meta(get_the_ID(), 'nomination_count');
	return $m;
}

function the_nomination_count(){
	echo get_the_item_tags();
}

function get_the_nominator_ids(){
	$m = pressforward()->metas->retrieve_meta(get_the_ID(), 'nominator_array', false, false);
	return $m;
}

function get_the_nominators(){
	#var_dump(get_the_nominators());
	$nominators = get_the_nominator_ids();
	if (is_array($nominators)){
		$nomers = '';
		$lastElement = end($nominators);
		$lastKey = key($nominators);
		foreach ($nominators as $k => $nomer){
			if (is_array($nomer)){
				$nomers .= implode("," , $nomer);
			} else {
				$nomers .= $nomer;
			}
			if ($lastKey != $k){
				$nomers .= ',';
			}
		}
		#$nomers = implode(", " , get_the_nominators());
	} else {
		$nomers = get_the_nominator_ids();
	}

	# Look, there are a lot of weird things that can happen
	# depending on how far back your version history has gone
	# So this is a stupid way to do it, but it is really the
	# best way.

	$nominating_user_ids = array_filter( explode( ",", $nomers ) );
	$nominating_users = array();
	foreach ($nominating_user_ids as $user_id){
		$user_obj = get_user_by('id', $user_id);
		$nominating_users[] = $user_obj->display_name;
	}

	return $nominating_users;

}

function get_the_nominating_users(){
	$nominating_users = get_the_nominators();
	$imp = implode(', ', $nominating_users);
	return $imp;

}

function the_nominators(){
	echo get_the_nominating_users();
}

function get_the_word_count(){
	$m = pressforward()->metas->retrieve_meta(get_the_ID(), 'pf_feed_item_word_count');
	return $m;
}

function the_word_count(){
	echo get_the_word_count();
}

function the_pf_comments( $id_for_comments = 0 ){

		if ( 0 == $id_for_comments ){
			$id_for_comments = get_the_ID();
		}
		$item_post_id = get_post_meta($id_for_comments, 'pf_item_post_id', true);
		if ( $item_post_id ){
			$id_for_comments = $item_post_id;
		}
	?>
	<ul id="ef-comments">
		<?php
		$comments = new PF_Comments;
					$editorial_comments = $comments->ef_get_comments_plus (
						array(
							'post_id' => $id_for_comments,
							'comment_type' => 'pressforward-comment',
							'orderby' => 'comment_date',
							'order' => 'ASC',
							'status' => 'pressforward-comment'
						)
					);
			// We use this so we can take advantage of threading and such

			wp_list_comments(
				array(
					'type' => 'pressforward-comment',
					'callback' => array($comments, 'the_comment'),
					'end-callback' => '__return_false'
				),
				$editorial_comments
			);
		?>
	</ul>
	<?php
}
