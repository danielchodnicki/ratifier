<?php

namespace Ratifier;

defined( 'ABSPATH' ) || exit;

trait Rating {

    private function rating_init() {

        add_action( 'rest_api_init' , array( $this , 'rating_rest_api_init' ) );

        $this->prepare_rating_post_type();

    }

    public function rating_rest_api_init() {

        register_rest_route( $this::NAME_SPACE . $this::VERSION, '/rating', array(
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array( $this, 'add_rating' ),
                'permission_callback' => array( $this , 'add_rating_permissions_check' ),
            ),
        ));

        register_rest_route( $this::NAME_SPACE . $this::VERSION, '/userRatings', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_user_ratings' ),
                'permission_callback' => array( $this , 'add_rating_permissions_check' ),
            ),
        ));

    }

    public function get_rating_metas() {
        return $this->rating_metas;
    }

    private function prepare_rating_post_type() {
        register_post_type( 'rating' , array(
                'label' => __( 'Rating' , $this::TEXT_DOMAIN ),
                'public' => true,
                'publicly_queryable' => true,
                'delete_with_user' => false,
                'show_in_rest' => true,
                'rest_base' => 'ratings',
                'supports' => array( 'title' , 'author' , 'custom-fields' ),
            )
        );

        add_action( 'pre_get_posts' , array( $this , 'show_private_ratings' ) , 10 );

        $this->rating_metas = array(
            array(
                'label' => __( 'Album ID' , $this::TEXT_DOMAIN ),
                'name' => 'album_id',
                'type' => 'number',
            ),
        );

        foreach ( $this->get_rating_axes() as $axis ) {
            $axis['type'] = 'number';
            $axis['readonly'] = true;
            $this->rating_metas[] = $axis;
        }

        foreach ( $this->rating_metas as $rating_meta ) {
            register_post_meta( 'rating', $rating_meta['name'], array(
                'type' => $rating_meta['type'],
                'description' => $rating_meta['label'],
                'single' => true,
                'show_in_rest' => true
            ));
        }
    }

    public function get_user_ratings( $request ) {

        $order_by = $request->get_param( 'order_by' ) ? $request->get_param( 'order_by' ) : 'user_rating_desc';

        $albums_output = [];

        if ( $order_by == 'user_rating_desc' || $order_by == 'user_rating_asc' ) {

            $query_args = $this->get_sorting_query( $order_by );

            $ratings = new \WP_Query( array_merge( $query_args , array(
                'author' => get_current_user_id(),
                'post_type' => 'rating',
                'posts_per_page' => $request->get_param( 'posts_per_page' ) ? $request->get_param( 'posts_per_page' ) : 10,
                'paged' => $request->get_param( 'page' ) ? $request->get_param( 'page' ) : 1,
                'post_status' => 'any',
            ) ) );
            foreach ( $ratings->posts as $album ) {
                $album_metas = get_post_meta( $album->ID );
                $album_output = [];
                $album_output['album_id'] = $album_metas['album_id'][0];
                $albums_output[] = $album_output;
            }
            $albums_output = $this->fill_rating_data_with_album_data( $albums_output );
            $albums_output = $this->fill_album_data_with_user_ratings( $albums_output );
            wp_send_json_success( $albums_output );
            return;

        }

        $album_ids = [];

        $ratings = new \WP_Query( array(
            'author' => get_current_user_id(),
            'post_type' => 'rating',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ) );

        foreach ( $ratings->posts as $rating ) {
            $album_ids[] = get_post_meta( $rating->ID , 'album_id' , true );
        }

        $query_args = $this->get_sorting_query( $order_by );

        $query_args = array_merge( $query_args , array(
            'post_type' => 'album',
            'posts_per_page' => $request->get_param( 'posts_per_page' ) ? $request->get_param( 'posts_per_page' ) : 10,
            'paged' => $request->get_param( 'page' ) ? $request->get_param( 'page' ) : 1,
        ));

        $query_args['meta_query']['album_id_clause'] = array(
            'key' => 'album_id',
            'compare' => 'IN',
            'value' => $album_ids,
        );

        //var_dump($query_args);

        $albums = new \WP_Query( $query_args );

        foreach ( $albums->posts as $album ) {
            $album_metas = get_post_meta( $album->ID );
            $album_output = [];
            foreach ( $album_metas as $album_meta_key => $album_meta_value ) {
                $album_output[ $album_meta_key ] = $album_meta_value[0];
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

    public function add_rating( $request ) {

        $rating_data = $request->get_params('POST');

        if ( $this->check_rating_for_errors( $rating_data ) ) {
            return new WP_Error( 500 , $this->check_rating_for_errors( $rating_data ) );
        }

        $ratings = new \WP_Query(
            array(
                'author' => get_current_user_id(),
                'meta_key'   => 'album_id',
                'meta_value' => $rating_data['album_id'],
                'post_type' => 'rating',
                'post_status' => 'any',
            )
        );

        $mode = 'added';

        if ( count( $ratings->posts ) ) {
            $rating_to_update_id = $ratings->posts[0]->ID;
            $mode = 'updated';
        }
        else {
            $this->maybe_insert_new_album( $rating_data['album_id'] );
            $rating_to_update_id = wp_insert_post( array(
                'post_title' => $this->get_new_rating_post_title( $rating_data ),
                'post_type' => 'rating',
            ));
            update_post_meta( $rating_to_update_id, 'album_id', $rating_data['album_id'] );
        }

        foreach ( $this->get_rating_axes() as $axis ) {
            update_post_meta( $rating_to_update_id, $axis['name'], $rating_data[$axis['name']] );
        }

        $this->update_album_ratings( null , $rating_to_update_id );
        $albums = [];
        $albums[] = $rating_data;
        $albums = $this->fill_rating_data_with_album_data( $albums );
        $albums = $this->fill_album_data_with_user_ratings( $albums );
        return new \WP_REST_Response(array(
            'status'  => 200,
            'message' => 'Rating ' . $mode,
            'data' => $albums,
        ), 200);
    }

    public function check_rating_for_errors( $data ) {
        if ( ! isset( $data['album_id'] ) || ! $this->get_album_data( $data['album_id'] ) ) {
            return __( 'Bad album ID' , $this::TEXT_DOMAIN );
        }
        foreach ( $this->get_rating_axes() as $axis ) {
            if ( ! isset( $data[ $axis['name'] ] ) ) {
                return __( 'Lack of rating:' , $this::TEXT_DOMAIN ) . $axis['name'];
            }
            if ( intval( $data[ $axis['name'] ] ) != $data[ $axis['name'] ] ) {
                return __( 'Rating is not integer:' , $this::TEXT_DOMAIN ) . $axis['name'];
            }
        }
        return false;
    }

    public function get_new_rating_post_title( $rating_data ) {
        return $rating_data['album_id'] . '-' . get_current_user_id();
    }

    public function show_private_ratings( $query ) {

        if ( is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( $query->get( 'post_type' ) === 'rating' ) {
            $query->set( 'author' , get_current_user_id() );
            $query->set( 'post_status' , 'any' );
        }

    }

}