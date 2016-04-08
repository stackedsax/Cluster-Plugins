<?php
// Display the style configuration page
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

function mc_get_style_path( $filename = false, $type = 'path' ) {
	$url = plugin_dir_url( __FILE__ );
	$dir = plugin_dir_path( __FILE__ );
	if ( !$filename ) {
		$filename = get_option( 'mc_css_file' );
	}
	if ( strpos( $filename, 'mc_custom_' ) === 0 ) {
		$filename  = str_replace( 'mc_custom_', '', $filename );
		$stylefile = ( $type == 'path' ) ? str_replace( '/my-calendar/', '', $dir ) . '/my-calendar-custom/styles/' . $filename : str_replace( '/my-calendar/', '', $url ) . '/my-calendar-custom/styles/' . $filename;
	} else {
		$stylefile = ( $type == 'path' ) ? dirname( __FILE__ ) . '/styles/' . $filename : plugins_url( 'styles', __FILE__ ) . '/' . $filename;
	}
	if ( $type == 'path' ) {
		if ( is_file( $stylefile ) ) {
			return $stylefile;
		} else {
			return false;
		}
	} else {
		return $stylefile;
	}
}

function mc_is_custom_style( $filename ) {
	if ( strpos( $filename, 'mc_custom_' ) === 0 ) {
		return true;
	} else {
		return false;
	}
}

function mc_default_style( $filename = false, $return = 'content' ) {
	if ( ! $filename ) {
		$mc_css_file = get_option( 'mc_css_file' );
	} else {
		$mc_css_file = $filename;
	}
	$mc_current_file = dirname( __FILE__ ) . '/templates/' . $mc_css_file;
	if ( file_exists( $mc_current_file ) ) {
		$f                = fopen( $mc_current_file, 'r' );
		$file             = fread( $f, filesize( $mc_current_file ) );
		$mc_current_style = $file;
		fclose( $f );
		switch ( $return ) {
			case 'content':
				return $mc_current_style;
				break;
			case 'path':
				return $mc_current_file;
				break;
			case 'both':
				return array( $mc_current_file, $mc_current_style );
				break;
		}
	}

	return '';
}

function mc_write_styles( $stylefile, $my_calendar_style ) {
	if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT == true ) {
		return false;
	}
	
	$standard        = dirname( __FILE__ ) . '/styles/';
	$files = my_csslist( $standard );
	foreach ( $files as $file ) {
		$filepath = mc_get_style_path( $file );
		$path = pathinfo( $filepath );
		if ( $path['extension'] == 'css' ) {
			$styles_whitelist[] = $filepath;
		}
	}
	
	if ( in_array( $stylefile, $styles_whitelist ) ) {
		if ( function_exists( 'wp_is_writable' ) ) {
			$is_writable = wp_is_writable( $stylefile );
		} else {
			$is_writable = is_writeable( $stylefile );
		}
		if ( $is_writable ) {
			$f = fopen( $stylefile, 'w+' );
			fwrite( $f, $my_calendar_style ); // number of bytes to write, max.
			fclose( $f );

			return true;
		} else {
			return false;
		}
	}
	return false;
}

function edit_my_calendar_styles() {
	$edit_files = true;
	if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT == true ) {
		$edit_files = false;
		echo "<div class='my-calendar-notice updated error'><p>" . __( 'File editing is disallowed in your WordPress installation. Edit your stylesheets offline.', 'my-calendar' ) . "</p></div>";
	}
	$dir = plugin_dir_path( __FILE__ );
	if ( isset( $_POST['mc_edit_style'] ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			die( "Security check failed" );
		}
		$my_calendar_style = ( isset( $_POST['style'] ) ) ? stripcslashes( $_POST['style'] ) : false;
		$mc_css_file       = stripcslashes( $_POST['mc_css_file'] );

		if ( $edit_files ) {
			$stylefile    = mc_get_style_path( $mc_css_file );
			$wrote_styles = ( $my_calendar_style !== false ) ? mc_write_styles( $stylefile, $my_calendar_style ) : 'disabled';
		} else {
			$wrote_styles = false;
		}

		if ( $wrote_styles === 'disabled' ) {
			$message = "<p>" . __( "Styles are disabled, and were not edited.", 'my-calendar' ) . "</p>";
		} else {
			$message = ( $wrote_styles == true ) ? '<p>' . __( 'The stylesheet has been updated.', 'my-calendar' ) . '</p>' : '<p><strong>' . __( 'Write Error! Please verify write permissions on the style file.', 'my-calendar' ) . '</strong></p>';
		}

		$mc_show_css = ( empty( $_POST['mc_show_css'] ) ) ? '' : stripcslashes( $_POST['mc_show_css'] );
		update_option( 'mc_show_css', $mc_show_css );
		$use_styles = ( empty( $_POST['use_styles'] ) ) ? '' : 'true';
		update_option( 'mc_use_styles', $use_styles );

		if ( ! empty( $_POST['reset_styles'] ) ) {
			$stylefile        = mc_get_style_path();
			$styles           = mc_default_style();
			$wrote_old_styles = mc_write_styles( $stylefile, $styles );
			if ( $wrote_old_styles ) {
				$message .= "<p>" . __( 'Stylesheet reset to default.', 'my-calendar' ) . "</p>";
			}
		}
		$message .= "<p><strong>" . __( 'Style Settings Saved', 'my-calendar' ) . ".</strong></p>";
		echo "<div id='message' class='updated fade'>$message</div>";
	}
	if ( isset( $_POST['mc_choose_style'] ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			die( "Security check failed" );
		}
		$mc_css_file = stripcslashes( $_POST['mc_css_file'] );

		update_option( 'mc_css_file', $mc_css_file );
		$message = '<p><strong>' . __( 'New theme selected.', 'my-calendar' ) . '</strong></p>';
		echo "<div id='message' class='updated fade'>$message</div>";
	}

	$mc_show_css = get_option( 'mc_show_css' );
	$stylefile   = mc_get_style_path();
	if ( $stylefile ) {
		$f                 = fopen( $stylefile, 'r' );
		$file              = fread( $f, filesize( $stylefile ) );
		$my_calendar_style = $file;
		fclose( $f );
		$mc_current_style = mc_default_style();
	} else {
		$mc_current_style  = '';
		$my_calendar_style = __( 'Sorry. The file you are looking for doesn\'t appear to exist. Please check your file name and location!', 'my-calendar' );
	}
	?>
	<div class="wrap jd-my-calendar">
	<?php my_calendar_check_db(); ?>
	<h1><?php _e( 'My Calendar Styles', 'my-calendar' ); ?></h1>
	<div class="postbox-container jcd-wide">
		<div class="metabox-holder">
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h2><?php _e( 'Calendar Style Settings', 'my-calendar' ); ?></h2>

					<div class="inside">

						<form method="post" action="<?php echo admin_url( "admin.php?page=my-calendar-styles" ); ?>">
							<div><input type="hidden" name="_wpnonce"
							            value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/></div>
							<div><input type="hidden" value="true" name="mc_choose_style"/></div>
							<?php
								$custom_directory = str_replace( '/my-calendar/', '', $dir ) . '/my-calendar-custom/styles/';
								$directory        = dirname( __FILE__ ) . '/styles/';

								$files = @my_csslist( $custom_directory );
							?>
							<fieldset>
								<p>
									<label
										for="mc_css_file"><?php _e( 'Select My Calendar Theme', 'my-calendar' ); ?></label>
									<select name="mc_css_file" id="mc_css_file"><?php
										if ( ! empty( $files ) ) {
											echo "<optgroup label='" . __( 'Your Custom Stylesheets', 'my-calendar' ) . "'>\n";
											foreach ( $files as $value ) {
												$test     = "mc_custom_" . $value;												
												$filepath = mc_get_style_path( $test );
												$path = pathinfo( $filepath );
												if ( $path['extension'] == 'css' ) {											
													$selected = ( get_option( 'mc_css_file' ) == $test ) ? " selected='selected'" : "";
													echo "<option value='mc_custom_$value'$selected>$value</option>\n";
												}
											}
											echo "</optgroup>";
										}
										$files = my_csslist( $directory );
										echo "<optgroup label='" . __( 'Installed Stylesheets', 'my-calendar' ) . "'>\n";
										foreach ( $files as $value ) {
											$filepath = mc_get_style_path( $value );
											$path = pathinfo( $filepath );
											if ( $path['extension'] == 'css' ) {
												$selected = ( get_option( 'mc_css_file' ) == $value ) ? " selected='selected'" : "";
												echo "<option value='$value'$selected>$value</option>\n";
											}
										}
										echo "</optgroup>"; ?>
									</select>
									<input type="submit" name="save" class="button-secondary"
									       value="<?php _e( 'Choose Style', 'my-calendar' ); ?>"/>
								</p>
							</fieldset>
						</form>
						<hr/>
						<form method="post" action="<?php echo admin_url( "admin.php?page=my-calendar-styles" ); ?>">
							<div><input type="hidden" name="_wpnonce"
							            value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/></div>
							<div><input type="hidden" value="true" name="mc_edit_style"/>
								<input type="hidden" name="mc_css_file"
								       value="<?php esc_attr_e( get_option( 'mc_css_file' ) ); ?>"/>
							</div>
							<fieldset style="position:relative;">
								<legend><?php _e( 'CSS Style Options', 'my-calendar' ); ?></legend>
								<p>
									<label
										for="mc_show_css"><?php _e( 'Apply CSS on these pages (comma separated IDs)', 'my-calendar' ); ?></label>
									<input type="text" id="mc_show_css" name="mc_show_css"
									       value="<?php esc_attr_e( $mc_show_css ); ?>"/>
								</p>

								<p>
									<input type="checkbox" id="reset_styles"
									       name="reset_styles" <?php if ( mc_is_custom_style( get_option( 'mc_css_file' ) ) ) {
										echo "disabled='disabled'";
									} ?> /> <label
										for="reset_styles"><?php _e( 'Reset to default', 'my-calendar' ); ?></label>
									<input type="checkbox" id="use_styles"
									       name="use_styles" <?php mc_is_checked( 'mc_use_styles', 'true' ); ?> />
									<label
										for="use_styles"><?php _e( 'Disable My Calendar Stylesheet', 'my-calendar' ); ?></label>
								</p>
								<p>						
								<?php if ( mc_is_custom_style( get_option( 'mc_css_file' ) ) ) {
									_e( 'The editor is not available for custom CSS files. Edit your custom CSS locally, then upload your changes.', 'my-calendar' );
								} else {
									$disabled = ( $edit_files ) ? '' : ' disabled="disabled"';
								?>
									<label
										for="style"><?php _e( 'Edit the stylesheet for My Calendar', 'my-calendar' ); ?></label><br/><textarea <?php echo $disabled; ?> 
										class="style-editor" id="style" name="style" rows="30"
										cols="80"<?php if ( get_option( 'mc_use_styles' ) == 'true' ) {
										echo "disabled='disabled'";
									} ?>><?php echo $my_calendar_style; ?></textarea>

								<?php } ?>
								</p>
								<p>
									<input type="submit" name="save" class="button-primary button-adjust"
									       value="<?php _e( 'Save Changes', 'my-calendar' ); ?>"/>
								</p>
							</fieldset>
						</form>
						<?php
						$left_string  = normalize_whitespace( $my_calendar_style );
						$right_string = normalize_whitespace( $mc_current_style );
						if ( $right_string ) { // if right string is blank, there is no default
							if ( isset( $_GET['diff'] ) ) {
								echo '<div class="wrap jd-my-calendar" id="diff">';
								echo mc_text_diff( $left_string, $right_string, array( 
										'title'       => __( 'Comparing Your Style with latest installed version of My Calendar', 'my-calendar' ),
										'title_right' => __( 'Latest (from plugin)', 'my-calendar' ),
										'title_left'  => __( 'Current (in use)', 'my-calendar' )
									) );
								echo '</div>';
							} else if ( trim( $left_string ) != trim( $right_string ) ) {
								echo '<div class="wrap jd-my-calendar">';
								echo '<div class="updated"><p>' . __( 'There have been updates to the stylesheet.', 'my-calendar' ) . ' <a href="' . admin_url( "admin.php?page=my-calendar-styles&amp;diff#diff" ) . '">' . __( 'Compare Your Stylesheet with latest installed version of My Calendar.', 'my-calendar' ) . '</a></p></div>';
								echo '</div>';
							} else {
								echo '
						<div class="wrap jd-my-calendar">
							<p>' . __( 'Your stylesheet matches that included with My Calendar.', 'my-calendar' ) . '</p>
						</div>';
							}
						} ?>
					</div>
				</div>
				<p><?php _e( 'Resetting your stylesheet will set your stylesheet to the version currently distributed with the plug-in.', 'my-calendar' ); ?></p>
			</div>
		</div>
	</div>
	<?php mc_show_sidebar(); ?>
	</div><?php
}
 
	// fixed wp_text_diff
function mc_text_diff( $left_string, $right_string, $args = null ) {
	$defaults = array( 'title' => '', 'title_left' => '', 'title_right' => '' );
	$args = wp_parse_args( $args, $defaults );

	if ( !class_exists( 'WP_Text_Diff_Renderer_Table' ) )
		require( ABSPATH . WPINC . '/wp-diff.php' );

	$left_string  = normalize_whitespace($left_string);
	$right_string = normalize_whitespace($right_string);

	$left_lines  = explode("\n", $left_string);
	$right_lines = explode("\n", $right_string);
	$text_diff = new Text_Diff($left_lines, $right_lines);
	$renderer  = new WP_Text_Diff_Renderer_Table( $args );
	$diff = $renderer->render($text_diff);
	$r = '';
	
	if ( !$diff )
		return '';
	if ( $args['title'] ) {
		$r .= "<h2>$args[title]</h2>\n";
	}
	
	$r  .= "<table class='diff'>\n";
	$r  .= "<col class='content diffsplit left' /><col class='content diffsplit middle' /><col class='content diffsplit right' />";


	if ( $args['title'] || $args['title_left'] || $args['title_right'] )
		$r .= "<thead>";

	if ( $args['title_left'] || $args['title_right'] ) {
		$r .= "<tr class='diff-sub-title'>\n";
		$r .= "\t<th scope='col'>$args[title_left]</th><td></td>\n";
		$r .= "\t<th scope='col'>$args[title_right]</th>\n";
		$r .= "</tr>\n";
	}
	if ( $args['title'] || $args['title_left'] || $args['title_right'] )
		$r .= "</thead>\n";

	$r .= "<tbody>\n$diff\n</tbody>\n";
	$r .= "</table>";

	return $r;
}