<?php
/*
Plugin Name: WP Hentry Fixer
Plugin URI: https://github.com/michaeldoye/wp-hentry-fixer
Description: Fixes missing hentry errors for single posts and archive pages.
Author: Web SEO Online (PTY) LTD
Author URI: https://webseo.co.za
Version: 0.1.2

  Copyright: © 2016 Web SEO Online (PTY) LTD (email : michael@webseo.co.za)
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
  
if (!defined('ABSPATH')) die ('No direct access allowed');

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
    register_setting( 'wp-hentry-fixer-settings', 'remove_hentry_class_from_attachment_pages' );
    register_setting( 'wp-hentry-fixer-settings', 'inject_hatom_on_archive_pages' );
    register_setting( 'wp-hentry-fixer-settings', 'inject_hatom_on_single_post_pages' );
    register_setting( 'wp-hentry-fixer-settings', 'inject_hatom_on_posts_page' );
    register_setting( 'wp-hentry-fixer-settings', 'redirect_attachment_pages_to_parent_else_home' );
    register_setting( 'wp-hentry-fixer-settings', 'add_woocommerce_product_schema' );
    $available_post_types = get_post_types( array('public' => true, '_builtin' => false), 'objects' );
    foreach ( $available_post_types as $post_type ) {
      register_setting( 'wp-hentry-fixer-settings', 'detected_post_type_'.$post_type->name );
    }
});


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
     **/
    public function __construct() {

      add_filter( 'the_content', array( $this, 'hatom_data_in_content'), 100 ); 
      add_filter( 'post_class', array( $this, 'remove_hentry_class'), 100 );
      if ( get_option('redirect_attachment_pages_to_parent_else_home') == 'on') {
      	add_action( 'template_redirect', array( $this, 'redirect_attachment_pages' ), 100 );                    
      }
      if ( get_option('add_woocommerce_product_schema') == 'on') {
        add_filter( 'wp_head', array( $this, 'add_ld_script') );          
      }

    }

    /**
     * hatom_data_in_content
     * Checks post type and injects hatom data.
     * @param string $content - html post content
     * @return string 
     **/
    public function hatom_data_in_content( $content ) {

      if ( get_option('inject_hatom_on_single_post_pages') == 'on' && is_single() && is_singular( 'post' ) && is_main_query() ) { /* Inject only into standard post - not custom post_types */
        $content .= $this->get_hatom_data(); 
      }
      if ( get_option('inject_hatom_on_archive_pages') == 'on' && is_archive() && is_main_query() ) {
        echo $this->get_hatom_data(); 
      }
      if ( get_option('inject_hatom_on_posts_page') == 'on' && is_home() && is_main_query() ) {
        echo $this->get_hatom_data();
      }
      $available_post_types = get_post_types( array('public' => true, '_builtin' => false), 'objects' );
      if($available_post_types) {
        $current_post_type = get_post_type( $post->ID );
        foreach ( $available_post_types as $post_type ) {
          if ( get_option('detected_post_type_'.$post_type->name) == 'on' && $post_type->name == $current_post_type && is_main_query() ) {
            /* Since we can't guarantee that all custom post_types will always use the_content() all the time, better to just echo it into the page and not append it to $content */
            if($counter == 0){ /* check if we've already inserted */
                echo $this->get_hatom_data();
                $counter++; /* make sure we only insert it once */
            }
          }
        }
      }
      return $content;

    }
     
    /**
     * get_hatom_data
     * Contstructs hatom/hentry markup for post content
     * @return string 
     **/
    private function get_hatom_data() {

      $html  = '<!-- WP Hentry Fixer -->';
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
      if ( get_option('remove_hentry_class_from_attachment_pages') == 'on' && is_attachment() && is_main_query() ) {
        $classes = array_diff( $classes, array( 'hentry' ) );
      }
      return $classes;

    }
     
    /**
     * redirect_attachment_pages
     * Redirect attachment pages to post parent, or home page if no post parent is found
     * @return boolean False when no redirect was triggered
     **/
    public function redirect_attachment_pages() {

  		global $post;
	    if ( is_attachment() && ( ( is_object( $post ) && isset( $post->post_parent ) ) && ( is_numeric( $post->post_parent ) && $post->post_parent != 0 ) ) ) {
	      wp_safe_redirect( get_permalink( $post->post_parent ), 301 );
	      exit;
	    } elseif ( is_attachment() && $post->post_parent == 0 )  {
	      wp_safe_redirect( home_url( '/' ), 301 );
	      exit;
	    }
		  return false;

    }

    /**
    * add_ld_script
    * Checks post type and injects JSON LD data.
    **/
    public function add_ld_script() {

      if ( get_option('add_woocommerce_product_schema') == 'on' && in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        global $woocommerce, $post;
        $product = wc_get_product( $post->ID );
        $terms = get_the_terms( $post->ID, 'product_cat' );
        $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' );
        if ( is_product() ) { ?>
          <!-- JSON-LD Script -->
          <script type="application/ld+json">
          {
            "@context": "http://schema.org/",
            "@type": "Product",
            <?php if( $product->get_title() ) : ?>
              "name": "<?php echo $product->get_title() ?>",
            <?php endif; ?>
            <?php if( $image[0] ) : ?>
              "image": "<?php echo $image[0] ?>",
            <?php endif; ?>
            <?php if( get_post( $post->ID )->post_content ) : ?>
              "description": "<?php echo get_post( $post->ID )->post_content ?>",
            <?php endif; ?>
            <?php if( $product->get_sku() ) : ?>
              "mpn": "<?php echo $product->get_sku() ?>",
            <?php endif; ?>
            "brand": {
              "@type": "Thing",
              <?php if( $terms[0]->name ) : ?>
                "name": "<?php echo $terms[0]->name ?>"
              <?php endif; ?>
            },
            "aggregateRating": {
              "@type": "AggregateRating",
              "ratingValue": "<?php echo $product->get_average_rating() ?>",
              "reviewCount": "<?php echo $product->get_review_count() ?>"
            },
            "offers": {
              "@type": "Offer",
              <?php if( get_woocommerce_currency() ) : ?>
                "priceCurrency": "<?php echo get_woocommerce_currency() ?>",
              <?php endif; ?>
              <?php if( $product->get_price() ) : ?>
                "price": "<?php echo $product->get_price() ?>",
              <?php endif; ?>
              "itemCondition": "http://schema.org/UsedCondition",
              "availability": "http://schema.org/InStock",
              "seller": {
              "@type": "Organization",
              "name": "Executive Objects"
              }
            }
          }
          </script>
        <?php } 
      }

    }

  }
  
  // finally instantiate our plugin class and add it to the set of globals
  $GLOBALS['HentryFixer'] = new HentryFixer();
}

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
                    </label><br/>
                    <label>
                        <input type="checkbox" name="remove_hentry_class_from_attachment_pages" <?php echo esc_attr( get_option('remove_hentry_class_from_attachment_pages') ) == 'on' ? 'checked="checked"' : ''; ?> />Yes, remove <code>hentry</code> classes from attachment pages <code>is_attachment()</code>
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
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>

            <tr>
                <th align="left" valign="top">Redirect attachment pages to post parent,<br/>or home page if unattached to any post parent</th>
                <td>
                    <label>
                        <input type="checkbox" name="redirect_attachment_pages_to_parent_else_home" <?php echo esc_attr( get_option('redirect_attachment_pages_to_parent_else_home') ) == 'on' ? 'checked="checked"' : ''; ?> />Yes, redirect attachment pages to parent pages or the home page if <em>unattached</em>
                    </label>
                </td>
            </tr>

            <?php if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) : ?>

              <tr>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
              </tr>

              <tr>
                  <th align="left" valign="top">Add schema to WooCommerce product pages</th>
                  <td>
                      <label>
                          <input type="checkbox" name="add_woocommerce_product_schema" <?php echo esc_attr( get_option('add_woocommerce_product_schema') ) == 'on' ? 'checked="checked"' : ''; ?> /> WooCommerce Product Page <code>is_product()</code>
                      </label>
                  </td>
              </tr>

            <?php endif; ?>

            <?php
              $available_post_types = get_post_types( array('public' => true, '_builtin' => false), 'objects' );
              if($available_post_types){
                echo '<tr>';
                echo    '<td>&nbsp;</td>';
                echo    '<td>&nbsp;</td>';
                echo '</tr>';
                echo '<tr>';
                echo    '<th align="left" valign="top">Inject Hentry Data into Custom Post Types detected:</th>';
                echo    '<td>';
                foreach ( $available_post_types as $post_type ) {
                  echo '<label>';
                          echo '<input type="checkbox" name="detected_post_type_' . $post_type->name . '"';
                          echo get_option('detected_post_type_'.$post_type->name) == 'on' ? 'checked="checked"' : '';
                          echo '/>' . $post_type->label . ' Pages <code>is_singular( \'' . $post_type->name . '\' )</code><br/>';
                  echo '</label>';
                }
                echo    '</td>';
                echo '</tr>';
              }
            ?>

            <tr>
              <td><?php submit_button(); ?></td>
            </tr>
        </table>

     </form>
   </div>
 <?php
}
