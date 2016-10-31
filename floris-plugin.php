<?php
/*
Plugin Name: Floris
Description: A powerful theme for shops.
Author: Azelab
Version: 1.0
Text Domain: Floris
Plugin URI: http://azelab.com/
*/

//Update

add_action( 'init', 'github_plugin_updater_test_init' );
function github_plugin_updater_test_init() {

    include_once 'updater.php';

    define( 'WP_GITHUB_FORCE_UPDATE', true );

    if ( is_admin() ) { // note the use of is_admin() to double check that this is happening in the admin

        $config = array(
            'slug' => plugin_basename( __FILE__ ),
            'proper_folder_name' => 'floris-plugin',
            'api_url' => 'https://api.github.com/repos/azelab/floris-plugin',
            'raw_url' => 'https://raw.github.com/azelab/floris-plugin/master',
            'github_url' => 'https://github.com/azelab/floris-plugin',
            'zip_url' => 'https://github.com/azelab/floris-plugin/archive/master.zip',
            'sslverify' => true,
            'requires' => '4.0',
            'tested' => '4.6.1',
            'access_token' => '',
        );

        new WP_GitHub_Updater( $config );

    }

}

// Define Constants
define('TT_FW_ROOT', dirname(__FILE__));
define('TT_FW_VERSION', '1.4');

// Fetch the options set from theme, which we use to decide which features to turn on from this plugin.
$defaults = array(
		'portfolio_cpt'             => '0',
		'team_cpt'                  => '0',
		'client_cpt'                => '0',
		'testimonial_cpt'           => '0',
		'project_cpt'               => '0',
		'metaboxes'                 => '1',
		'theme_options'             => '1',
		'common_shortcodes'         => '1',
		'integrate_VC'              => '1',
		'tt_widget_instagram'       => '0',
		'tt_widget_twitter'         => '0',
);
$tt_temptt_components = wp_parse_args( get_option('tt_temptt_components_user'), $defaults ); // Replace defaults with values set in Theme.


//Include redux framework
if ( ! class_exists( 'Redux' && ! empty( $tt_temptt_components['theme_options'] ) ) ) {
	include TT_FW_ROOT . '/inc/redux/admin-init.php';
}

//Include CS framework
if ( ! class_exists( 'CSFramework' && ! empty( $tt_temptt_components['metaboxes'] ) ) ) {
	include TT_FW_ROOT . '/inc/cs-framework/cs-framework.php';
}

/*-----------------------------------------------------------------------------------*/
/* Remove no-ttfmwrk class from body, when this plugin is active. */
/*-----------------------------------------------------------------------------------*/
add_filter( 'body_class','tt_temptt_ttfmwrk_yes', 11 );
if ( ! function_exists( 'tt_temptt_ttfmwrk_yes' ) ) {
function tt_temptt_ttfmwrk_yes( $classes ) {

	if (($key = array_search('no-ttfmwrk', $classes)) !== false) {
    unset($classes[$key]);
	}    

    $menu_type = floris_get_option( 'header_layout');
    $enable_border = floris_get_option('enable_border', 0);
    if($menu_type == '2'){ $classes[] ='border-style-2';}
    if(!$enable_border){ $classes[] =' enable_border_body';}

	return $classes;
  }
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if(is_plugin_active('js_composer/js_composer.php')){
	function your_name_integrateWithVC() {
		function my_param($settings, $value) {
		    $css_option = vc_get_dropdown_option( $settings, $value );
		    $value1 = explode( ',', $value );
		    $output  = '<select name="'. $settings['param_name'] .'" data-placeholder="'. $settings['placeholder'] .'" multiple="multiple" class="wpb_vc_param_value wpb_chosen chosen wpb-input wpb-efa-select '. $settings['param_name'] .' '. $settings['type'] .' '. $css_option .'" data-option="'. $css_option .'">';
		    foreach ( $settings['value'] as $values => $option ) {
		      	$selected = ( in_array( $option, $value1 ) ) ? ' selected="selected"' : '';
		      	$output .= '<option value="'. $option .'"'. $selected .'>'.htmlspecialchars( $values ).'</option>';
		    }
		    $output .= '</select>' . "\n";	    
		    return $output;  
		  }
		vc_add_shortcode_param('vc_efa_chosen', 'my_param');
	}
	add_action( 'vc_before_init', 'your_name_integrateWithVC' );
}

// Add Thumbnail Product category
add_action( 'product_cat_edit_form_fields', 'floris_taxonomy_edit_meta_field', 10, 2 );
function floris_taxonomy_edit_meta_field($term) {
    $thumbnail_idb = absint( get_woocommerce_term_meta( $term->term_id, 'thumbnail_idb', true ) );
    if ( $thumbnail_idb ) {
        $imageb = wp_get_attachment_thumb_url( $thumbnail_idb );
    } else {
        $imageb = wc_placeholder_img_src();
    }
    // put the term ID into a variable
    $t_id = $term->term_id;
    // retrieve the existing value(s) for this meta field. This returns an array
    $term_meta = get_option( "taxonomy_$t_id" );
    $content = $term_meta['custom_term_meta'] ? wp_kses_post($term_meta['custom_term_meta'] ) : '';
    $settings = array( 'textarea_name' => 'term_meta[custom_term_meta]' );
?>

    <tr class="form-field">
        <th scope="row" valign="top"><label><?php esc_html_e( 'Thumbnail Page', 'floris' ); ?></label></th>
        <td>
            <div id="product_cat_thumbnailbig" style="float: left; margin-right: 10px;"><img src="<?php print esc_url( $imageb ); ?>" width="60px" height="60px" /></div>
            <div style="line-height: 60px;">
                <input type="hidden" id="product_cat_thumbnailbig_id" name="product_cat_thumbnailbig_id" value="<?php print esc_html( $thumbnail_idb ); ?>" />
                <button type="button" class="upload_image_buttonbig button"><?php esc_html_e( 'Upload/Add image', 'floris' ); ?></button>
                <button type="button" class="remove_image_buttonbig button"><?php esc_html_e( 'Remove image', 'floris' ); ?></button>
            </div>
            <script type="text/javascript">
                // Only show the "remove image" button when needed
                if ( '0' === jQuery( '#product_cat_thumbnailbig_id' ).val() ) {
                    jQuery( '.remove_image_buttonbig' ).hide();
                }
                // Uploading files
                var file_frame;
                jQuery( document ).on( 'click', '.upload_image_buttonbig', function( event ) {
                    event.preventDefault();
                    // If the media frame already exists, reopen it.
                    if ( file_frame ) {
                        file_frame.open();
                        return;
                    }
                    // Create the media frame.
                    file_frame = wp.media.frames.downloadable_file = wp.media({
                        title: '<?php esc_html_e( "Choose an image", "floris" ); ?>',
                        button: {
                            text: '<?php esc_html_e( "Use image", "floris" ); ?>'
                        },
                        multiple: false
                    });
                    // When an image is selected, run a callback.
                    file_frame.on( 'select', function() {
                        var attachment = file_frame.state().get( 'selection' ).first().toJSON();
                        jQuery( '#product_cat_thumbnailbig_id' ).val( attachment.id );
                        jQuery( '#product_cat_thumbnailbig img' ).attr( 'src', attachment.sizes.thumbnail.url );
                        jQuery( '.remove_image_buttonbig' ).show();
                    });
                    // Finally, open the modal.
                    file_frame.open();
                });
                jQuery( document ).on( 'click', '.remove_image_buttonbig', function() {
                    jQuery( '#product_cat_thumbnailbig img' ).attr( 'src', '<?php print esc_js( wc_placeholder_img_src() ); ?>' );
                    jQuery( '#product_cat_thumbnailbig_id' ).val( '' );
                    jQuery( '.remove_image_buttonbig' ).hide();
                    return false;
                });
            </script>
            <div class="clear"></div>
        </td>
    </tr>
<?php
}
// Save extra taxonomy fields callback function
add_action( 'edited_product_cat', 'floris_save_taxonomy_custom_meta', 10, 2 );  
add_action( 'create_product_cat', 'floris_save_taxonomy_custom_meta', 10, 2 );
function floris_save_taxonomy_custom_meta( $term_id) {
    if ( isset( $_POST['term_meta'] ) ) {
        $t_id = $term_id;
        $term_meta = get_option( "taxonomy_$t_id" );
        $cat_keys = array_keys( $_POST['term_meta'] );
        foreach ( $cat_keys as $key ) {
            if ( isset ( $_POST['term_meta'][$key] ) ) {
                $term_meta[$key] = wp_kses_post( stripslashes($_POST['term_meta'][$key]) );
            }
        }
        // Save the option array.
        update_option( "taxonomy_$t_id", $term_meta );
    }
    if ( isset( $_POST['product_cat_thumbnailbig_id'] ) ) {
        update_woocommerce_term_meta( $term_id, 'thumbnail_idb', absint( $_POST['product_cat_thumbnailbig_id'] ) );
    }
}
// add_action('user_register', 'set_user_metaboxes');
add_action('admin_init', 'floris_set_user_metaboxes');
function floris_set_user_metaboxes($user_id=NULL) {
    // These are the metakeys we will need to update
    $meta_key['order'] = 'meta-box-order_post';
    $meta_key['hidden'] = 'metaboxhidden_post';
    // So this can be used without hooking into user_register
    if ( ! $user_id)
        $user_id = get_current_user_id(); 
    // Set the default order if it has not been set yet
    if ( ! get_user_meta( $user_id, $meta_key['order'], true) ) {
        $meta_value = array(
            'side' => 'submitdiv,formatdiv,categorydiv,postimagediv',
            'normal' => 'postexcerpt,tagsdiv-post_tag,postcustom,commentstatusdiv,commentsdiv,trackbacksdiv,slugdiv,authordiv,revisionsdiv',
            'advanced' => '',
        );
        update_user_meta( $user_id, $meta_key['order'], $meta_value );
    }
    // Set the default hiddens if it has not been set yet
    if ( ! get_user_meta( $user_id, $meta_key['hidden'], true) ) {
        $meta_value = array('postcustom','trackbacksdiv','commentstatusdiv','commentsdiv','slugdiv','authordiv','revisionsdiv');
        update_user_meta( $user_id, $meta_key['hidden'], $meta_value );
    }
}
/*add woo support*/
add_action( 'after_setup_theme', 'floris_woocommerce_support' );
function floris_woocommerce_support() {
    add_theme_support( 'woocommerce' );
}

add_action('wp_footer','floris_products_ajaxurl');
    function floris_products_ajaxurl() {
    ?>
    <script type="text/javascript">
        var ajaxurl = '<?php print admin_url('admin-ajax.php'); ?>';
    </script>
    <?php
}

//Add admin css.
add_action('admin_head', 'floris_admin_css');
function floris_admin_css() {
    wp_enqueue_style( 'super_admin', FLORIS_THEME_DIRURI . 'assets/css/admin.css', '', null );
}

//Add admin js.
add_action('admin_footer', 'floris_admin_js');
function floris_admin_js() {
    wp_enqueue_script( 'admin-js', FLORIS_THEME_DIRURI . 'assets/js/admin.js', array( 'jquery' ), null, true );
}

//Add header code hook.
add_action('wp_head','floris_hook_header_code');
function floris_hook_header_code() {
    print floris_get_option('codes_header');
}

//Add footer code hook.
add_action('wp_footer','floris_hook_footer_code');
function floris_hook_footer_code() {
    print floris_get_option('codes_footer');
}

//post_video
function floris_post_video($video){
    print do_shortcode('[vc_video link="'.$video.'"]');
}

//mailchimp
function floris_mailchimp($mailchimp_ID, $shortcode){
    print do_shortcode('[yikes-mailchimp form="'.$mailchimp_ID.'" '.$shortcode.']');
}

//banner
function floris_banner($content){
    print do_shortcode($content);
}

//floris_content
function floris_content( $content, $ignore_html = false ) {
    global $shortcode_tags;

    if ( false === strpos( $content, '[' ) ) {
        print $content;
    }

    if (empty($shortcode_tags) || !is_array($shortcode_tags))
        print $content;

    // Find all registered tag names in $content.
    preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches );
    $tagnames = array_intersect( array_keys( $shortcode_tags ), $matches[1] );

    if ( empty( $tagnames ) ) {
        print $content;
    }

    $content = do_shortcodes_in_html_tags( $content, $ignore_html, $tagnames );

    $pattern = get_shortcode_regex( $tagnames );
    $content = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $content );

    // Always restore square braces so we don't break things like <!--[if IE ]>
    $content = unescape_invalid_shortcodes( $content );

    print $content;
}

/*share*/
function floris_open_graph(){
    print '<meta property="og:title" content="'.get_the_title().'"/>
        <meta property="og:url" content="'.get_the_permalink().'"/>
        <meta property="og:image" content="'.wp_get_attachment_url(get_post_thumbnail_id()).'"/>';
}

//mega menu style
function megamenu_add_theme_default_floris($themes) {
    $themes["default_floris"] = array(
        'title' => 'Floris',
        'container_background_from' => 'rgba(0, 0, 0, 0)',
        'container_background_to' => 'rgba(0, 0, 0, 0)',
        'container_padding_top' => '23px',
        'menu_item_background_hover_from' => 'rgba(0, 0, 0, 0)',
        'menu_item_background_hover_to' => 'rgba(0, 0, 0, 0)',
        'menu_item_spacing' => '24px',
        'menu_item_link_font_size' => '16px',
        'menu_item_link_height' => 'auto',
        'menu_item_link_color' => 'rgb(47, 47, 47)',
        'menu_item_link_weight' => 'inherit',
        'menu_item_link_text_transform' => 'uppercase',
        'menu_item_link_color_hover' => 'rgb(182, 145, 118)',
        'menu_item_link_weight_hover' => 'inherit',
        'menu_item_link_padding_left' => '0px',
        'menu_item_link_padding_right' => '0px',
        'panel_background_from' => 'rgb(255, 255, 255)',
        'panel_background_to' => 'rgb(255, 255, 255)',
        'panel_width' => '117.8%',
        'panel_header_color' => 'rgb(62, 62, 62)',
        'panel_header_font_weight' => 'inherit',
        'panel_header_border_color' => '#555',
        'panel_widget_padding_left' => '14px',
        'panel_widget_padding_right' => '12px',
        'panel_font_size' => '16px',
        'panel_font_color' => 'rgb(62, 62, 62)',
        'panel_font_family' => 'inherit',
        'panel_second_level_font_color' => 'rgb(62, 62, 62)',
        'panel_second_level_font_color_hover' => 'rgb(182, 145, 118)',
        'panel_second_level_text_transform' => 'uppercase',
        'panel_second_level_font' => 'inherit',
        'panel_second_level_font_size' => '16px',
        'panel_second_level_font_weight' => 'bold',
        'panel_second_level_font_weight_hover' => 'inherit',
        'panel_second_level_text_decoration' => 'none',
        'panel_second_level_text_decoration_hover' => 'none',
        'panel_second_level_border_color' => '#555',
        'panel_third_level_font_color' => '#666',
        'panel_third_level_font_color_hover' => '#666',
        'panel_third_level_font' => 'inherit',
        'panel_third_level_font_size' => '14px',
        'flyout_width' => '246px',
        'flyout_menu_background_from' => 'rgb(255, 255, 255)',
        'flyout_menu_background_to' => 'rgb(255, 255, 255)',
        'flyout_menu_item_divider' => 'on',
        'flyout_menu_item_divider_color' => 'rgb(219, 219, 219)',
        'flyout_link_padding_left' => '0px',
        'flyout_link_padding_right' => '0px',
        'flyout_link_weight' => 'inherit',
        'flyout_link_height' => '40px',
        'flyout_background_from' => 'rgb(255, 255, 255)',
        'flyout_background_to' => 'rgb(255, 255, 255)',
        'flyout_background_hover_from' => 'rgba(0, 0, 0, 0)',
        'flyout_background_hover_to' => 'rgba(0, 0, 0, 0)',
        'flyout_link_size' => '14px',
        'flyout_link_color' => 'rgb(47, 47, 47)',
        'flyout_link_color_hover' => 'rgb(47, 47, 47)',
        'flyout_link_family' => 'inherit',
        'responsive_breakpoint' => '991px',
        'toggle_background_from' => 'rgb(255, 255, 255)',
        'toggle_background_to' => 'rgb(255, 255, 255)',
        'toggle_font_color' => 'rgb(47, 47, 47)',
        'mobile_background_from' => '#222',
        'mobile_background_to' => '#222',
        'custom_css' => '/** Push menu onto new line **/
        #{$wrap} {
        clear: both;
        }',
    );
    return $themes;
}
add_filter("megamenu_themes", "megamenu_add_theme_default_floris");