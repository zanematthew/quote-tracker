<?php

function zm_base_inplace_edit( $post_id=null ) {
	
	wp_enqueue_script( 'inplace-edit-script', plugin_dir_url( __FILE__ ) . 'library/inplace-edit/inplace-edit.js',              array('jquery'), '0.1' );                        
    wp_enqueue_style(  'inplace-edit-style',  plugin_dir_url( __FILE__ ) . 'library/inplace-edit/inplace-edit.css',             '', 'all' );
    
    ?>
    <script type="text/javascript">
    _post_id = <?php $post_id; ?>
    jQuery(document).ready(function( $ ){
    /**
     * Check if the inPlaceEdit plugin is loaded    
     */
    if ( jQuery().inPlaceEdit ) {

        if ( typeof _post_id !== "undefined" && $(".post-title").length ) {
            $(".post-title").inPlaceEdit({ 
                    postId: _post_id, 
                    field: "title" 
            });

            $(".post-content").inPlaceEdit({ 
                    postId: _post_id, 
                    field: "content" 
            });

            $(".post-excerpt").inPlaceEdit({ 
                    postId: _post_id, 
                    field: "excerpt" 
            });            
        } else {
        	console.log( 'post id is not in global scope' );
        }
    }
});
    </script>
<?php 
} 

// add_action( $tag, $function_to_add, $priority, $accepted_args );
add_action( 'inplace_edit', 'zm_base_inplace_edit', 10, 1 );  
