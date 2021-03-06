<?php 

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*

THIS IS THE FUNCTION THAT BUILDS THE DISPLAY OF CREATED CLONES ON THE CLONE POST TYPE

*/

function clone_finder(){
    if( have_rows('examples') ): // are there any examples?
    $clone_html = '';
    $title = '';
    $allowed_view = get_field('visible_to');// get the field of allowed viewers
    $current_user = get_current_user_id();
    $view_ok = in_array($current_user, $allowed_view);
    // loop through the rows of data
    while ( have_rows('examples') ) : the_row();
        $name = get_sub_field('name');
        $url = get_sub_field('url');
        $description = get_sub_field('description');
        $display = get_sub_field('display');
        if ($display == "True" || $view_ok || current_user_can('administrator')) {//set to show if set True in ACF, if user is in view list, or if user can admin

        	$title = "<h2>Example Sites</h2>";
            $clone_html = '<div class="clone-example"><a href="'.$url.'"><h3>' . $name . '</h3></a><div class="clone-description">' . $description . '</div></div>' . $clone_html;  
        }

    endwhile;
    return $title . $clone_html;

    else :

        // no rows found

    endif;
}