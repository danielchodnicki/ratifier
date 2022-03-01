<?php
/*
Plugin Name: Ratifier
Plugin URI: https://web-zeppelin.pl
Text Domain: ratifier
Description: Wtyczka do oceniania albumów ze Spotify
Author: Daniel Chodnicki
Version: 0.0.1
Plugin URI: https://web-zeppelin.pl/ratifier/
*/

defined( 'ABSPATH' ) || exit;

require( __DIR__ . '/trait-helpers.php' );
require( __DIR__ . '/trait-spotify.php' );
require( __DIR__ . '/trait-user-management.php' );
require( __DIR__ . '/trait-album.php' );
require( __DIR__ . '/trait-rating.php' );

if ( ! class_exists( 'Ratifier' ) ) {

    class Ratifier {

        use Ratifier\Helpers;
        use Ratifier\Spotify;
        use Ratifier\UserManagement;
        use Ratifier\Album;
        use Ratifier\Rating;

        const TEXT_DOMAIN = 'ratifier';
        const NAME_SPACE = 'ratifier/v';
        const VERSION = '1';
        const USER_ROLE_NAME = 'rater';

        public function __construct() {
            add_action( 'init' ,       array( $this , 'init' ) );
            add_action( 'admin_init' , array( $this , 'admin_init' ) );
            add_action( 'admin_menu' , array( $this , 'admin_menu' ) );
        }

        public function init() {

            $this->call_inits();

            add_action( 'template_redirect' , array( $this , 'redirect_to_frontend' ) );

        }

        public function redirect_to_frontend() {
            wp_redirect( 'https://web-zeppelin.pl/ratifier/' );
            die;
        }

        private function call_inits( $suffix = '_init' ) {
            foreach ( get_class_methods( $this ) as $method ) {
                if ( $suffix == '_init' ) {
                    if ( substr( $method , -5 ) === '_init'
                        && substr( $method , -1 * strlen( 'admin_init' ) ) !== 'admin_init'
                        && substr( $method , -1 * strlen( 'rest_api_init' ) ) !== 'rest_api_init' ) {
                        $this->$method();
                    }
                }
                else {
                    if ( substr( $method , -1 * strlen( $suffix ) ) === $suffix ) {
                        $this->$method();
                    }
                }
            }
        }

        public function get_rating_axes() {
            /*
             * This ultimately will be retrieved dynamically from plugin configuration.
             * More than one rating axis will be available
             */
            return array(
                array(
                    'label' => __( 'Production rating' , $this::TEXT_DOMAIN ),
                    'name' => 'rating_production',
                ),
            );
        }

        public function admin_init() {
            register_setting( 'ratifier', 'ratifier_options' );
        }

        public function admin_menu() {
            add_menu_page(
                'Ratifier',
                'Ratifier',
                'ratifier_admin',
                'ratifier',
                array( $this , 'admin_page' )
            );
        }

        public function admin_page() {
            if ( ! current_user_can( 'ratifier_admin' ) ) {
                return;
            }

            if ( isset( $_GET['settings-updated'] ) ) {
                add_settings_error( 'ratifier_messages', 'ratifier_message', __( 'Settings Saved', $this::TEXT_DOMAIN ), 'updated' );
            }
            ?>
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <?php
                settings_errors( 'ratifier_messages' );
            ?>
            <form action="options.php" method="post">
            <?php
                settings_fields( 'ratifier' );
                do_settings_sections( 'ratifier' );
                submit_button();
        }

        public function get_sort_types() {
            return array(
                    'user_rating_desc',
                    'user_rating_asc',
                    'artist_desc',
                    'artist_asc',
                    'title_desc',
                    'title_asc',
                    'year_desc',
                    'year_asc',
                    'rating_desc',
                    'rating_asc',
                );
        }

        private function get_sorting_query( $order_by ) {

            $return = [];

            if ( ! in_array( strtolower( $order_by ) , $this->get_sort_types() ) ) {
                $order_by = 'rating_desc';
            }

            $explode = explode( '_' , $order_by );
            $order = strtoupper( array_pop( $explode ) );

            $order_by = implode( '_' , $explode );

            $return['orderby'] = 'meta_value';
            $return['meta_key'] = $order_by;
            if ( in_array( $order_by , [ 'year' , 'rating' , 'user_rating' ] ) ) {
                $return['orderby'] = 'meta_value_num';
            }

            if ( $order_by == 'rating' || $order_by == 'user_rating' ) {
                $order_by = $this->get_rating_axes()[0]['name'];
            }

            $query = array(
                'meta_query' => array(
                    'relation' => 'AND',
                    'main_clause' => array(
                        'key' => $order_by,
                        'compare' => 'EXISTS',
                    ),
                    'album_id_clause' => array(
                        'key' => 'album_id',
                        'compare' => 'EXISTS',
                    ),
                ),
                'orderby' => array(
                    'main_clause' => $order,
                    'album_id_clause' => $order,
                ),
            );

            return $query;

        }

    }
    new Ratifier();
}