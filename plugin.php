<?php
if ( is_admin() && $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {    
    ini_set('display_errors', 'on');
    error_reporting( E_ALL );
}

/**
 * Registers Custom post type: "Quote" with Quote taxonoimes: 
 */

/**
 * Plugin Name: Quote Tracker
 * Plugin URI: --
 * Description: A way to record your most inspirational Quotes
 * Version: .alpha
 * Author: Zane M. Kolnik
 * Author URI: http://zanematthew.com/
 * License: GP
 */


/**
 *
 * Libraries/Actions
 * @todo find a way to autoload libraries. Maybe
 * $lib_dir = 'path/to/lib';
 * for ( $dir in $lib_dir ) 
 *     require_once plugin_dir_path( __FILE__ ) . $lib_dir . $dir_name . 'functions.php';
 */
require_once plugin_dir_path( __FILE__ ) . 'library/zm-cpt/abstract.php';
require_once plugin_dir_path( __FILE__ ) . 'library/zm-ajax/functions.php';
require_once plugin_dir_path( __FILE__ ) . 'library/inplace-edit/functions.php';
require_once plugin_dir_path( __FILE__ ) . 'library/hash/functions.php';
require_once plugin_dir_path( __FILE__ ) . 'library/zm-wordpress-helpers/functions.php';


// Unique Templates for this Plugin
require_once plugin_dir_path( __FILE__ ) . 'actions.php';

/**
 * The following is not needed only helpful
 */
$_zm_cpt = 'zm-quote-tracker';

require_once plugin_dir_path( __FILE__ ) . $_zm_cpt . '.class.php';

$_zm_taxonomies = array(
    'book',
    'movie',
    'song',
    'people',        
    'zm-quote-tag'
    );

/**
 * Init our object
 */
$_GLOBALS[ $_zm_cpt ] = new QuotePostType();

/**
 * Build our Custom Post Type
 * These map back to the 'zm-quote-tracker.class.php'
 */
$_GLOBALS[ $_zm_cpt ]->post_type = array(
    array(
        'name' => 'Quote',
        'type' => $_zm_cpt,
        'rewrite' => array( 
            'slug' => 'quote-archive'
            ),
        'supports' => array(
            'title',            
            'excerpt',
            'editor',     
            'comments'
        ),
        'taxonomies' => $_zm_taxonomies
    )
);

/**
 * Build our taxonomies
 * These map back to the 'zm-quote-tracker.class.php'
 */
$_GLOBALS[ $_zm_cpt ]->taxonomy = array(
    array(
        'name' => 'book',
        'post_type' => $_zm_cpt,
        ),
    array(
        'name' => 'movie',
        'post_type' => $_zm_cpt,
        ),
    array(
        'name' => 'song',
        'post_type' => $_zm_cpt,
        ),
    array(
        'name' => 'people',
        'post_type' => $_zm_cpt,
        ),
    array(
        'name' => 'zm-quote-tag',
        'post_type' => $_zm_cpt,
        'menu_name' => 'Quote Tag',
        'singular_name' => 'Quote Tag'
        )                                
);