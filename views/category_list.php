<?php

/**
 * The Output of the Advanced Sidebar Categories Widget
 *
 * @since  7.0.0
 *
 * @todo split args into class similar to Advanced_Sidebar_Menu_List_Pages
 *
 * To edit create a file named category_list.php and put in a folder in the your
 * theme called 'advanced-sidebar-menu' copy the contents of the file into that file and edit at will
 *
 * @notice Do NOT edit this file in this location or it will break on update
 */
$asm = Advanced_Sidebar_Menu_Menus_Category::get_current();
$instance = $asm->get_widget_instance();
$content = '';

//Include the parent page if chosen
if( $asm->include_parent() ){
	$content .= '<ul class="parent-sidebar-menu">';
	$_args = array(
		'echo'       => 0,
		'orderby'    => $asm->order_by,
		'order'      => $asm->order,
		'taxonomy'   => $asm->taxonomy,
		'title_li'   => '',
		'hide_empty' => 0,
		'include'    => trim( $asm->top_id ),
	);
	$content .= $asm->openListItem( wp_list_categories( $_args ) );

}

//If there are children to display
if( !empty( $all_categories ) ){
	$content .= '<ul class="child-sidebar-menu">';

	#-- If we want all the child categories displayed always
	if( $asm->display_all() ){
		$_args = array(
			'echo'     => 0,
			'orderby'  => $asm->order_by,
			'order'    => $asm->order,
			'taxonomy' => $asm->taxonomy,
			'title_li' => '',
			'child_of' => $asm->top_id,
			'depth'    => $instance[ 'levels' ],
			'exclude'  => $instance[ 'exclude' ],
			'show_option_none' => false,
		);
		$content .= wp_list_categories( $_args );
	} else {

		#-- to Display the categories based a parent child relationship
		foreach( $all_categories as $child_cat ){

			//IF this is a child category of the top one
			if( $asm->first_level_category( $child_cat ) ){

				//List the child category and the children if it is the current one
				$_args = array(
					'echo'     => 0,
					'orderby'  => $asm->order_by,
					'order'    => $asm->order,
					'taxonomy' => $asm->taxonomy,
					'title_li' => '',
					'include'  => $child_cat->term_id,
					'depth'    => 1,
					'show_option_none' => false,

				);
				$content .= $asm->openListItem( wp_list_categories( $_args ) );

				//If there are children of this cat and it is a parent or child or the current cat
				if( $asm->second_level_cat( $child_cat ) ){
					#-- Create a new menu with all the children under it
					$content .= '<ul class="grandchild-sidebar-menu children">';
					$_args = array(
						'echo'     => 0,
						'orderby'  => $asm->order_by,
						'order'    => $asm->order,
						'taxonomy' => $asm->taxonomy,
						'title_li' => '',
						'exclude'  => $instance[ 'exclude' ],
						'depth'    => 3,
						'child_of' => $child_cat->term_id,
						'show_option_none' => false,
					);
					$content .= wp_list_categories( $_args );
					$content .= '</ul>';

				}

				$content .= '</li>';
			}
		}

	}

	$content .= '</ul><!-- End #child-sidebar-menu -->';

}

#-- if a parent category was displayed
if( $asm->include_parent() ){
	$content .= '</li></ul><!-- End #parent-sidebar-menu -->';
}

return $content;