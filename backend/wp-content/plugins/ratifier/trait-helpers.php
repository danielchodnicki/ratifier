<?php

namespace Ratifier;

defined( 'ABSPATH' ) || exit;

trait Helpers {

    public function fill_rating_data_with_album_data( $ratings ) {
        $album_ids = [];

        foreach ( $ratings as $rating ) {
            $album_ids[] = $rating['album_id'];
        }

        $album_posts = new \WP_Query(
            array(
                'post_type' => 'album',
                'posts_per_page' => -1,
                'meta_query' => array(
                    'meta_key' => 'album_id',
                    'meta_value' => $album_ids,
                    'compare' => 'IN'
                )
            )
        );
        foreach ( $album_posts->posts as $album_post ) {
            $album_metas = get_post_meta( $album_post->ID );
            foreach ( $ratings as &$album ) {
                if ( $album['album_id'] == $album_metas['album_id'][0] ) {
                    foreach ( $album_metas as $album_meta_key => $album_meta_value ) {
                        $album[ $album_meta_key ] = $album_meta_value[0];
                        foreach( $this->get_rating_axes() as $axis ) {
                            if ( $album_meta_key == $axis['name'] ) {
                                $album[ $album_meta_key ] = substr( $album_meta_value[0] , 0 , 4 );
                            }
                        }
                    }
                }
            }
            unset( $album );
        }
        return $ratings;
    }

    public function fill_album_data_with_rating_data( $albums ) {
        $album_ids = [];

        foreach ( $albums as $album ) {
            $album_ids[] = $album['album_id'];
        }

        $album_posts = new \WP_Query(
            array(
                'post_type' => 'album',
                'meta_query' => array(
                    'meta_key' => 'album_id',
                    'meta_value' => $album_ids,
                    'compare' => 'IN'
                )
            )
        );
        foreach ( $album_posts->posts as $album_post ) {
            $album_metas = get_post_meta( $album_post->ID );
            foreach ( $albums as &$album ) {
                if ( $album['album_id'] == $album_metas['album_id'][0] ) {
                    foreach ( $this->get_rating_axes() as $axis ) {
                        $album[ $axis['name'] ] = substr( $album_metas[ $axis['name'] ][0] , 0 ,4 );
                    }
                }
            }
        }
        return $albums;
    }

    public function fill_album_data_with_user_ratings( $albums ) {
        $user_id = get_current_user_id();
        $album_ids = [];

        foreach ( $albums as &$album ) {
            $album_ids[] = $album['album_id'];
            $album['user_ratings'] = false;
        }
        unset( $album );

        $ratings = new \WP_Query(
            array(
                'author' => $user_id,
                'post_type' => 'rating',
                'status' => 'any',
                'meta_query' => array(
                    'meta_key' => 'album_id',
                    'meta_value' => $album_ids,
                    'compare' => 'IN'
                )
            )
        );

        foreach ( $ratings->posts as $rating ) {
            $rating_metas = get_post_meta( $rating->ID );
            foreach ( $albums as &$album ) {
                if ( $album['album_id'] == $rating_metas['album_id'][0] ) {
                    $album['user_ratings'] = [];
                    foreach ( $this->get_rating_axes() as $axis ) {
                        $album['user_ratings'][ $axis['name'] ] = substr( $rating_metas[ $axis['name'] ][0] , 0 , 4 );
                    }
                }
            }
        }
        return $albums;
    }
}