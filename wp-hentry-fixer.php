<?php
/*
Plugin Name: WP Hentry Fixer
Plugin URI: https://github.com/michaeldoye/wp-hentry-fixer
Description: Fixes missing hentry errors for single posts and archive pages.
Author: Web SEO Online (PTY) LTD
Author URI: https://webseo.co.za
Version: 0.1.0

	Copyright: © 2016 Web SEO Online (PTY) LTD (email : michael@webseo.co.za)
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Set Admin Options Page
**/

add_action('admin_menu', function() {
    add_options_page( 'WP Hentry Fixer Options', 'WP Hentry Fixer', 'manage_options', 'wp-hentry-fixer', 'wp_hentry_fixer_page' );
});

/**
 * Register Settings Group for Plugin
**/

add_action( 'admin_init', function() {
    register_setting( 'wp-hentry-fixer-settings', 'remove_hentry_class_from_pages' );
    register_setting( 'wp-hentry-fixer-settings', 'inject_hatom_on_archive_pages' );
    register_setting( 'wp-hentry-fixer-settings', 'inject_hatom_on_single_post_pages' );
    register_setting( 'wp-hentry-fixer-settings', 'inject_hatom_on_posts_page' );
});

function wp_hentry_fixer_page() {
 ?>
   <div class="wrap">
     <h1>WP Hentry Fixer Options</h1>
     <form action="options.php" method="post">

       <?php
       settings_fields( 'wp-hentry-fixer-settings' );
       do_settings_sections( 'wp-hentry-fixer-settings' );
       ?>

       <table cellspacing="15px">
            <tr>
                <th align="left" valign="top">Remove Hentry Classes From Pages?</th>
                <td>
                    <label>
                        <input type="checkbox" name="remove_hentry_class_from_pages" <?php echo esc_attr( get_option('remove_hentry_class_from_pages') ) == 'on' ? 'checked="checked"' : ''; ?> />Yes, remove <code>hentry</code> classes from pages <code>is_page()</code>
                    </label>
                </td>
            </tr>

            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>

            <tr>
                <th align="left" valign="top">Inject Hentry Data into the following:</th>
                <td>
                    <label>
                        <input type="checkbox" name="inject_hatom_on_archive_pages" <?php echo esc_attr( get_option('inject_hatom_on_archive_pages') ) == 'on' ? 'checked="checked"' : ''; ?> /> Archive Pages <code>is_archive()</code>
                    </label><br/>
                    <label>
                        <input type="checkbox" name="inject_hatom_on_single_post_pages" <?php echo esc_attr( get_option('inject_hatom_on_single_post_pages') ) == 'on' ? 'checked="checked"' : ''; ?> /> Single Post Pages <code>is_single()</code>
                    </label><br/>
                    <label>
                        <input type="checkbox" name="inject_hatom_on_posts_page" <?php echo esc_attr( get_option('inject_hatom_on_posts_page') ) == 'on' ? 'checked="checked"' : ''; ?> /> Posts Page <code>is_home()</code>
                    </label>
                </td>
            </tr>

            <tr>
            	<td><?php submit_button(); ?></td>
            </tr>
        </table>

     </form>
   </div>
 <?php
}


/**
 * Make sure class doesn't already exist
 */
	
if ( ! class_exists( 'HentryFixer' ) ) {
	
	/**
	 * Localisation
	 **/
	load_plugin_textdomain( 'HentryFixer', false, dirname( plugin_basename( __FILE__ ) ) . '/' );

	class HentryFixer {

	    /**
	     * constructor
	     */
		public function __construct() {
			add_filter( 'the_content', array( $this, 'hatom_data_in_content'), 100 );	
			add_filter( 'post_class', array( $this, 'remove_hentry_class'), 100 );	             			
		}

		/**
		 * hatom_data_in_content
		 * Checks post type and injects hatom data.
		 * @param string $content - html post content
		 * @return string 
		 **/
		public function hatom_data_in_content( $content ) {

			if ( get_option('inject_hatom_on_single_post_pages') == 'on' && is_single() && is_main_query() ) {
				$content .= $this->get_hatom_data(); 
			}
			if ( get_option('inject_hatom_on_archive_pages') == 'on' && is_archive() && is_main_query() ) {
		        echo $this->get_hatom_data(); 
			}
			if ( get_option('inject_hatom_on_posts_page') == 'on' && is_home() && is_main_query() ) {
				echo $this->get_hatom_data();
			}
			return $content;
		}
		 
		/**
		 * get_hatom_data
		 * Contstructs hatom/hentry markup for post content
		 * @return string 
		 **/
		private function get_hatom_data() {
		    $html  = '<div class="hatom-extra" style="display:none;visibility:hidden;">';
		    $html .= '<span class="entry-title">'.get_the_title().'</span>';
		    $html .= '<span class="updated"> '.get_the_modified_time('F jS, Y').'</span>';
		    $html .= '<span class="author vcard"><span class="fn">'.get_the_author().'</span></span></div>';
		    return $html;
		}
		 
		/**
		 * remove_hentry_class
		 * Removes "hentry" class from body tag of page
		 * @param string $classes - body tag classes
		 * @return string 
		 **/
		public function remove_hentry_class( $classes ) {

			if ( get_option('remove_hentry_class_from_pages') == 'on' && is_page() && is_main_query() ) {
				$classes = array_diff( $classes, array( 'hentry' ) );
			}
			return $classes;
		}

	}
	
	// finally instantiate our plugin class and add it to the set of globals
	$GLOBALS['HentryFixer'] = new HentryFixer();
}
