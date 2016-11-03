<?php
/**
 * Class A_Custom_Lightbox_Form
 * @mixin C_Form
 * @adapts I_Form using "custom_lightbox" context
 */
class A_Custom_Lightbox_Form extends Mixin
{
    function get_model()
    {
        return C_Lightbox_Library_Manager::get_instance()->get('custom_lightbox');
    }
    /**
     * Returns a list of fields to render on the settings page
     */
    function _get_field_names()
    {
        return array('lightbox_library_code', 'lightbox_library_styles', 'lightbox_library_scripts');
    }
    /**
     * @param $lightbox
     * @return mixed
     */
    function _render_lightbox_library_code_field($lightbox)
    {
        return $this->_render_text_field($lightbox, 'code', __('Code', 'nggallery'), $lightbox->code);
    }
    /**
     * @param $lightbox
     * @return mixed
     */
    function _render_lightbox_library_styles_field($lightbox)
    {
        return $this->_render_textarea_field($lightbox, 'styles', __('Stylesheet URL', 'nggallery'), implode("\n", $lightbox->styles));
    }
    /**
     * @param $lightbox
     * @return mixed
     */
    function _render_lightbox_library_scripts_field($lightbox)
    {
        return $this->_render_textarea_field($lightbox, 'scripts', __('Javascript URL', 'nggallery'), implode("\n", $lightbox->scripts));
    }
    function _convert_to_urls($input)
    {
        $retval = array();
        $urls = explode("\n", $input);
        foreach ($urls as $url) {
            if (strpos($url, home_url()) === 0) {
                $url = str_replace(home_url(), '', $url);
            } elseif (strpos($url, 'http') === 0) {
                $url = str_replace('https://', '//', $url);
                $url = str_replace('http://', '//', $url);
            }
            $retval[] = $url;
        }
        return $retval;
    }
    function save_action()
    {
        $settings = C_NextGen_Settings::get_instance();
        $modified = FALSE;
        if ($params = $this->param('custom_lightbox')) {
            if (array_key_exists('scripts', $params)) {
                $settings->thumbEffectScripts = $this->_convert_to_urls($params['scripts']);
                $modified = TRUE;
            }
            if (array_key_exists('styles', $params)) {
                $settings->thumbEffectStyles = $this->_convert_to_urls($params['styles']);
                $modified = TRUE;
            }
            if (array_key_exists('code', $params)) {
                $settings->thumbEffectCode = $params['code'];
                $modified = TRUE;
            }
        }
        if ($modified) {
            $settings->save();
        }
    }
}
/**
 * Class A_Image_Options_Form
 * @mixin C_Form
 * @adapts I_Form using "image_options" context
 */
class A_Image_Options_Form extends Mixin
{
    function get_model()
    {
        return C_Settings_Model::get_instance();
    }
    function get_title()
    {
        return __('Image Options', 'nggallery');
    }
    /**
     * Returns the options available for sorting images
     * @return array
     */
    function _get_image_sorting_options()
    {
        return array(__('Custom', 'nggallery') => 'sortorder', __('Image ID', 'nggallery') => 'pid', __('Filename', 'nggallery') => 'filename', __('Alt/Title Text', 'nggallery') => 'alttext', __('Date/Time', 'nggallery') => 'imagedate');
    }
    /**
     * Returns the options available for sorting directions
     * @return array
     */
    function _get_sorting_direction_options()
    {
        return array(__('Ascending', 'nggallery') => 'ASC', __('Descending', 'nggallery') => 'DESC');
    }
    /**
     * Returns the options available for matching related images
     */
    function _get_related_image_match_options()
    {
        return array(__('Categories', 'nggallery') => 'category', __('Tags', 'nggallery') => 'tags');
    }
    /**
     * Tries to create the gallery storage directory if it doesn't exist
     * already
     * @return string
     */
    function _create_gallery_storage_dir($gallerypath = NULL)
    {
        $retval = TRUE;
        if (!$gallerypath) {
            $gallerypath = $this->object->get_model()->get('gallerypath');
        }
        $fs = C_Fs::get_instance();
        $gallerypath = $fs->join_paths($fs->get_document_root('galleries'), $gallerypath);
        if (!@file_exists($gallerypath)) {
            @mkdir($gallerypath);
            $retval = @file_exists($gallerypath);
        }
        return $retval;
    }
    /*
     * Renders the form
     */
    function render()
    {
        if (!$this->object->_create_gallery_storage_dir()) {
            $this->object->get_model()->add_error(__('Gallery path does not exist and could not be created', 'nggallery'), 'gallerypath');
        }
        $settings = $this->object->get_model();
        return $this->render_partial('photocrati-nextgen_other_options#image_options_tab', array('gallery_path_label' => __('Where would you like galleries stored?', 'nggallery'), 'gallery_path_help' => __('Where galleries and their images are stored', 'nggallery'), 'gallery_path' => $settings->gallerypath, 'delete_image_files_label' => __('Delete Image Files?', 'nggallery'), 'delete_image_files_help' => __('When enabled, image files will be removed after a Gallery has been deleted', 'nggallery'), 'delete_image_files' => $settings->deleteImg, 'show_related_images_label' => __('Show Related Images on Posts?', 'nggallery'), 'show_related_images_help' => __('When enabled, related images will be appended to each post by matching the posts tags/categories to image tags', 'nggallery'), 'show_related_images' => $settings->activateTags, 'related_images_hidden_label' => __('(Show Customization Settings)', 'nggallery'), 'related_images_active_label' => __('(Hide Customization Settings)', 'nggallery'), 'match_related_images_label' => __('How should related images be match?', 'nggallery'), 'match_related_images' => $settings->appendType, 'match_related_image_options' => $this->object->_get_related_image_match_options(), 'max_related_images_label' => __('Maximum # of related images to display', 'nggallery'), 'max_related_images' => $settings->maxImages, 'related_images_heading_label' => __('Heading for related images', 'nggallery'), 'related_images_heading' => $settings->relatedHeading, 'sorting_order_label' => __("What's the default sorting method?", 'nggallery'), 'sorting_order_options' => $this->object->_get_image_sorting_options(), 'sorting_order' => $settings->galSort, 'sorting_direction_label' => __('Sort in what direction?', 'nggallery'), 'sorting_direction_options' => $this->object->_get_sorting_direction_options(), 'sorting_direction' => $settings->galSortDir, 'automatic_resize_label' => __('Automatically resize images after upload', 'nggallery'), 'automatic_resize_help' => __('It is recommended that your images be resized to be web friendly', 'nggallery'), 'automatic_resize' => $settings->imgAutoResize, 'resize_images_label' => __('What should images be resized to?', 'nggallery'), 'resize_images_help' => __('After images are uploaded, they will be resized to the above dimensions and quality', 'nggallery'), 'resized_image_width_label' => __('Width:', 'nggallery'), 'resized_image_height_label' => __('Height:', 'nggallery'), 'resized_image_quality_label' => __('Quality:', 'nggallery'), 'resized_image_width' => $settings->imgWidth, 'resized_image_height' => $settings->imgHeight, 'resized_image_quality' => $settings->imgQuality, 'backup_images_label' => __('Backup the original images?', 'nggallery'), 'backup_images_yes_label' => __('Yes'), 'backup_images_no_label' => __('No'), 'backup_images' => $settings->imgBackup), TRUE);
    }
    function save_action($image_options)
    {
        $save = TRUE;
        if ($image_options) {
            // Update the gallery path. Moves all images to the new location
            if (isset($image_options['gallerypath']) && (!is_multisite() || get_current_blog_id() == 1)) {
                $fs = C_Fs::get_instance();
                $original_dir = $fs->get_abspath($this->object->get_model()->get('gallerypath'));
                $new_dir = $fs->get_abspath($image_options['gallerypath']);
                $image_options['gallerypath'] = $fs->add_trailing_slash($image_options['gallerypath']);
                // Note: the below file move is disabled because it's quite unreliable as it doesn't perform any checks
                //       For instance changing gallery path from /wp-content to /wp-content/gallery would attempt a recursive copy and then delete ALL files under wp-content, which would be disastreus
                #				// If the gallery path has changed...
                #				if ($original_dir != $new_dir) {
                #                    // Try creating the new directory
                #                    if ($this->object->_create_gallery_storage_dir($new_dir) AND is_writable($new_dir)) {
                #					    // Try moving files
                #						$this->object->recursive_copy($original_dir, $new_dir);
                #						$this->object->recursive_delete($original_dir);
                #						// Update gallery paths
                #						$mapper = $this->get_registry()->get_utility('I_Gallery_Mapper');
                #						foreach ($mapper->find_all() as $gallery) {
                #							$gallery->path = $image_options['gallerypath'] . $gallery->name;
                #							$mapper->save($gallery);
                #						}
                #					}
                #					else {
                #						$this->get_model()->add_error("Unable to change gallery path. Insufficient filesystem permissions");
                #						$save = FALSE;
                #					}
                #				}
            } elseif (isset($image_options['gallerypath'])) {
                unset($image_options['gallerypath']);
            }
            // Update image options
            if ($save) {
                $this->object->get_model()->set($image_options)->save();
            }
        }
    }
    /**
     * Copies one directory to another
     * @param string $src
     * @param string $dst
     * @return boolean
     */
    function recursive_copy($src, $dst)
    {
        $retval = TRUE;
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if ($file != '.' && $file != '..') {
                if (is_dir($src . '/' . $file)) {
                    if (!$this->object->recursive_copy($src . '/' . $file, $dst . '/' . $file)) {
                        $retval = FALSE;
                        break;
                    }
                } else {
                    if (!copy($src . '/' . $file, $dst . '/' . $file)) {
                        $retval = FALSE;
                        break;
                    }
                }
            }
        }
        closedir($dir);
        return $retval;
    }
    /**
     * Deletes all files within a particular directory
     * @param string $dir
     * @return boolean
     */
    function recursive_delete($dir)
    {
        $retval = FALSE;
        $fp = opendir($dir);
        while (false !== ($file = readdir($fp))) {
            if ($file != '.' && $file != '..') {
                $file = $dir . '/' . $file;
                if (is_dir($file)) {
                    $retval = $this->object->recursive_delete($file);
                } else {
                    $retval = unlink($file);
                }
            }
        }
        closedir($fp);
        @rmdir($dir);
        return $retval;
    }
}
/**
 * Class A_Lightbox_Manager_Form
 * @mixin C_Form
 * @adapts I_Form using "lightbox_effects" context
 */
class A_Lightbox_Manager_Form extends Mixin
{
    function get_model()
    {
        return C_Settings_Model::get_instance();
    }
    function get_title()
    {
        return __('Lightbox Effects', 'nggallery');
    }
    function render()
    {
        $form_manager = C_Form_Manager::get_instance();
        // retrieve and render the settings forms for each library
        $sub_fields = array();
        $form_manager->add_form(NGG_LIGHTBOX_OPTIONS_SLUG, 'custom_lightbox');
        foreach ($form_manager->get_forms(NGG_LIGHTBOX_OPTIONS_SLUG, TRUE) as $form) {
            $form->enqueue_static_resources();
            $sub_fields[$form->context] = $form->render(FALSE);
        }
        // Highslide and jQuery.Lightbox were removed in 2.0.73 due to licensing. If a user has selected
        // either of those options we silently make their selection fallback to Fancybox
        $selected = $this->object->get_model()->thumbEffect;
        if (in_array($selected, array('highslide', 'lightbox'))) {
            $selected = 'fancybox';
        }
        // Render container tab
        return $this->render_partial('photocrati-nextgen_other_options#lightbox_library_tab', array('lightbox_library_label' => __('What effect would you like to use?', 'nggallery'), 'libs' => C_Lightbox_Library_Manager::get_instance()->get_all(), 'selected' => $selected, 'sub_fields' => $sub_fields, 'lightbox_global' => $this->object->get_model()->thumbEffectContext), TRUE);
    }
    function save_action()
    {
        $settings = $this->object->get_model();
        // Ensure that a lightbox library was selected
        if ($id = $this->object->param('lightbox_library_id')) {
            $lightboxes = C_Lightbox_Library_Manager::get_instance();
            if (!$lightboxes->get($id)) {
                $settings->add_error('Invalid lightbox effect selected');
            } else {
                $settings->thumbEffect = $id;
            }
        }
        // Get thumb effect context
        if ($thumbEffectContext = $this->object->param('thumbEffectContext')) {
            $settings->thumbEffectContext = $thumbEffectContext;
        }
        $settings->save();
        // Save other forms
        $form_manager = C_Form_Manager::get_instance();
        $form_manager->add_form(NGG_LIGHTBOX_OPTIONS_SLUG, 'custom_lightbox');
        foreach ($form_manager->get_forms(NGG_LIGHTBOX_OPTIONS_SLUG, TRUE) as $form) {
            if ($form->has_method('save_action')) {
                $form->save_action();
            }
        }
    }
}
/**
 * Class A_Miscellaneous_Form
 * @mixin C_Form
 * @adapts I_Form using "miscellaneous" context
 */
class A_Miscellaneous_Form extends Mixin
{
    function get_model()
    {
        return C_Settings_Model::get_instance('global');
    }
    function get_title()
    {
        return __('Miscellaneous', 'nggallery');
    }
    function render()
    {
        return $this->object->render_partial('photocrati-nextgen_other_options#misc_tab', array('mediarss_activated' => C_NextGen_Settings::get_instance()->useMediaRSS, 'mediarss_activated_label' => __('Add MediaRSS link?', 'nggallery'), 'mediarss_activated_help' => __('When enabled, adds a MediaRSS link to your header. Third-party web services can use this to publish your galleries', 'nggallery'), 'mediarss_activated_no' => __('No'), 'mediarss_activated_yes' => __('Yes'), 'galleries_in_feeds' => C_NextGen_Settings::get_instance()->galleries_in_feeds, 'galleries_in_feeds_label' => __('Display galleries in feeds', 'nggallery'), 'galleries_in_feeds_help' => __('NextGEN hides its gallery displays in feeds other than MediaRSS. This enables image galleries in feeds.', 'nggallery'), 'galleries_in_feeds_no' => __('No'), 'galleries_in_feeds_yes' => __('Yes'), 'cache_label' => __('Clear image cache', 'nggallery'), 'cache_confirmation' => __("Completely clear the NextGEN cache of all image modifications?\n\nChoose [Cancel] to Stop, [OK] to proceed.", 'nggallery'), 'slug_field' => $this->_render_text_field((object) array('name' => 'misc_settings'), 'router_param_slug', __('Permalink slug', 'nggallery'), $this->object->get_model()->router_param_slug), 'maximum_entity_count_field' => $this->_render_number_field((object) array('name' => 'misc_settings'), 'maximum_entity_count', __('Maximum image count', 'nggallery'), $this->object->get_model()->maximum_entity_count, __('This is the maximum limit of images that NextGEN will restrict itself to querying', 'nggallery') . " \n " . __('Note: This limit will not apply to slideshow widgets or random galleries if/when those galleries specify their own image limits', 'nggallery'), FALSE, '', 1)), TRUE);
    }
    function cache_action()
    {
        $cache = C_Cache::get_instance();
        $cache->flush_galleries();
        C_Photocrati_Transient_Manager::flush();
    }
    function save_action()
    {
        if ($settings = $this->object->param('misc_settings')) {
            // The Media RSS setting is actually a local setting, not a global one
            $local_settings = C_NextGen_Settings::get_instance();
            $local_settings->set('useMediaRSS', $settings['useMediaRSS']);
            unset($settings['useMediaRSS']);
            // It's important the router_param_slug never be empty
            if (empty($settings['router_param_slug'])) {
                $settings['router_param_slug'] = 'nggallery';
            }
            // If the router slug has changed, then flush the cache
            if ($settings['router_param_slug'] != $this->object->get_model()->router_param_slug) {
                C_Photocrati_Transient_Manager::flush('displayed_gallery_rendering');
            }
            // Do not allow this field to ever be unset
            if (empty($settings['maximum_entity_count']) || (int) $settings['maximum_entity_count'] <= 0) {
                $settings['maximum_entity_count'] = 500;
            }
            // Save both setting groups
            $this->object->get_model()->set($settings)->save();
            $local_settings->save();
        }
    }
}
/**
 * Class A_Other_Options_Controller
 * @mixin C_NextGen_Admin_Page_Controller
 * @adapts I_NextGen_Admin_Page using "ngg_other_options" context
 */
class A_Other_Options_Controller extends Mixin
{
    function enqueue_backend_resources()
    {
        $this->call_parent('enqueue_backend_resources');
        wp_enqueue_script('nextgen_settings_page', $this->get_static_url('photocrati-nextgen_other_options#nextgen_settings_page.js'), array('jquery-ui-accordion', 'jquery-ui-tooltip', 'wp-color-picker', 'jquery.nextgen_radio_toggle'), NGG_SCRIPT_VERSION);
        wp_enqueue_style('nextgen_settings_page', $this->get_static_url('photocrati-nextgen_other_options#nextgen_settings_page.css'), FALSE, NGG_SCRIPT_VERSION);
    }
    function get_page_title()
    {
        return __('Other Options', 'nggallery');
    }
    function get_required_permission()
    {
        return 'NextGEN Change options';
    }
}
/**
 * Class A_Other_Options_Page
 * @mixin C_Page_Manager
 * @adapts I_Page_Manager
 */
class A_Other_Options_Page extends Mixin
{
    function setup()
    {
        $this->object->add(NGG_OTHER_OPTIONS_SLUG, array('adapter' => 'A_Other_Options_Controller', 'parent' => NGGFOLDER, 'before' => 'ngg_pro_upgrade'));
        return $this->call_parent('setup');
    }
}
/**
 * Class A_Reset_Form
 * @mixin C_Form
 * @adapts I_Form using "reset" context
 */
class A_Reset_Form extends Mixin
{
    function get_title()
    {
        return __('Reset Options', 'nggallery');
    }
    function render()
    {
        return $this->object->render_partial('photocrati-nextgen_other_options#reset_tab', array('reset_value' => __('Reset all options to default settings', 'nggallery'), 'reset_warning' => __('Replace all existing options and gallery options with their default settings', 'nggallery'), 'reset_label' => __('Reset settings', 'nggallery'), 'reset_confirmation' => __("Reset all options to default settings?\n\nChoose [Cancel] to Stop, [OK] to proceed.", 'nggallery')), TRUE);
    }
    function reset_action()
    {
        global $wpdb;
        // Flush the cache
        C_Photocrati_Transient_Manager::flush();
        // Uninstall the plugin
        $settings = C_NextGen_Settings::get_instance();
        if (defined('NGG_PRO_PLUGIN_VERSION') || defined('NEXTGEN_GALLERY_PRO_VERSION')) {
            C_Photocrati_Installer::uninstall('photocrati-nextgen-pro');
        }
        C_Photocrati_Installer::uninstall('photocrati-nextgen');
        // removes all ngg_options entry in wp_options
        $settings->reset();
        $settings->destroy();
        // clear NextGEN's capabilities from the roles system
        $capabilities = array("NextGEN Gallery overview", "NextGEN Use TinyMCE", "NextGEN Upload images", "NextGEN Manage gallery", "NextGEN Manage others gallery", "NextGEN Manage tags", "NextGEN Edit album", "NextGEN Change style", "NextGEN Change options", "NextGEN Attach Interface");
        $roles = array("subscriber", "contributor", "author", "editor", "administrator");
        foreach ($roles as $role) {
            $role = get_role($role);
            foreach ($capabilities as $capability) {
                if (!is_null($role)) {
                    $role->remove_cap($capability);
                }
            }
        }
        // Some installations of NextGen that upgraded from 1.9x to 2.0x have duplicates installed,
        // so for now (as of 2.0.21) we explicitly remove all display types and lightboxes from the
        // db as a way of fixing this.
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->posts} WHERE post_type = %s", 'display_type'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->posts} WHERE post_type = %s", 'lightbox_library'));
        // the installation will run on the next page load; so make our own request before reloading the browser
        wp_remote_get(admin_url('plugins.php'), array('timeout' => 180, 'blocking' => true, 'sslverify' => false));
        header('Location: ' . get_admin_url());
        throw new E_Clean_Exit();
    }
}
/**
 * Class A_Roles_Form
 * @mixin C_Form
 * @adapts I_Form using "roles_and_capabilities" context
 */
class A_Roles_Form extends Mixin
{
    function get_title()
    {
        return __('Roles & Capabilities', 'nggallery');
    }
    function render()
    {
        $view = implode(DIRECTORY_SEPARATOR, array(rtrim(NGGALLERY_ABSPATH, "/\\"), 'admin', 'roles.php'));
        include_once $view;
        ob_start();
        nggallery_admin_roles();
        $retval = ob_get_contents();
        ob_end_clean();
        return $retval;
    }
}
/**
 * Class A_Styles_Form
 * @mixin C_Form
 * @adapts I_Form using "styles" context
 */
class A_Styles_Form extends Mixin
{
    function get_model()
    {
        return C_Settings_Model::get_instance();
    }
    function get_title()
    {
        return __('Styles', 'nggallery');
    }
    function render()
    {
        return $this->object->render_partial('photocrati-nextgen_other_options#styling_tab', array('activateCSS_label' => __('Enable custom CSS', 'nggallery'), 'activateCSS' => $this->object->get_model()->activateCSS, 'select_stylesheet_label' => __('What stylesheet would you like to use?', 'nggallery'), 'stylesheets' => C_NextGen_Style_Manager::get_instance()->find_all_stylesheets(), 'activated_stylesheet' => $this->object->get_model()->CSSfile, 'hidden_label' => __('(Show Customization Options)', 'nggallery'), 'active_label' => __('(Hide Customization Options)', 'nggallery'), 'cssfile_contents_label' => __('File Content:', 'nggallery'), 'writable_label' => __('Changes you make to the contents will be saved to', 'nggallery'), 'readonly_label' => __('You could edit this file if it were writable', 'nggallery')), TRUE);
    }
    function save_action()
    {
        // Ensure that we have
        if ($settings = $this->object->param('style_settings')) {
            $this->object->get_model()->set($settings)->save();
            // Are we to modify the CSS file?
            if ($contents = $this->object->param('cssfile_contents')) {
                // Find filename
                $css_file = $settings['CSSfile'];
                $styles = C_NextGen_Style_Manager::get_instance();
                $styles->save($contents, $css_file);
            }
        }
    }
}
/**
 * Registers new AJAX functions for retrieving/updating
 * the contents of CSS stylesheets
 *
 * @mixin C_Ajax_Controller
 * @adapts I_Ajax_Controller
 */
class A_Stylesheet_Ajax_Actions extends Mixin
{
    /**
     * Retrieves the contents of the CSS stylesheet specified
     */
    function get_stylesheet_contents_action()
    {
        $retval = array();
        if ($this->object->_authorized_for_stylesheet_action()) {
            $styles = C_NextGen_Style_Manager::get_instance();
            $abspath = $styles->find_selected_stylesheet_abspath($this->object->param('cssfile'));
            $writepath = $styles->get_selected_stylesheet_saved_abspath($this->object->param('cssfile'));
            if (is_readable($abspath)) {
                $retval['contents'] = file_get_contents($abspath);
                $retval['writable'] = is_writable($abspath);
                $retval['abspath'] = $abspath;
                $retval['writepath'] = $writepath;
            } else {
                $retval['error'] = "Could not find stylesheet";
            }
        } else {
            $retval['error'] = 'Unauthorized';
        }
        return $retval;
    }
    /**
     * Determines if the request is authorized
     * @return boolean
     */
    function _authorized_for_stylesheet_action()
    {
        $security = $this->get_registry()->get_utility('I_Security_Manager');
        $sec_actor = $security->get_current_actor();
        return $sec_actor->is_allowed('nextgen_edit_style');
    }
}
/**
 * Class A_Thumbnail_Options_Form
 * @mixin C_Form
 * @adapts I_Form using "thumbnail_options" context
 */
class A_Thumbnail_Options_Form extends Mixin
{
    function get_model()
    {
        return C_Settings_Model::get_instance();
    }
    function get_title()
    {
        return __('Thumbnail Options', 'nggallery');
    }
    function render()
    {
        $settings = $this->object->get_model();
        return $this->render_partial('photocrati-nextgen_other_options#thumbnail_options_tab', array('thumbnail_dimensions_label' => __('Default thumbnail dimensions:', 'nggallery'), 'thumbnail_dimensions_help' => __('When generating thumbnails, what image dimensions do you desire?', 'nggallery'), 'thumbnail_dimensions_width' => $settings->thumbwidth, 'thumbnail_dimensions_height' => $settings->thumbheight, 'thumbnail_crop_label' => __('Set fix dimension?', 'nggallery'), 'thumbnail_crop_help' => __('Ignore the aspect ratio, no portrait thumbnails?', 'nggallery'), 'thumbnail_crop' => $settings->thumbfix, 'thumbnail_quality_label' => __('Adjust Thumbnail Quality?', 'nggallery'), 'thumbnail_quality_help' => __('When generating thumbnails, what image quality do you desire?', 'nggallery'), 'thumbnail_quality' => $settings->thumbquality, 'size_list_label' => __('Size List', 'nggallery'), 'size_list_help' => __('List of default sizes used for thumbnails and images', 'nggallery'), 'size_list' => $settings->thumbnail_dimensions), TRUE);
    }
    function save_action()
    {
        if ($settings = $this->object->param('thumbnail_settings')) {
            $this->object->get_model()->set($settings)->save();
        }
    }
}
/**
 * Class A_Watermarking_Ajax_Actions
 * @mixin C_Ajax_Controller
 * @adapts I_Ajax_Controller
 */
class A_Watermarking_Ajax_Actions extends Mixin
{
    /**
     * Gets the new watermark preview url based on the new settings
     * @return array
     */
    function get_watermark_preview_url_action()
    {
        $security = $this->get_registry()->get_utility('I_Security_Manager');
        $sec_actor = $security->get_current_actor();
        if ($sec_actor->is_allowed('nextgen_edit_settings')) {
            $settings = C_NextGen_Settings::get_instance();
            $imagegen = C_Dynamic_Thumbnails_Manager::get_instance();
            $mapper = C_Image_Mapper::get_instance();
            $image = $mapper->find_first();
            $storage = C_Gallery_Storage::get_instance();
            $sizeinfo = array('quality' => 100, 'height' => 250, 'crop' => FALSE, 'watermark' => TRUE);
            $size = $imagegen->get_size_name($sizeinfo);
            $thumbnail_url = $storage->get_image_url($image, $size);
            // Temporarily update the watermark options. Generate a new image based
            // on these settings
            if ($watermark_options = $this->param('watermark_options')) {
                $watermark_options['wmFont'] = trim($watermark_options['wmFont']);
                $settings->set($watermark_options);
                $storage->generate_image_size($image, $size);
                $thumbnail_url = $storage->get_image_url($image, $size);
                $settings->load();
            }
            return array('thumbnail_url' => $thumbnail_url);
        } else {
            return array('thumbnail_url' => '', 'error' => 'You are not allowed to perform this operation');
        }
    }
}
/**
 * Class A_Watermarks_Form
 * @mixin C_Form
 * @adapts I_Form using "watermarks" context
 */
class A_Watermarks_Form extends Mixin
{
    function get_model()
    {
        return C_Settings_Model::get_instance();
    }
    function get_title()
    {
        return __('Watermarks', 'nggallery');
    }
    /**
     * Gets all fonts installed for watermarking
     * @return array
     */
    function _get_watermark_fonts()
    {
        $retval = array();
        $path = implode(DIRECTORY_SEPARATOR, array(rtrim(NGGALLERY_ABSPATH, "/\\"), 'fonts'));
        foreach (scandir($path) as $filename) {
            if (strpos($filename, '.') === 0) {
                continue;
            } else {
                $retval[] = $filename;
            }
        }
        return $retval;
    }
    /**
     * Gets watermark sources, along with their respective fields
     * @return array
     */
    function _get_watermark_sources()
    {
        // We do this so that an adapter can add new sources
        return array(__('Using an Image', 'nggallery') => 'image', __('Using Text', 'nggallery') => 'text');
    }
    /**
     * Renders the fields for a watermark source (image, text)
     * @return string
     */
    function _get_watermark_source_fields()
    {
        $retval = array();
        foreach ($this->object->_get_watermark_sources() as $label => $value) {
            $method = "_render_watermark_{$value}_fields";
            if ($this->object->has_method($method)) {
                $retval[$value] = $this->object->call_method($method);
            }
        }
        return $retval;
    }
    /**
     * Render fields that are needed when 'image' is selected as a watermark
     * source
     * @return string
     */
    function _render_watermark_image_fields()
    {
        $message = __('An absolute or relative (to the site document root) file system path', 'nggallery');
        if (ini_get('allow_url_fopen')) {
            $message = __('An absolute or relative (to the site document root) file system path or an HTTP url', 'nggallery');
        }
        return $this->object->render_partial('photocrati-nextgen_other_options#watermark_image_fields', array('image_url_label' => __('Image URL:', 'nggallery'), 'watermark_image_url' => $this->object->get_model()->wmPath, 'watermark_image_text' => $message), TRUE);
    }
    /**
     * Render fields that are needed when 'text is selected as a watermark
     * source
     * @return string
     */
    function _render_watermark_text_fields()
    {
        $settings = $this->object->get_model();
        return $this->object->render_partial('photocrati-nextgen_other_options#watermark_text_fields', array('fonts' => $this->object->_get_watermark_fonts($settings), 'font_family_label' => __('Font Family:', 'nggallery'), 'font_family' => $settings->wmFont, 'font_size_label' => __('Font Size:', 'nggallery'), 'font_size' => $settings->wmSize, 'font_color_label' => __('Font Color:', 'nggallery'), 'font_color' => strpos($settings->wmColor, '#') === 0 ? $settings->wmColor : "#{$settings->wmColor}", 'watermark_text_label' => __('Text:', 'nggallery'), 'watermark_text' => $settings->wmText, 'opacity_label' => __('Opacity:', 'nggallery'), 'opacity' => $settings->wmOpaque), TRUE);
    }
    function _get_preview_image()
    {
        $registry = $this->object->get_registry();
        $storage = C_Gallery_Storage::get_instance();
        $image = C_Image_Mapper::get_instance()->find_first();
        $imagegen = C_Dynamic_Thumbnails_Manager::get_instance();
        $size = $imagegen->get_size_name(array('height' => 250, 'crop' => FALSE, 'watermark' => TRUE));
        $url = $image ? $storage->get_image_url($image, $size) : NULL;
        $abspath = $image ? $storage->get_image_abspath($image, $size) : NULL;
        return array('url' => $url, 'abspath' => $abspath);
    }
    function render()
    {
        $settings = $this->get_model();
        $image = $this->object->_get_preview_image();
        return $this->render_partial('photocrati-nextgen_other_options#watermarks_tab', array('notice' => __('Please note: You can only activate the watermark under Manage Gallery. This action cannot be undone.', 'nggallery'), 'watermark_source_label' => __('How will you generate a watermark?', 'nggallery'), 'watermark_sources' => $this->object->_get_watermark_sources(), 'watermark_fields' => $this->object->_get_watermark_source_fields($settings), 'watermark_source' => $settings->wmType, 'position_label' => __('Position:', 'nggallery'), 'position' => $settings->wmPos, 'offset_label' => __('Offset:', 'nggallery'), 'offset_x' => $settings->wmXpos, 'offset_y' => $settings->wmYpos, 'hidden_label' => __('(Show Customization Options)', 'nggallery'), 'active_label' => __('(Hide Customization Options)', 'nggallery'), 'thumbnail_url' => $image['url'], 'preview_label' => __('Preview of saved settings:', 'nggallery'), 'refresh_label' => __('Refresh preview image', 'nggallery'), 'refresh_url' => $settings->ajax_url), TRUE);
    }
    function save_action()
    {
        if ($settings = $this->object->param('watermark_options')) {
            $this->object->get_model()->set($settings)->save();
            $image = $this->object->_get_preview_image();
            if (is_file($image['abspath'])) {
                @unlink($image['abspath']);
            }
        }
    }
}
/**
 * Class C_Settings_Model
 * @mixin Mixin_Validation
 */
class C_Settings_Model extends C_Component
{
    /**
     * @var C_NextGen_Settings_Base
     */
    var $wrapper = NULL;
    static $_instances = array();
    static function get_instance($context = FALSE)
    {
        if (!isset(self::$_instances[$context])) {
            $klass = get_class();
            self::$_instances[$context] = new $klass();
        }
        return self::$_instances[$context];
    }
    function define($context = FALSE)
    {
        parent::define($context);
        $this->add_mixin('Mixin_Validation');
        if ($this->has_context('global') or $this->has_context('site')) {
            $this->wrapper = C_NextGen_Settings::get_instance();
        } else {
            $this->wrapper = C_NextGen_Settings::get_instance();
        }
    }
    function __get($key)
    {
        return $this->wrapper->get($key);
    }
    function __set($key, $value)
    {
        $this->wrapper->set($key, $value);
        return $this;
    }
    function __isset($key)
    {
        return $this->wrapper->is_set($key);
    }
    function __call($method, $args)
    {
        if (!$this->get_mixin_providing($method)) {
            return call_user_func_array(array(&$this->wrapper, $method), $args);
        } else {
            return parent::__call($method, $args);
        }
    }
}