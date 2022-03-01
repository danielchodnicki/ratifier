<?php

namespace Ratifier;

defined( 'ABSPATH' ) || exit;

trait Album {

    public $album_metas;
    public $albums_data_cache = [];

    public function album_init() {
        add_action( 'rest_api_init' , array( $this , 'album_rest_api_init' ) );
        $this->prepare_album_post_type();
    }

    public function album_rest_api_init() {

        add_filter( 'rest_album_query', array( $this , 'rest_album_query' ), 10, 2 );

        register_rest_route( $this::NAME_SPACE . $this::VERSION, '/search', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array( $this, 'search_for_album' ),
                'permission_callback' => array( $this , 'get_albums_permissions_check' ),
            ),
        ));

        register_rest_route( $this::NAME_SPACE . $this::VERSION, '/ratedAlbums', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_rated_albums' ),
                'permission_callback' => array( $this , 'get_albums_permissions_check' ),
            ),
        ));

    }

    public function get_rated_albums( $request ) {

        $order_by = $request->get_param( 'order_by' );

        $query_args = $this->get_sorting_query( $order_by );

        $query_args = array_merge( $query_args , array(
            'post_type' => 'album',
            'posts_per_page' => $request->get_param( 'posts_per_page' ) ? $request->get_param( 'posts_per_page' ) : 10,
            'paged' => $request->get_param( 'page' ) ? $request->get_param( 'page' ) : 1,
        ));

        $albums = new \WP_Query( $query_args );

        $albums_output = [];

        foreach ( $albums->posts as $album ) {
            $album_metas = get_post_meta( $album->ID );
            $album_output = [];
            foreach ( $album_metas as $album_meta_key => $album_meta_value ) {
                $album_output[ $album_meta_key ] = $album_meta_value[0];

                /*
                 * Outputed ratings should have no more than 2 decimal points
                 */
                foreach( $this->get_rating_axes() as $axis ) {
                    if ( $album_meta_key == $axis['name'] ) {
                        $album_output[ $album_meta_key ] = substr( $album_meta_value[0] , 0 , 4 );
                    }
                }

            }

            $album_output['counter'] = isset( $album_metas['counter'][0] ) ? $album_metas['counter'][0] : 0;
            $albums_output[] = $album_output;
        }

        $albums_output = $this->fill_album_data_with_user_ratings( $albums_output );

        wp_send_json_success( $albums_output );
    }

    private function get_album_data( $album_id ) {
        $album_results = new \WP_Query( array(
            'meta_key' => 'album_id',
            'meta_value' => $album_id
        ));
        if ( count( $album_results->posts ) ) {
            $album_metas = get_post_meta( $album_results->posts[0]->ID );
            return $album_metas;
        }
        else {
            $album_data = $this->get_album_data_from_spotify( $album_id );
            if ( $album_data ) {
                return $album_data;
            }
        }
        return false;
    }

    private function prepare_album_post_type() {

        register_post_type( 'album' , array(
                'label' => __( 'Album' , $this::TEXT_DOMAIN ),
                'public' => true,
                'publicly_queryable' => true,
                'delete_with_user' => false,
                'show_in_rest' => true,
                'rest_base' => 'albums',
                'supports' => array( 'title' , 'thumbnail' , 'custom-fields' ),
            )
        );

        add_action( 'updated_post_meta' , array( $this , 'update_album_ratings' ) , 10 , 4 );

        $this->album_metas = array(
            array(
                'label' => __( 'Album ID' , $this::TEXT_DOMAIN ),
                'name' => 'album_id',
                'type' => 'number',
            ),
            array(
                'label' => __( 'Year' , $this::TEXT_DOMAIN ),
                'name' => 'year',
                'type' => 'number',
            ),
            array(
                'label' => __( 'Artist' , $this::TEXT_DOMAIN ),
                'name' => 'artist',
                'type' => 'string',
            ),
            array(
                'label' => __( 'Title' , $this::TEXT_DOMAIN ),
                'name' => 'title',
                'type' => 'string',
            ),
            array(
                'label' => __( 'Counter' , $this::TEXT_DOMAIN ),
                'name' => 'counter',
                'type' => 'number',
            ),
        );

        foreach ( $this->get_rating_axes() as $axis ) {
            $axis['type'] = 'number';
            $axis['readonly'] = true;
            $this->album_metas[] = $axis;
        }

        foreach( $this->album_metas as $album_meta ) {
            register_post_meta( 'album', $album_meta['name'], array(
                'type' => $album_meta['type'],
                'description' => $album_meta['label'],
                'single' => true,
                'show_in_rest' => true
            ));
        }
    }

    public function rest_album_query( $args , $request ) {
        if ( $request->get_param( 'album_ids' ) ) {
            $args['meta_key'] = 'album_id';
            $args['meta_value'] = $request->get_param( 'album_ids' );
            $args['meta_compare'] = 'IN';
        }
        return $args;
    }

    public function maybe_insert_new_album( $album_id ) {
        $albums = new \WP_Query(
            array(
                'meta_value' => $album_id,
                'meta_key' => 'album_id',
                'post_type' => 'album',
            )
        );
        if ( ! count( $albums->posts ) ) {
            foreach ( $this->albums_data_cache as $album ) {
                if ( $album_id == $album['album_id'] ) {
                    return wp_insert_post( array(
                        'post_title' => $album_id,
                        'post_type' => 'album',
                        'post_status' => 'publish',
                        'meta_input' => $album,
                    ));
                }
            }
        }
        return false;
    }

    public function update_album_ratings( $null , $rating_id ) {

        $album_id = get_post_meta( $rating_id , 'album_id' , true );

        $ratings = new \WP_Query(
            array(
                'meta_value' => $album_id,
                'meta_key' => 'album_id',
                'post_type' => 'rating',
                'posts_per_page' => -1,
                'post_status' => 'any',
            )
        );

        add_action( 'pre_get_posts' , array( $this , 'show_private_ratings' ) , 10 );

        $sums = array();

        foreach ( $this->get_rating_axes() as $axis ) {
            $sums[ $axis['name'] ] = array(
                'count' => 0,
                'sum' => 0,
            );
        }

        if ( count( $ratings->posts ) ) {
            $albums = new \WP_Query(
                array(
                    'meta_value' => $album_id,
                    'meta_key' => 'album_id',
                    'post_type' => 'album',
                    'posts_per_page' => -1,
                )
            );

            if ( count( $albums->posts ) ) {
                $album_post_id = $albums->posts[0]->ID;
            }

            foreach ( $ratings->posts as $rating ) {
                $rating_meta = get_post_meta( $rating->ID );

                foreach ( $this->get_rating_axes() as $axis ) {
                    if ( isset( $rating_meta[ $axis['name'] ] ) ) {
                        $sums[ $axis['name'] ]['count']++;
                        $sums[ $axis['name'] ]['sum'] = intval( $rating_meta[ $axis['name'] ][0] ) + $sums[ $axis['name'] ]['sum'];
                    }
                }
            }

            foreach ( $sums as $axis => $sum ) {
                $avg = $sum['sum'] / $sum['count'];
                update_post_meta( $album_post_id, $axis, $avg );
                update_post_meta( $album_post_id, 'counter', $sum['count'] );
            }
        }
    }

    public function search_for_album( $request ) {
        $albums = $this->search_for_spotify_album( $request );
        $albums = $this->fill_album_data_with_rating_data( $albums );
        $albums = $this->fill_album_data_with_user_ratings( $albums );
        wp_send_json_success( $albums );
    }
}