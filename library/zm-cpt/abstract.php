<?php
/** 
 * 
 * This is used to regsiter a custom post type, custom taxonomy and provide template redirecting.
 * 
 * This abstract class defines some base functions for using Custom Post Types. You should not have to
 * edit this abstract, only add additional methods if need be. You must use what is provided for you
 * in the interface.
 *
 */
require_once 'interface.php';
if ( ! class_exists( 'zMCustomPostTypeBase' ) ) :
abstract class zMCustomPostTypeBase implements zMICustomPostType {

    public function __construct() {

        add_filter( 'post_class', array( &$this, 'addPostClass' ) );            
        add_action( 'wp_ajax_postTypeUpdate', array( &$this, 'postTypeUpdate' ) );
        add_action( 'wp_ajax_postTypeDelete', array( &$this, 'postTypeDelete' ) );
        add_action( 'wp_ajax_defaultUtilityUpdate', array( &$this, 'defaultUtilityUpdate' ) );                
        add_action( 'wp_ajax_addComment', array( &$this, 'addComment' ) );        
        add_action( 'wp_ajax_nopriv_loadTemplate', array( &$this, 'loadTemplate' ) );
        add_action( 'wp_ajax_loadTemplate', array( &$this, 'loadTemplate' ) );         
        add_action( 'wp_head', array( &$this, 'baseAjaxUrl' ) );                    
        add_action( 'wp_footer', array( &$this, 'createPostTypeDiv' ) );            
        add_action( 'wp_footer', array( &$this, 'createDeleteDiv' ) );                            

        wp_enqueue_script( 'zm-login', plugin_dir_url( __FILE__ ) . 'js/login.js',   array('jquery'), '0.1' );                        
        wp_enqueue_style(  'zm-login', plugin_dir_url( __FILE__ ) . 'css/login.css', '', 'all' );

        $this->loginSetup();
    }

    /**
     * Regsiter an unlimited number of CPTs based on an array of parmas.
     * 
     * @uses register_post_type()
     * @uses wp_die()
     *
     * @todo re-map more stuff, current NOT ALL the args are params
     */
    public function registerPostType( $args=NULL ) {
        $taxonomies = $supports = array();

        // our white list taken from http://codex.wordpress.org/Function_Reference/register_post_type see 'supports'
        $white_list = array();
        
        // Default, title, editor
        $white_list['supports'] = array(
                'title',
                'editor',
                'author',
                'thumbnail',
                'excerpt',
                'comments',
                'custom-fields',
                'trackbacks'
                );
                    
        foreach ( $this->post_type as $post_type ) {

            if ( !empty( $post_type['taxonomies'] ) )                                
                $taxonomies = $post_type['taxonomies'];            
        
            $post_type['type'] = strtolower( $post_type['type'] );
            
            if ( empty( $post_type['singular_name'] ) )
                $post_type['singular_name'] = $post_type['name'];

            // @todo white list rewrite array
            if ( !is_array( $post_type['rewrite'] ) ) {
                $rewrite = true;
            } else {
                $rewrite = $post_type['rewrite'];
            }

            $labels = array(
                'name' => _x( $post_type['name'], 'post type general name'),
                'singular_name' => _x( $post_type['singular_name'], 'post type singular name'),
                'add_new' => _x('Add New ' . $post_type['singular_name'] . '', 'something'),
                'add_new_item' => __('Add New ' . $post_type['singular_name'] . ''),
                'edit_item' => __('Edit '. $post_type['singular_name'] .''),
                'new_item' => __('New '. $post_type['singular_name'] .''),
                'view_item' => __('View '. $post_type['singular_name'] . ''),
                'search_items' => __('Search ' . $post_type['singular_name'] . ''),
                'not_found' => __('No ' . $post_type['singular_name'] . ' found'),
                'not_found_in_trash' => __('No ' . $post_type['singular_name'] . ' found in Trash'),
                'parent_item_colon' => ''
                );

            foreach ( $post_type['supports'] as $temp ) {

                if ( in_array( $temp, $white_list['supports'] ) ) {
                    array_push( $supports, $temp );
                } else {
                    wp_die('gtfo with this sh!t: <b>' . $temp . '</b> it ain\'t in my white list mofo!' );
                }
            }
             
            // @todo make defaults optional
            $args = array(
                'labels' => $labels,
                'public' => true,
                'capability_type' => 'bmx-race-schedule',
                //'capability_type' => 'post',               
                'map_meta_cap' => true,                 
/*
                'capabilities' => array(
                                'publish_posts' => 'publish_bmx-race-schedules',
                                'edit_posts' => 'edit_bmx-race-schedules',
                                'edit_others_posts' => 'edit_others_bmx-race-schedules',
                                'delete_posts' => 'delete_bmx-race-schedules',
                                'delete_others_posts' => 'delete_others_bmx-race-schedules',
                                'read_private_posts' => 'read_private_bmx-race-schedules',
                                'edit_post' => 'edit_bmx-race-schedule',
                                'delete_post' => 'delete_bmx-race-schedule',
                                'read_post' => 'read_bmx-race-schedule',
                            ),                
*/                            
                'supports' => $supports,
                'rewrite' => $rewrite,
                'hierarchical' => true,
                'description' => 'None for now GFYS',
                'taxonomies' => $taxonomies,
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'menu_position' => 5,
                'show_in_nav_menus' => true,
                'publicly_queryable' => true,
                'exclude_from_search' => false,
                'has_archive' => true,
                'query_var' => true,
                'can_export' => true                
                );

            register_post_type( $post_type['type'], $args);

        } // End 'foreach'         

        return $this->post_type;
    } // End 'function'

    /**
     * Wrapper for register_taxonomy() to register an unlimited
     * number of taxonomies for a given CPT.
     *     
     * @uses register_taxonomy
     *
     * @todo re-map more stuff, current NOT ALL the args are params
     * @todo this 'hierarchical' => false' fucks up on wp_set_post_terms() for submitting and updating a cpt
     */
    public function registerTaxonomy( $args=NULL ) {
        foreach ( $this->taxonomy as $taxonomy ) {
            
            if ( empty( $taxonomy['taxonomy'] ) )
                $taxonomy['taxonomy'] = strtolower( str_replace( " ", "-", $taxonomy['name'] ) );

            if ( empty( $taxonomy['slug'] ) )
                $taxonomy['slug'] = $taxonomy['taxonomy'];
            
            if ( empty( $taxonomy['singular_name'] ) )
                $taxonomy['singular_name'] = $taxonomy['name'];

            if ( empty( $taxonomy['plural_name'] ) )
                $taxonomy['plural_name'] = $taxonomy['name'] . 's';

            if ( !isset( $taxonomy['hierarchical'] ) ) {
                $taxonomy['hierarchical'] = true;
            }
            
            if ( empty( $taxonomy['menu_name'] ) ) {
                $taxonomy['menu_name'] = $taxonomy['name'];
            }

            $labels = array(
                'name'              => _x( $taxonomy['name'], 'taxonomy general name' ),
                'singular_name'     => _x( $taxonomy['singular_name'], 'taxonomy singular name' ),
                'search_items'      => __( 'Search ' . $taxonomy['plural_name'] . ''),
                'all_items'         => __( 'All ' . $taxonomy['plural_name'] . '' ),
                'parent_item'       => __( 'Parent ' . $taxonomy['singular_name'] . '' ),
                'parent_item_colon' => __( 'Parent ' . $taxonomy['singular_name'] . ': ' ),
                'edit_item'         => __( 'Edit ' . $taxonomy['singular_name'] . '' ),
                'update_item'       => __( 'Update ' . $taxonomy['singular_name'] . ''),
                'add_new_item'      => __( 'Add New ' . $taxonomy['singular_name'] . ''),
                'new_item_name'     => __( 'New ' . $taxonomy['singular_name'] . ' Name' ),
                'menu_name'         => __( $taxonomy['menu_name'] )
                );

            $args = array(               
                'labels'  => $labels,
                'hierarchical' => $taxonomy['hierarchical'],
                'query_var' => true,
                'public' => true,
                'rewrite' => array('slug' => $taxonomy['slug']),
                'show_in_nav_menus' => true,
                'show_ui' => true,
                'show_tagcloud' => true
                );
                
            register_taxonomy( $taxonomy['taxonomy'], $taxonomy['post_type'], $args );
            
        } // End 'foreach'
       
        return $this->taxonomy;
    } // End 'function'   
    
    /** 
     * loads a template from a specificed path
     *
     * @package Ajax
     *
     * @uses load_template()
     */
    public function loadTemplate() {

        $template = $_POST['template'];
    
        if ( $template == null )
            wp_die( 'Yo, you need a template!');
    
        load_template( $template );
        die();
    } // loadTemplate    

    /**
     * Inserts a comment for the current post if the user is logged in
     *
     * @package Ajax
     *
     * @uses is_user_logged_in()
     * @uses wp_insert_comment()
     * @uses wp_get_current_user()
     * @uses current_time()
     *
     * @todo add check_ajax_refer()
     */
    public function addComment() {
        
        if ( !is_user_logged_in() )
            return false;
        
        if ( !empty( $_POST['comment'] ) ) {

            $current_user = wp_get_current_user();
            
            $post_id = (int)$_POST['post_id'];

            $time = current_time('mysql');
            $data = array(
                'comment_post_ID' => $post_id,
                'comment_author' => $current_user->user_nicename,
                'comment_author_email' => $current_user->user_email,
                'comment_author_url' => $current_user->user_url,
                'comment_content' => $_POST['comment'],
                'comment_type' => '',
                'comment_parent' => 0,
                'user_id' => $current_user->ID,
                'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
                'comment_agent' => $_SERVER['HTTP_USER_AGENT'],
                'comment_date' => $time,
                'comment_approved' => 1
                );
                
            wp_insert_comment( $data );
        }
        die();
    } // End 'commentAdd'

    /**
     * Delets a post given the post ID, post will be moved to the trash
     *
     * @package Ajax
     *
     * @param (int) post id
     *
     * @uses check_ajax_referer
     * @uses is_wp_error
     * @uses is_user_logged_in
     * @uses wp_trash_post     
     *
     * @todo generic validateUser method, check ajax refer and if user can (?)     
     */
    public function postTypeDelete( $id=null ) {
        
        check_ajax_referer( 'tt-ajax-forms', 'security' );

        $id = (int)$_POST['post_id'];

        if ( !is_user_logged_in() )
            return false;        

        if ( is_null( $id )  ) {
            wp_die( 'I need a post_id to kill!');
        } else {        
            $result = wp_trash_post( $id );
            if ( is_wp_error( $result ) ) {                
                print_r( $result );
            } else {
                print_r( $result );
            }
        }

        die();
    } // postTypeDelete

    /**
     * Print our ajax url in the footer 
     *
     * @uses plugin_dir_url()
     * @uses admin_url()
     *
     * @todo baseAjaxUrl() consider moving to abstract
     * @todo consider using localize script     
     */
    public function baseAjaxUrl() {
        // @todo use localize for this
        // http://www.garyc40.com/2010/03/5-tips-for-using-ajax-in-wordpress/#js-global
        print '<script type="text/javascript"> var ajaxurl = "'. admin_url("admin-ajax.php") .'"; var _pluginurl="'. plugin_dir_url( __FILE__ ) .'";</script>';    
    } // End 'baseAjaxUrl'
    
    /**
     * Adds additional classes to post_class() for additional CSS styling and JavaScript manipulation.     
     * term_slug-taxonomy_id
     *
     * @param classes
     *
     * @uses get_post_types()
     * @uses get_the_terms()
     * @uses is_wp_error()
     */
    public function addPostClass( $classes ) {
        global $post;
        $cpt = $post->post_type;
                    
        $cpt_obj = get_post_types( array( 'name' => $cpt ), 'objects' );

        foreach( $cpt_obj[ $cpt ]->taxonomies  as $name ) {
            $terms = get_the_terms( $post->ID, $name );
            if ( !is_wp_error( $terms ) && !empty( $terms )) {
                foreach( $terms as $term ) {
                    $classes[] = $name . '-' . $term->term_id;
                }
            } 
        }        
        return $classes;
    } // End 'addPostClass'  
    
    /**
     * To be used in AJAX submission, gets the $_POST data and logs the user in.
     *
     * @uses check_ajax_referer()
     * @uses wp_signon()
     * @uses is_wp_error()
     *
     * @todo move this to the abtract!
     */    
    public function siteLoginSubmit() {

        check_ajax_referer( 'tt-ajax-forms', 'security' );
        
        $creds = array();
        $creds['user_login'] = $_POST['user_name'];
        $creds['user_password'] = $_POST['password'];

        if ( $_POST['remember'] == 'on' ) {
            $creds['remember'] = true;
        } else {
            $creds['remember'] = false;
        }

        $user = wp_signon( $creds, false );

        if ( is_wp_error( $user ) )
            $user->get_error_message();

        die();
    } // siteLoginSubmit

    /**
     * 
     */
    public function createLoginDiv(){ ?>
    <div id="login_dialog" class="dialog-container">
            <div id="login_target" style="display: none;">login hi</div>
        </div>
    <?php }
      
    /**
     * Login set-up
     * 
     * Note this does NOT hook into the default WordPress login! In essence 
     * you will need custom mark-up. Telling it which template to call
     * and create you own, see theme/default/login.php 
     *
     * @uses add_action()
     * @uses wp_enqueue_style()
     * @uses wp_enqueue_script()
     * @uses plugin_dir_url()
     * @todo segment this!
     */
    public function loginSetup() {
        wp_enqueue_script( 'login-script', plugin_dir_url( __FILE__ ) . 'js/login.js', array('jquery','tt-script'), '1.0' );

        add_action( 'wp_footer', array( &$this, 'createLoginDiv' ) );            
        add_action( 'wp_ajax_siteLoginSubmit', array( &$this, 'siteLoginSubmit' ) );        
        add_action( 'wp_ajax_nopriv_siteLoginSubmit', array( &$this, 'siteLoginSubmit' ) ); 

        $dependencies['style'] = array(
            'tt-base-style',
            'wp-jquery-ui-dialog'
        );
        
        wp_enqueue_style( 'wp-jquery-ui-dialog' );
        wp_enqueue_style( 'tt-login-style', plugin_dir_url( __FILE__ ) . 'css/login.css', $dependencies['style'], 'all' );
        wp_enqueue_script( 'jquery-ui-effects' );
    } // End 'loginSetup'

    /**
     * @todo DON'T print from an index, print from $i
     */
    public function createPostTypeDiv(){ 
        if ( is_user_logged_in() ) : ?>
        <div id="create_ticket_dialog" class="dialog-container" title="Add a new <em><?php print $this->post_type[0]['name']; ?></em>">
            <div id="create_ticket_target" style="display: none;"></div>
        </div>
        <?php endif;
    } 

    /**
     * @todo this should not load if there is NO 'cpt'
     */
    public function createDeleteDiv(){         
        if ( is_user_logged_in() ) : ?>
        <div id="delete_dialog" class="dialog-container" style="display: none;">
            <p>These items will be permanently deleted and cannot be recovered. Are you sure?</p>
            <div id="delete_target" style="display: none"></div>
        </div>
        <?php endif;
    }

} // End 'CustomPostTypeBase'
endif;