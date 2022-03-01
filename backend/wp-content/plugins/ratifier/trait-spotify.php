<?php

namespace Ratifier;

defined( 'ABSPATH' ) || exit;

trait Spotify {

    public $spotify_api;

    private function spotify_init() {
        add_action( 'admin_init' , array( $this , 'spotify_admin_init' ) );
    }

    private function get_spotifyapi_session() {
        $options = get_option( 'ratifier_options' );
        $client_id = $options['spotify_client_id'];
        $client_secret = $options['spotify_client_secret'];
        if ( $this->spotify_api ) {
            return $this->spotify_api;
        }
        require 'vendor/autoload.php';
        $session = new \SpotifyWebAPI\Session(
            $client_id,
            $client_secret
        );
        $session->requestCredentialsToken();
        $accessToken = $session->getAccessToken();
        $api = new \SpotifyWebAPI\SpotifyWebAPI();
        $api->setAccessToken($accessToken);
        $this->spotify_api = $api;
        return $api;
    }

    private function get_album_data_from_spotify( $album_id ) {
        try {
            $api = $this->get_spotifyapi_session();
            $album = $api->getAlbum( $album_id );
            if ( $album ) {
                $artists = '';
                $prefix = '';
                foreach ( $album->artists as $artist ) {
                    $artists .= $prefix . $artist->name;
                }
                $album_to_cache = array(
                    'title' => $album->name,
                    'artist' => $artists,
                    'year' => substr( $album->release_date , 0 , 4 ),
                    'album_id' => $album->id,
                );
                $this->albums_data_cache[] = $album_to_cache;
                return $album_to_cache;
            }
            return false;
        }
        catch ( Exception $e ) {
            return new WP_Error( 503 , 'Error connecting to album database' );
        }
    }

    public function search_for_spotify_album( $request ) {
        try {
            $api = $this->get_spotifyapi_session();

            $query = $request->get_param('s');
            $limit = $request->get_param('limit');
            if ( ! $limit ) {
                $limit = 10;
            }
            $offset = $limit * $request->get_param('offset');

            $results = $api->search( $query, 'album' , array(
                'limit' => $limit,
                'offset' => $offset,
            ));

            $albums = [];
            foreach ( $results->albums->items as $album ) {
                $artists = '';
                $prefix = '';
                foreach ( $album->artists as $artist ) {
                    $artists .= $prefix . $artist->name;
                }
                $albums[] = array(
                    'album_id' => $album->id,
                    'title' => $album->name,
                    'artist' => $artists,
                    'year' => substr( $album->release_date , 0 , 4 ), // only year is needed
                );
            }
            return $albums;
        }
        catch ( Exception $e ) {
            return new WP_Error( 503 , 'Error connecting to album database' );
        }
    }

    public function spotify_admin_init() {
        add_settings_section(
            'ratifier_spotify',
            __( 'Spotify', $this::TEXT_DOMAIN ),
            __return_false(),
            'ratifier'
        );

        add_settings_field(
            'spotify_client_id',
            __( 'Spotify Client ID', $this::NAME_SPACE ),
            array( $this , 'spotify_admin_section' ),
            'ratifier',
            'ratifier_spotify',
            array(
                'label_for'         => 'spotify_client_id',
            )
        );

        add_settings_field(
            'spotify_client_secret',
            __( 'Spotify Client Secret', $this::NAME_SPACE ),
            array( $this , 'spotify_admin_section' ),
            'ratifier',
            'ratifier_spotify',
            array(
                'label_for' => 'spotify_client_secret',
            )
        );
    }

    public function spotify_admin_section( $args ) {
        $options = get_option( 'ratifier_options' );
        ?>
        <input type="text" class="regular-text" id="<?php echo esc_attr( $args['label_for'] ); ?>"
               name="ratifier_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
               value="<?php echo isset( $options[esc_attr( $args['label_for'] )] ) ? $options[esc_attr( $args['label_for'] )] : ''; ?>">
        <?php
    }
}