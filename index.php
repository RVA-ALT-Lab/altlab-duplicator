<?php 
/*
Plugin Name: ALT Lab Duplicator
Plugin URI:  https://github.com/
Description: Let's clone sites via gravity form & NS Cloner
Version:     1.0
Author:      Tom Woodward
Author URI:  http://altlab.vcu.edu
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: altlab-duplicator

*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


add_action('wp_enqueue_scripts', 'opened_duplicator_scripts');

$form_id = RGFormsModel::get_form_id('duplicate site');

function opened_duplicator_scripts() {                           
    $deps = array('jquery');
    $version= '1.0'; 
    $in_footer = true;    
    wp_enqueue_script('altlab-dup-main-js', plugin_dir_url( __FILE__) . 'js/altlab-dup-main.js', $deps, $version, $in_footer); 
    wp_enqueue_style( 'altlab-dup-main-css', plugin_dir_url( __FILE__) . 'css/altlab-dup-main.css');
}

add_action( 'gform_after_submission_' . $form_id, 'gform_site_cloner', 10, 2 );//specific to the gravity form id

function gform_site_cloner($entry, $form){
    /**
 FROM https://neversettle.it/documentation/ns-cloner/call-ns-cloner-copy-sites-plugins/
 **/
 $request = array(
      'clone_mode'     => 'core',
      'source_id'      => rgar( $entry, '1' ), // any blog/site id on network
      'target_name'    => rgar( $entry, '3' ),
      'target_title'   => rgar( $entry, '2' ),
      //'debug'          => 1 // optional: enables logs
  );



// Register request with the cloner.
foreach ( $request as $key => $value ) {
   ns_cloner_request()->set( $key, $value );
}

// Get the cloner process object.
$cloner = ns_cloner()->process_manager;

// Begin cloning.
$cloner->init();

// Check for errors (from invalid params, or already running process).
$errors = $cloner->get_errors();
    if ( ! empty( $errors ) ) {
       // Handle error(s) and exit
    }

}

//add created sites to cloner posts


add_action( 'gform_after_submission_' . $form_id, 'gform_new_site_to_acf', 10, 2 );//specific to the gravity form id

function gform_new_site_to_acf($entry, $form){
    $form_title = rgar( $entry, '2' );
    $form_url = rgar( $entry, '3' );
    $clone_form_id = (int)rgar( $entry, '1');
   
     $posts = get_posts( 'numberposts=-1&post_status=publish&post_type=clone' ); 
        foreach ( $posts as $post ) {
            $url = get_field('site_url', $post->ID);
            $main = parse_url($url);//probably need to add a check for trailing slash
            $arg = array(
                'domain' => $main['host'],
                'path' => $main['path']
            );
            $blog_details = get_blog_details($arg);

            $clone_id = (int)$blog_details->blog_id;  

            if ($clone_id === $clone_form_id){
                $post_id = $post->ID;
            }
        }

    $row = array(
        'name'   => $form_title,
        'url'  => 'https://rampages.us/' .$form_url,
        'description' => '',
        'display' => 'False'
    );

    $i = add_row('examples', $row, $post_id);
}

//GRAVITY FORM PROVISIONING BASED ON CLONE POSTS
add_filter( 'gform_pre_render_'.$form_id, 'populate_posts' );
add_filter( 'gform_pre_validation_'.$form_id, 'populate_posts' );
add_filter( 'gform_pre_submission_filter_'.$form_id, 'populate_posts' );
add_filter( 'gform_admin_pre_render_'.$form_id, 'populate_posts' );
function populate_posts( $form ) {
 
    foreach ( $form['fields'] as &$field ) {
 
        if ( $field->id != 1 ) {
            continue;
        }
 
        // you can add additional parameters here to alter the posts that are retrieved
        // more info: http://codex.wordpress.org/Template_Tags/get_posts
        $posts = get_posts( 'numberposts=-1&post_status=publish&post_type=clone' );
 
        $choices = array();
 
        foreach ( $posts as $post ) {
            $url = get_field('site_url', $post->ID);
            // $parsed = parse_url($url);
            // $clone_id = get_blog_id_from_url($parsed['host']);

            $main = parse_url($url);//probably need to add a check for trailing slash
            $arg = array(
                'domain' => $main['host'],
                'path' => $main['path']
            );
            $blog_details = get_blog_details($arg);

            $clone_id = $blog_details->blog_id;   

            $choices[] = array( 'text' => $post->post_title, 'value' => $clone_id);
        }
 
        // update 'Select a Post' to whatever you'd like the instructive option to be
        $field->placeholder = 'Select a site to clone';
        $field->choices = $choices;
 
    }
 
    return $form;
}

/*
CREATE CLONE CUSTOM POST TYPE
*/

// Register Custom Post Type clone
// Post Type Key: clone

function create_clone_cpt() {

  $labels = array(
    'name' => __( 'Clones', 'Post Type General Name', 'textdomain' ),
    'singular_name' => __( 'Clone', 'Post Type Singular Name', 'textdomain' ),
    'menu_name' => __( 'Clone', 'textdomain' ),
    'name_admin_bar' => __( 'Clone', 'textdomain' ),
    'archives' => __( 'Clone Archives', 'textdomain' ),
    'attributes' => __( 'Clone Attributes', 'textdomain' ),
    'parent_item_colon' => __( 'Clone:', 'textdomain' ),
    'all_items' => __( 'All Clones', 'textdomain' ),
    'add_new_item' => __( 'Add New Clone', 'textdomain' ),
    'add_new' => __( 'Add New', 'textdomain' ),
    'new_item' => __( 'New Clone', 'textdomain' ),
    'edit_item' => __( 'Edit Clone', 'textdomain' ),
    'update_item' => __( 'Update Clone', 'textdomain' ),
    'view_item' => __( 'View Clone', 'textdomain' ),
    'view_items' => __( 'View Clones', 'textdomain' ),
    'search_items' => __( 'Search Clones', 'textdomain' ),
    'not_found' => __( 'Not found', 'textdomain' ),
    'not_found_in_trash' => __( 'Not found in Trash', 'textdomain' ),
    'featured_image' => __( 'Featured Image', 'textdomain' ),
    'set_featured_image' => __( 'Set featured image', 'textdomain' ),
    'remove_featured_image' => __( 'Remove featured image', 'textdomain' ),
    'use_featured_image' => __( 'Use as featured image', 'textdomain' ),
    'insert_into_item' => __( 'Insert into clone', 'textdomain' ),
    'uploaded_to_this_item' => __( 'Uploaded to this clone', 'textdomain' ),
    'items_list' => __( 'Clone list', 'textdomain' ),
    'items_list_navigation' => __( 'Clone list navigation', 'textdomain' ),
    'filter_items_list' => __( 'Filter Clone list', 'textdomain' ),
  );
  $args = array(
    'label' => __( 'clone', 'textdomain' ),
    'description' => __( '', 'textdomain' ),
    'labels' => $labels,
    'menu_icon' => '',
    'supports' => array('title', 'editor', 'revisions', 'author', 'trackbacks', 'custom-fields', 'thumbnail',),
    'taxonomies' => array('category'),
    'public' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_position' => 5,
    'show_in_admin_bar' => true,
    'show_in_nav_menus' => true,
    'can_export' => true,
    'has_archive' => true,
    'hierarchical' => false,
    'exclude_from_search' => false,
    'show_in_rest' => true,
    'publicly_queryable' => true,
    'capability_type' => 'post',
    'menu_icon' => 'dashicons-universal-access-alt',
  );
  register_post_type( 'clone', $args );
  
  // flush rewrite rules because we changed the permalink structure
  global $wp_rewrite;
  $wp_rewrite->flush_rules();
}
add_action( 'init', 'create_clone_cpt', 0 );


//GET URL OF CLONE SITE
function acf_fetch_site_url(){
  global $post;
  $html = '';
  $site_url = get_field('site_url');
    if( $site_url) {      
      $html = $site_url;  
     return $html;    
    }

}

//GET SITE ID OF CLONE SITE
function build_site_clone_button($content){
    global $post;
    if ($post->post_type === 'clone'){
       $button = clone_button_maker(); 
       $clone_examples = clone_finder(); 
        return $content . $button . $clone_examples;
    }
    else {
        return $content;
    }
}

add_filter( 'the_content', 'build_site_clone_button' );

//builds clone button link
function clone_button_maker($form_id){
    global $post;
    $url = acf_fetch_site_url($post->ID);
    $main = parse_url($url);//probably need to add a check for trailing slash
    $arg = array(
        'domain' => $main['host'],
        'path' => $main['path']
    );
    $blog_details = get_blog_details($arg);

    $site_id = $blog_details->blog_id;   
    return '<a class="dup-button" href="' . get_site_url() . '/clone-zone?cloner=' . $site_id . '#field_'. $form_id .'_2">Clone it to own it!</a>';
}


//builds clone example list
function clone_finder(){
    if( have_rows('examples') ):
    $clone_html = '';
    $clone_html = '<h2>Example Sites</h2>';
    // loop through the rows of data
    while ( have_rows('examples') ) : the_row();
        // display a sub field value
        $name = get_sub_field('name');
        $url = get_sub_field('url');
        $description = get_sub_field('description');
        $display = get_sub_field('display');
        if ($display == "True") {
            $clone_html .= '<div class="clone-example"><a href="'.$url.'"><h3>' . $name . '</h3></a><div class="clone-description">' . $description . '</div></div>';  
        }

    endwhile;
    return $clone_html;

    else :

        // no rows found

    endif;
}


//save ACF data
add_filter('acf/settings/save_json', 'my_acf_json_save_point');
 
function my_acf_json_save_point( $path ) {
    
    // update path
    $path = plugin_dir_path( __FILE__) . '/import-data';
    
    
    // return
    return $path;
    
}


function create_clone_zone_page(){
    $exists =   get_page_by_title( 'clone-zone' );
    if(!$exists){
        $my_post = array(
          'post_title'    => 'clone-zone',
          'post_content'  => '',
          'post_status'   => 'publish',
          'post_author'   => 1,
          'post_category' => array( 8,39 )
        );
         
        // Insert the post into the database
        wp_insert_post( $my_post );
    }
}