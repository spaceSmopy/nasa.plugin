<?php
/*
Plugin Name: Nasa Plugin
Plugin URI: http://github.com
Description: Nasa Images
Author: Smopy
Author URI: http://github.com/spaceSmopy
Version: 1.0.0
*/

class nasaplugin {
    private $nasaAPIkey = 'PIsWbOsEWttNwBt0gFwbBDw1qehuCHmQczr6cLqJ';

    public function __construct() {
//        Default actions
        add_action( 'wp_enqueue_scripts', array( $this, 'assets' ));
        add_action( 'init', array( $this, 'post_nasa_gallery' ));

//        Cron actions
        add_action( 'wp_ajax_importNasaPost', array( $this, 'importNasaPost') );
        add_action( 'wp_ajax_nopriv_importNasaPost', array( $this, 'importNasaPost') );
        add_action( 'cornNasaPost_hook', array( $this, 'importNasaPost') );
        wp_schedule_event( time(), 'daily', 'cornNasaPost_hook' );

//        Shortcode actions
        add_shortcode('nasa_images', array($this, 'shortcode_func'));
    }


    public function assets() {
//        Slick Slider SDN
        wp_register_style( 'plugin_slickCssSDN', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.css', false, '1.0.0' );
        wp_register_style( 'plugin_slickAdditionalCssSDN', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.min.css', false, '1.0.0' );
        wp_register_script( 'plugin_slickScriptSDN', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', false, '1.0.0' );

//      Latest jQuery
        wp_register_script( 'plugin_jQuerySDN', 'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js', NULL, '3.5.1');

//        Plugin Script and Style
        wp_register_script( 'plugin_customScript', plugin_dir_url( __FILE__ ) . 'assets/custom.js', false, '1.0.0' );
        wp_register_style( 'plugin_customStyle', plugin_dir_url( __FILE__ ) . 'assets/custom.css', false, '1.0.0' );


//        Initialize jQuery
        wp_enqueue_script( 'plugin_jQuerySDN' );

//        Initialize Slick Slider SDN
        wp_enqueue_script( 'plugin_slickScriptSDN' );
        wp_enqueue_style( 'plugin_slickAdditionalCssSDN' );
        wp_enqueue_style( 'plugin_slickCssSDN' );

//        Initialize Plugin Script and Style
        wp_enqueue_script( 'plugin_customScript' );
        wp_enqueue_style( 'plugin_customStyle' );

    }


    private function get_api_call($date = null) {
        /**
         * Get Response from NASA API
        */
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.nasa.gov/planetary/apod?api_key=$this->nasaAPIkey&date=$date",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

    public function get_postInfoFromAPI($date = null) {
        /**
         * Build date object for future Posts
         */
        $response = $this->get_api_call($date);
        $info = new stdClass();
        $info->date = $response->date;
        $info->image = $response->url;

        return $info;
    }

    public function post_nasa_gallery() {
        /**
         * Initialize Custom Post Type
         */
        $labels = array(
            'name'                => _x( 'NASA Gallery', 'Post Type General Name', 'twentynineteen' ),
            'singular_name'       => _x( 'NASA Gallery', 'Post Type Singular Name', 'twentynineteen' ),
            'menu_name'           => __( 'NASA Gallery', 'twentynineteen' ),
            'parent_item_colon'   => __( 'Parent NASA Gallery', 'twentynineteen' ),
            'all_items'           => __( 'All NASA Gallery', 'twentynineteen' ),
            'view_item'           => __( 'View NASA Gallery', 'twentynineteen' ),
            'add_new_item'        => __( 'Add New NASA Gallery', 'twentynineteen' ),
            'add_new'             => __( 'Add New', 'twentynineteen' ),
            'edit_item'           => __( 'Edit NASA Gallery', 'twentynineteen' ),
            'update_item'         => __( 'Update NASA Gallery', 'twentynineteen' ),
            'search_items'        => __( 'Search NASA Gallery', 'twentynineteen' ),
            'not_found'           => __( 'Not Found', 'twentynineteen' ),
            'not_found_in_trash'  => __( 'Not found in Trash', 'twentynineteen' ),
        );

        $args = array(
            'label'               => __( 'NASA Gallery', 'twentynineteen' ),
            'description'         => __( 'NASA Gallery', 'twentynineteen' ),
            'labels'              => $labels,
            'supports'            => array( 'title', 'editor',  'thumbnail' ),
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 5,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'show_in_rest' => true,

        );

        register_post_type( 'post-nasa-gallery', $args );

    }



    public function Generate_Featured_Image( $image_url, $post_id  ){
        /**
         * Download Image to site and use them as Featured Image
         */
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($image_url);
        $filename = basename($image_url);
        if(wp_mkdir_p($upload_dir['path']))
            $file = $upload_dir['path'] . '/' . $filename;
        else
            $file = $upload_dir['basedir'] . '/' . $filename;
        file_put_contents($file, $image_data);

        $wp_filetype = wp_check_filetype($filename, null );
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
        $res1= wp_update_attachment_metadata( $attach_id, $attach_data );
        $res2= set_post_thumbnail( $post_id, $attach_id );
    }

    public function importNasaPost() {
        /**
         * Action for Ajax
         */
        $NasaPost_data = $this->get_postInfoFromAPI();
        $this->insert_or_update( $NasaPost_data );
        wp_die();

    }
    public function insert_or_update($NasaPost_data) {
        /**
         * Update or insert new data
        */
        if ( ! $NasaPost_data)
            return false;

        $NasaPost_title = $NasaPost_data->date;
        $NasaPost_content = '<img src="'. $NasaPost_data->image .'">';

//        Check uniqueness by post title (image Date)
        $args = array(
            'meta_query' => array(
                array(
                    'key'   => 'NasaPost_unic',
                    'value' => $NasaPost_title
                )
            ),
            'post_type'      => 'post-nasa-gallery',
            'post_status'    => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'),
            'posts_per_page' => 1
        );
        $NasaPost = get_posts( $args );

        if ( !$NasaPost ){
            $NasaPost_post = array(
                'post_title'    => $NasaPost_title,
                'post_content'  => $NasaPost_content,
                'post_type'     => 'post-nasa-gallery',
                'post_status'   => 'publish'
            );

            $NasaPost_id = wp_insert_post( $NasaPost_post );

            if ( $NasaPost_id ) {
                update_post_meta( $NasaPost_id, 'NasaPost_unic', $NasaPost_title );

                $this->generate_Featured_Image( $NasaPost_data->image, $NasaPost_id );
            }
        }

    }

    public function shortcode_func(){
        /**
         * Output of shortcode [nasa_images]
        */
        $args = array(
            'numberposts' => 5,
            'post_type'      => 'post-nasa-gallery',
            'post_status'    => 'publish',
            'orderby'     => 'date',
            'order'       => 'DESC',
        );
        $NasaPosts = get_posts( $args );

        $output = '<div class="center-slider">';
        foreach ($NasaPosts as $NasaPost){
            $featuredImageUrl = get_the_post_thumbnail_url($NasaPost->ID);
            $output .= '<div><img src="'. $featuredImageUrl .'" alt=""></div>';
        }
        $output .= '</div>';

        return $output;
    }

}

$class = new nasaplugin();


register_activation_hook( __FILE__ , 'nasaplugin_install');
function nasaplugin_install() {
    global $wpdb;

    $class = new nasaplugin();

    for ($i = 0; $i <= 4; $i++) {

        $date = date("Y-m-d", strtotime("-$i days"));
        $post_info = $class->get_postInfoFromAPI($date);
        $title = $post_info->date;
        $post_content = '<img src="'. $post_info->image .'">';


        $the_page_title = $title;
        $the_page_name = $title;

        // the menu entry...
        delete_option("nasaplugin_page_title".$i);
        add_option("nasaplugin_page_title".$i, $the_page_title, '', 'yes');
        // the slug...
        delete_option("nasaplugin_page_name".$i);
        add_option("nasaplugin_page_name".$i, $the_page_name, '', 'yes');
        // the id...
        delete_option("nasaplugin_page_id".$i);
        add_option("nasaplugin_page_id".$i, $i, '', 'yes');

        $the_page = get_page_by_title( $the_page_title );

        if ( ! $the_page ) {

            // Create post object
            $new_post = array(
                'post_title'    => $title,
                'post_content'  => $post_content,
                'post_status'   => 'publish',
                'post_type' => 'post-nasa-gallery'
            );

            // Insert the post into the database
            $the_page_id = wp_insert_post($new_post);
            $class->generate_Featured_Image( $post_info->image, $the_page_id );
        }
        else {
            // the plugin may have been previously active and the page may just be trashed...

            $the_page_id = $the_page->ID;

            //make sure the page is not trashed...
            $the_page->post_status = 'publish';
            $the_page_id = wp_update_post( $the_page );

        }

        delete_option( 'nasaplugin_page_id'.$i );
        add_option( 'nasaplugin_page_id'.$i, $the_page_id );

    }


}

/* Runs on plugin deactivation */
register_deactivation_hook( __FILE__, 'nasaplugin_remove') ;
function nasaplugin_remove() {

    global $wpdb;

    for ($i = 0; $i <= 4; $i++) {
        //  the id of our page...
        $the_page_id = get_option( 'nasaplugin_page_id'.$i );
        if( $the_page_id ) {
            wp_delete_post( $the_page_id ); // this will trash, not delete
        }
        delete_option("nasaplugin_page_title".$i);
        delete_option("nasaplugin_page_name".$i);
        delete_option("nasaplugin_page_id".$i);
    }


}


