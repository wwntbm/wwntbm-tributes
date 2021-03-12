<?php
/**
 * Plugin Name: WWNTBM Tributes
 * Plugin URI: https://wwntbm.com/
 * Description: Enables tribute posts and submission
 * Author: Andrew Minion
 * Version: 1.0.0
 * Author URI: https://andrewrminion.com/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WWNTBM_Tributes {

    /** @var string */
    public $post_type_key = 'wwntbm_tribute';

    /** @var string */
    public $taxonomy_key = 'wwntbm_tribute_category';

	/**
	 * Class instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Return only one instance of this class.
	 *
	 * @return WWNTBM_Tributes class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new WWNTBM_Tributes();
		}

		return self::$instance;
	}

    /**
     * Load actions and hooks.
     *
     * @since 1.0.0
     */
    public function __construct() {
        // CPT.
        add_action( 'init', array( $this, 'register_cpt' ) );

        // Form submission.
        add_action( 'gform_after_submission_1', array( $this, 'gform_after_submission' ), 10, 2 );

        // ACF.
        add_filter( 'acf/settings/load_json', array( $this, 'load_acf_json' ) );

        // Gallery.
        add_filter( 'the_content', array( $this, 'tribute_gallery' ) );
        add_filter( 'the_excerpt', array( $this, 'tribute_gallery' ) );

        add_shortcode( 'wwntbm_tributes', array( $this, 'shortcode' ) );
    }

    /**
     * Register CPT and taxonomy.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_cpt() {
        register_post_type(
            $this->post_type_key,
            array(
                'label'                 => 'Tribute',
                'description'           => 'User-Submitted Tribute',
                'labels'                => array(
                    'name'                  => 'Tributes',
                    'singular_name'         => 'Tribute',
                    'menu_name'             => 'Tributes',
                    'name_admin_bar'        => 'Tribute',
                    'archives'              => 'Tribute Archives',
                    'attributes'            => 'Tribute Attributes',
                    'parent_item_colon'     => 'Parent Tribute:',
                    'all_items'             => 'All Tributes',
                    'add_new_item'          => 'Add New Tribute',
                    'add_new'               => 'Add New',
                    'new_item'              => 'New Tribute',
                    'edit_item'             => 'Edit Tribute',
                    'update_item'           => 'Update Tribute',
                    'view_item'             => 'View Tribute',
                    'view_items'            => 'View Tributes',
                    'search_items'          => 'Search Tribute',
                    'not_found'             => 'Not found',
                    'not_found_in_trash'    => 'Not found in Trash',
                    'featured_image'        => 'Featured Image',
                    'set_featured_image'    => 'Set featured image',
                    'remove_featured_image' => 'Remove featured image',
                    'use_featured_image'    => 'Use as featured image',
                    'insert_into_item'      => 'Insert into tribute',
                    'uploaded_to_this_item' => 'Uploaded to this tribute',
                    'items_list'            => 'Tributes list',
                    'items_list_navigation' => 'Tributes list navigation',
                    'filter_items_list'     => 'Filter tributes list',
                ),
                'supports'              => array( 'title', 'editor', 'thumbnail', 'revisions' ),
                'taxonomies'            => array( 'tribute_category' ),
                'hierarchical'          => false,
                'public'                => true,
                'show_ui'               => true,
                'show_in_menu'          => true,
                'menu_position'         => 25,
                'menu_icon'             => 'dashicons-format-status',
                'show_in_admin_bar'     => true,
                'show_in_nav_menus'     => true,
                'can_export'            => true,
                'has_archive'           => true,
                'exclude_from_search'   => false,
                'publicly_queryable'    => true,
                'rewrite'               => array(
                    'slug'                  => 'tribute',
                    'with_front'            => true,
                    'pages'                 => true,
                    'feeds'                 => true,
                ),
                'capability_type'       => 'page',
                'show_in_rest'          => true,
                'rest_base'             => 'tribute',
            )
        );

        register_taxonomy(
            $this->taxonomy_key,
            array( $this->post_type_key ),
            array(
                'labels'                     => array(
                    'name'                       => 'Tribute Categories',
                    'singular_name'              => 'Tribute Category',
                    'menu_name'                  => 'Categories',
                    'all_items'                  => 'All Categories',
                    'parent_item'                => 'Parent Category',
                    'parent_item_colon'          => 'Parent Category:',
                    'new_item_name'              => 'New Category Name',
                    'add_new_item'               => 'Add New Category',
                    'edit_item'                  => 'Edit Category',
                    'update_item'                => 'Update Category',
                    'view_item'                  => 'View Category',
                    'separate_items_with_commas' => 'Separate categories with commas',
                    'add_or_remove_items'        => 'Add or remove categories',
                    'choose_from_most_used'      => 'Choose from the most used',
                    'popular_items'              => 'Popular Categories',
                    'search_items'               => 'Search Categories',
                    'not_found'                  => 'Not Found',
                    'no_terms'                   => 'No categories',
                    'items_list'                 => 'Categories list',
                    'items_list_navigation'      => 'Categories list navigation',
                ),
                'hierarchical'               => true,
                'public'                     => true,
                'show_ui'                    => true,
                'show_admin_column'          => true,
                'show_in_nav_menus'          => true,
                'show_tagcloud'              => true,
                'rewrite'                    => array(
                    'slug'                       => 'tribute-category',
                    'with_front'                 => true,
                    'hierarchical'               => false,
                ),
                'show_in_rest'               => true,
                'rest_base'                  => 'tribute-category',
            )
        );
    }

    /**
     * Load ACF JSON from plugin folder.
     *
     * @param array $paths ACF JSON directories.
     *
     * @return array       ACF JSON directories.
     */
    public function load_acf_json( $paths ) {
        $paths[] = plugin_dir_path( __FILE__ ) . 'acf-json';
        return $paths;
    }

    /**
     * Handle form submission.
     *
     * @since 1.0.0
     *
     * @param array $entry Entry object.
     * @param array $form  Form object.
     *
     * @return void
     */
    public function gform_after_submission( $entry, $form ) {
        $first_name  = rgar( $entry, '1.3' );
        $last_name   = rgar( $entry, '1.6' );
        $content     = rgar( $entry, '3' );
        $photos      = json_decode( rgar( $entry, '4' ) );
        $category_id = rgar( $entry, '5' );

        $post_content = array(
            'post_type'     => $this->post_type_key,
            'post_status'   => 'pending',
            'post_title'    => trim( $first_name . ' ' . $last_name ),
            'post_content'  => $content,
            'tax_input'     => array(
                $this->taxonomy_key => array( $category_id ),
            ),
        );

        $post_id = wp_insert_post( $post_content );
        if ( ! is_wp_error( $post_id ) ) {
            update_field( 'first_name', $first_name, $post_id );
            update_field( 'last_name', $last_name, $post_id );

            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $photo_ids = array();
            if ( $photos ) {
                foreach ( $photos as $photo ) {
                    $photo_id = media_sideload_image( $photo, $post_id, null, 'id' );
                    if ( ! is_wp_error( $photo_id ) ) {
                        $photo_ids[] = $photo_id;
                    }
                }
            }

            update_field( 'photos', $photo_ids, $post_id );
        }
    }

    /**
     * Add gallery to tribute content.
     *
     * @since 1.0.0
     *
     * @param string $content
     *
     * @return string
     */
    public function tribute_gallery( $content ) {
        if ( $this->post_type_key !== get_post_type() ) {
            return $content;
        }

        $photo_ids = get_field( 'photos' );

        if ( $photo_ids ) {
            $shortcode = sprintf( '[gallery ids="%s" size="medium" link="file"]', esc_attr( implode( ',', $photo_ids ) ) );
            $content  .= do_shortcode( $shortcode );
        }

        return $content;
    }

    /**
     * Display tributes in a shortcode.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function shortcode() {
        $args = array(
            'post_type'      => $this->post_type_key,
            'posts_per_page' => -1,
            'order'          => 'DESC',
        );

        $tributes = new WP_Query( $args );

        ob_start();
        while ( $tributes->have_posts() ) {
            $tributes->the_post();

            echo '<h3>' . get_the_title() . '</h3>';
            echo apply_filters( 'the_content', get_the_content() );
        }
        wp_reset_postdata();

        return ob_get_clean();
    }
}
WWNTBM_Tributes::get_instance();
