<?php

/**
 * Advanced_Sidebar_Menu_List_Pages
 *
 * Parse and build the child and grandchild menus
 * Create the opening and closing <ul class="child-sidebar-menu">
 * in the view and this will fill in the guts.
 *
 * Send the args ( similar to wp_list_pages ) to the constructor and then
 * display by calling list_pages()
 *
 * @package Advanced Sidebar Menu
 *
 * @author Mat Lipe <mat@matlipe.com>
 *
 * @since 5.0.0
 *
 */
class Advanced_Sidebar_Menu_List_Pages{

	/**
	 * output
	 *
	 * The page list
	 *
	 * @var string
	 */
	public $output = '';

	/**
	 * current_page
	 *
	 * Used when walking the list
	 *
	 * @var WP_Post
	 */
	private $current_page = NULL;

	/**
	 * current_page_id
	 *
	 * Holds id of current page. Separate from current_page because
	 * current_page could be empty if something custom going on
	 *
	 * @var int
	 */
	private $current_page_id = 0;

	/**
	 * top_parent_id
	 *
	 * Id of current page unless filtered when whatever set during
	 * widgetcreation
	 *
	 * @var int
	 */
	private $top_parent_id = 0;

	/**
	 * args
	 *
	 * Passed during construct given to walker and used for queries
	 *
	 * @var array
	 */
	private $args = array();


	/**
	 * level
	 *
	 * Level of grandchild pages we are on
	 *
	 * @var int
	 */
	private $level = 0;


	/**
	 * Constructor
	 *
	 * Used in the view
	 *
	 * @param array $child_pages - array if page_ids
	 * @param array $args - see $this->fill_class_vars
	 */
	public function __construct( $parent_id, $args ){

		$this->top_parent_id = $parent_id;
		$this->fill_class_vars( $args );

	}


	/**
	 * __toString
	 *
	 * Magic method to allow using a simple echo to get output
	 *
	 * @return string
	 */
	public function __toString(){
		return $this->output;
	}



	/**
	 * fill_class_vars
	 *
	 * Do any adjustmens to class vars here
	 *
	 * @param $args
	 *
	 * @return void
	 */
	private function fill_class_vars( $args ){
		global $wp_query;

		$defaults = array(
			'depth'        => 1,
			'exclude'      => '',
			'echo'         => 0,
			'sort_column'  => 'menu_order, post_title',
			'walker'       => new Advanced_Sidebar_Menu_Page_Walker(),
			'hierarchical' => 0,
			'link_before'  => '',
			'link_after'   => '',
			'title_li'     => ''
		);

		$args = wp_parse_args( $args, $defaults );

		// sanitize, mostly to keep spaces out
		if( is_string( $args[ 'exclude' ] ) ){
			$args[ 'exclude' ]  = explode( ',', $args[ 'exclude' ] );
		}
		$args[ 'exclude' ] = preg_replace( '/[^0-9,]/', '', implode( ',', apply_filters( 'wp_list_pages_excludes', $args[ 'exclude' ] ) ) );

		$this->args = $args;

		if ( is_page() || is_singular() ) {
			$this->current_page = get_queried_object();
			$this->current_page_id = $this->current_page->ID;
		}

	}


	/**
	 * list_pages
	 *
	 * List the pages very similar to wp_list_pages
	 *
	 * @return string|void
	 */
	public function list_pages() {

		$pages = $this->get_child_pages( $this->top_parent_id );

		foreach( $pages as $page ){

			$this->output .= walk_page_tree( array( $page ), 1, $this->current_page_id, $this->args );

				$this->output .= $this->list_grandchild_pages( $page->ID );

			$this->output .= "</li>\n";

		}

		$this->output = apply_filters( 'wp_list_pages', $this->output, $this->args );

		if( $this->args[ 'echo' ] ){
			echo $this->output;
		} else {
			return $this->output;
		}
	}


	/**
	 * list_grandchild_pages
	 *
	 * List as many levels as exist within the grandchild-sidebar-menu ul
	 *
	 * @param int $parent_page_id
	 *
	 * @return string
	 */
	private function list_grandchild_pages( $parent_page_id ){
		$content = '';

		if( !$this->current_page_ancestor( $parent_page_id ) ){
			return '';
		}

		if( !$pages = $this->get_child_pages( $parent_page_id ) ){
			return '';
		}

		foreach( $pages as $page ){
			## TODO // be sure to test for excluded pages ( perhaps unit test )
			$content .= walk_page_tree( array( $page ), 1, $this->current_page_id, $this->args );

				$content .= $this->list_grandchild_pages( $page->ID );

			$content .= "</li>\n";

		}

		if( '' == $content ){
			return $content;
		}

		$this->level++;

		return sprintf( '<ul class="grandchild-sidebar-menu level-%s children">', $this->level ) . $content . "</ul>\n";
	}


	/**
	 * page_children
	 *
	 * Retrieve the child pages of specific page_id
	 *
	 * @param $parent_page_id
	 *
	 * @return array
	 */
	public function get_child_pages( $parent_page_id ) {
		$args = $this->args;
		$args[ 'parent' ] = $parent_page_id;

		return get_pages( $args );

	}


	/**
	 * current_page_ancestor
	 *
	 * Is the current page and ancestor of the specified page?
	 *
	 * @param $page_id
	 *
	 * @return bool
	 */
	private function current_page_ancestor( $page_id ) {
		$return = false;

		if( !empty( $this->current_page_id ) ){
			if( $page_id == $this->current_page_id ){
				$return = true;
			} elseif( $this->current_page->post_parent == $page_id ) {
				$return = true;
			} else {
				if( !empty( $this->current_page->ancestors ) ){
					if( in_array( $page_id, $this->current_page->ancestors ) ){
						$return = true;
					}
				}
			}
		}

		$return = apply_filters(
			'advanced_sidebar_menu_page_ancestor',
			$return,
			$this->current_page_id,
			$this
		);

		return $return;
	}


}