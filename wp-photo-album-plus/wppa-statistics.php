<?php
/* wppa-statistics.php
* Package: wp-photo-album-plus
*
* Functions for counts etc
* Common use front and admin
* Version 6.6.07
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

// show system statistics
function wppa_statistics() {

	wppa_out( wppa_get_statistics() );
}
function wppa_get_statistics() {

	$count = wppa_get_total_album_count();
	$y_id = wppa_get_youngest_album_id();
	$y_name = __(wppa_get_album_name($y_id), 'wp-photo-album-plus');
	$p_id = wppa_get_parentalbumid($y_id);
	$p_name = __(wppa_get_album_name($p_id), 'wp-photo-album-plus');

	$result = '<div class="wppa-box wppa-nav" style="text-align: center; '.__wcs('wppa-box').__wcs('wppa-nav').'">';
	$result .= sprintf( _n( 'There is %d photo album', 'There are %d photo albums', $count, 'wp-photo-album-plus'), $count );
	$result .= ' '.__('The last album added is', 'wp-photo-album-plus').' ';
	$result .= '<a href="'.wppa_get_permalink().'wppa-album='.$y_id.'&amp;wppa-cover=0&amp;wppa-occur=1">'.$y_name.'</a>';

	if ($p_id > '0') {
		$result .= __(', a subalbum of', 'wp-photo-album-plus').' ';
		$result .= '<a href="'.wppa_get_permalink().'wppa-album='.$p_id.'&amp;wppa-cover=0&amp;wppa-occur=1">'.$p_name.'</a>';
	}

	$result .= '.</div>';

	return $result;
}

// get number of photos in album
function wppa_get_photo_count( $id = '0', $use_treecounts = false ) {
global $wpdb;

	if ( $use_treecounts && $id ) {
		$treecounts = wppa_treecount_a( $id );
		if ( current_user_can('wppa_moderate') ) {
			$count = $treecounts['selfphotos'] + $treecounts['pendphotos'] + $treecounts['scheduledphotos'];
		}
		else {
			$count = $treecounts['selfphotos'];
		}
	}
	elseif ( ! $id ) {
		if ( current_user_can('wppa_moderate') ) {
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` " );
		}
		else {
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE ( ( `status` <> 'pending' AND `status` <> 'scheduled' ) OR `owner` = %s )", wppa_get_user() ) );
		}
	}
	else {
		if ( current_user_can('wppa_moderate') ) {
			$count = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM " . WPPA_PHOTOS . " WHERE album = %s", $id ) );
		}
		else {
			$count = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM " . WPPA_PHOTOS .
				" WHERE `album` = %s AND ( ( `status` <> 'pending' AND `status` <> 'scheduled' ) OR owner = %s )",
				$id, wppa_get_user() ) );
		}
	}

	// Substract private photos if not logged in and album given
	if ( $id && ! is_user_logged_in() ) {
		$count -= $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `album` = %s AND `status` = 'private' ", $id ) );
	}
	return $count;
}

// get number of albums in album
function wppa_get_album_count( $id, $use_treecounts = false ) {
global $wpdb;

	if ( $use_treecounts && $id ) {
		$treecounts = wppa_treecount_a( $id );
		$count = $treecounts['selfalbums'];
	}
	else {
		$count = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM " . WPPA_ALBUMS . " WHERE a_parent=%s", $id ) );
	}
    return $count;
}

// get number of albums in system
function wppa_get_total_album_count() {
global $wpdb;
static $count;

	if ( ! $count ) {
		$count = $wpdb->get_var("SELECT COUNT(*) FROM `".WPPA_ALBUMS."`");
	}

	return $count;
}

// Get the number of albums the user can upload to
// @: array containing album numbers that are in the pool
function wppa_get_uploadable_album_count( $alb = false ) {
global $wpdb;

	// If album array given, prepare partial where clause to limit album ids.
	if ( is_array( $alb ) ) {
		$where = " `id` IN (" . implode( ',', $alb ) . ") ";
		$where = trim( $where, ',' );
	}
	else {
		$where = false;
	}

	// Admin, do not look to owner
	if ( wppa_user_is( 'administrator' ) ) {
		$result = $wpdb->get_var( 	"SELECT COUNT(*) " .
									"FROM `" . WPPA_ALBUMS . "` " .
									( $where ? "WHERE " . $where : "" )
								);
	}

	// Owner or public
	elseif ( wppa_switch( 'upload_owner_only' ) ) {
		$result = $wpdb->get_var( $wpdb->prepare( 	"SELECT COUNT(*) " .
													"FROM `" . WPPA_ALBUMS . "` " .
													"WHERE `owner` = '--- public ---' OR `owner` = %s" .
													( $where ? "AND " . $where : "" ),
													wppa_get_user()
												)
								);
	}

	// No upload owners only
	else {
		$result = $wpdb->get_var( 	"SELECT COUNT(*) " .
									"FROM `" . WPPA_ALBUMS . "` " .
									( $where ? "WHERE " . $where : "" )
								);
	}

	// Done!
	return $result;
}

// get youngest photo id
function wppa_get_youngest_photo_id() {
global $wpdb;

	$result = $wpdb->get_var(
		"SELECT `id` FROM `" . WPPA_PHOTOS .
		"` WHERE `status` <> 'pending' AND `status` <> 'scheduled' ORDER BY `timestamp` DESC, `id` DESC LIMIT 1" );

	return $result;
}

// get n youngest photo ids
function wppa_get_youngest_photo_ids( $n = '3' ) {
global $wpdb;

	if ( ! wppa_is_int( $n ) ) $n = '3';
	$result = $wpdb->get_col(
		"SELECT `id` FROM `" . WPPA_PHOTOS .
		"` WHERE `status` <> 'pending' AND `status` <> 'scheduled' ORDER BY `timestamp` DESC, `id` DESC LIMIT ".$n );

	return $result;
}

// get youngest album id
function wppa_get_youngest_album_id() {
global $wpdb;

	$result = $wpdb->get_var( "SELECT `id` FROM `" . WPPA_ALBUMS . "` ORDER BY `timestamp` DESC, `id` DESC LIMIT 1" );

	return $result;
}

// get youngest album name
function wppa_get_youngest_album_name() {
global $wpdb;

	$result = $wpdb->get_var( "SELECT `name` FROM `" . WPPA_ALBUMS . "` ORDER BY `timestamp` DESC, `id` DESC LIMIT 1" );

	return stripslashes($result);
}

// Bump Clivkcount
function wppa_bump_clickcount( $id ) {
global $wpdb;
global $wppa_session;

	// Feature enabled?
	if ( ! wppa_switch( 'track_clickcounts' ) ) {
		return;
	}

	// Sanitize input
	if ( ! wppa_is_int( $id ) || $id < '1' ) {
		return;
	}

	// Init clicks in session?
	if ( ! isset ( $wppa_session['click'] ) ) {
		$wppa_session['click'] = array();
	}

	// Remember click and update photodata, only if first time
	if ( ! isset( $wppa_session['click'][$id] ) ) {
		$wppa_session['click'][$id] = true;
		$count = $wpdb->get_var( "SELECT `clicks` FROM `" . WPPA_PHOTOS . "` WHERE `id` = $id" );
		$count++;
		$wpdb->query( "UPDATE `" . WPPA_PHOTOS . "` SET `clicks` = $count WHERE `id` = $id" );

		// Invalidate cache
		wppa_cache_photo( 'invalidate', $id );
	}
}

// Bump Viewcount
function wppa_bump_viewcount($type, $id) {
global $wpdb;
global $wppa_session;

	if ( ! wppa_switch( 'track_viewcounts') ) return;

	if ( $type != 'album' && $type != 'photo' ) die ( 'Illegal $type in wppa_bump_viewcount: '.$type);
	if ( $type == 'album' ) {
		if ( strlen( $id ) == 12 ) {
			$id = wppa_decrypt_album( $id );
		}
	}
	else {
		if ( strlen( $id ) == 12 ) {
			$id = wppa_decrypt_photo( $id );
		}
	}

	if ( $id < '1' ) return;			// Not a wppa image
	if ( ! wppa_is_int( $id ) ) return;	// Not an integer

	if ( ! isset($wppa_session[$type]) ) {
		$wppa_session[$type] = array();
	}
	if ( ! isset($wppa_session[$type][$id] ) ) {	// This one not done yest
		$wppa_session[$type][$id] = true;			// Mark as viewed
		if ( $type == 'album' ) $table = WPPA_ALBUMS; else $table = WPPA_PHOTOS;

		$count = $wpdb->get_var("SELECT `views` FROM `".$table."` WHERE `id` = ".$id);
		$count++;

		$wpdb->query("UPDATE `".$table."` SET `views` = ".$count." WHERE `id` = ".$id);
		wppa_dbg_msg('Bumped viewcount for '.$type.' '.$id.' to '.$count, 'red');

		// If 'wppa_owner_to_name'
		if ( $type == 'photo' ) {
			wppa_set_owner_to_name( $id );
		}
	}

	wppa_save_session();
}

function wppa_get_upldr_cache() {

	$result = get_option( 'wppa_upldr_cache', array() );

	return $result;
}

function wppa_flush_upldr_cache( $key = '', $id = '' ) {

	$upldrcache	= wppa_get_upldr_cache();

	foreach ( array_keys( $upldrcache ) as $widget_id ) {

		switch ( $key ) {

			case 'widgetid':
				if ( $id == $widget_id ) {
					unset ( $upldrcache[$widget_id] );
				}

			case 'photoid':
				$usr = wppa_get_photo_item( $id, 'owner');
				if ( isset ( $upldrcache[$widget_id][$usr] ) ) {
					unset ( $upldrcache[$widget_id][$usr] );
				}
				break;

			case 'username':
				$usr = $id;
				if ( isset ( $upldrcache[$widget_id][$usr] ) ) {
					unset ( $upldrcache[$widget_id][$usr] );
				}
				break;

			case 'all':
				$upldrcache = array();
				break;

			default:
				wppa_dbg_msg('Missing key in wppa_flush_upldr_cache()', 'red');
				break;
		}
	}
	update_option('wppa_upldr_cache', $upldrcache);
}

function wppa_get_random_photo_id_from_youngest_album() {
global $wpdb;

	$albums = $wpdb->get_col( "SELECT `id` FROM `" . WPPA_ALBUMS . "` ORDER BY `timestamp` DESC" );
	$found 	= false;
	$count 	= count( $albums );
	$idx 	= 0;
	$result = false;

	while ( ! $found && $idx < $count ) {
		$album = $albums[$idx];
		$result = $wpdb->get_var( "SELECT `id` FROM `" . WPPA_PHOTOS ."` WHERE `album` = $album ORDER BY RAND() LIMIT 1" );
		if ( $result ) {
			$found = true;
		}
		$idx++;
	}

	return $result;
}