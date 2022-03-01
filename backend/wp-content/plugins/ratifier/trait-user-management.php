<?php

namespace Ratifier;

defined( 'ABSPATH' ) || exit;

trait UserManagement {

    public function user_management_init() {
        add_role(
            $this::USER_ROLE_NAME,
            ucfirst( $this::USER_ROLE_NAME ),
            array(
                'rate_album' => true,
                'get_rated_albums' => true,
            )
        );
        get_role( 'administrator' )->add_cap( 'rate_album' );
        get_role( 'administrator' )->add_cap( 'get_rated_albums' );
        get_role( 'administrator' )->add_cap( 'ratifier_admin' );

        add_action( 'admin_init' , array( $this , 'user_management_admin_init' ) );

        add_action( 'updated_option' , array( $this , 'update_user_management_options' ) , 10 , 3 );

        add_action( 'rest_api_init' , array( $this , 'user_rest_api_init' ) );

        add_action( 'init', array( $this , 'restrict_access_to_admin_for_raters' ) );
    }

    public function user_rest_api_init() {
        register_rest_route( $this::NAME_SPACE . $this::VERSION, '/login', array(
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array( $this, 'login' ),
                'permission_callback' => array( $this , 'add_rating_permissions_check' ),
            ),
        ));

        add_filter( 'wp_pre_insert_user_data' , array( $this , 'disable_rest_user_update' ) );
    }

    public function login() {
        $nonce = array( 'wp_nonce' => wp_create_nonce( 'wp_rest' ) );
        wp_send_json_success( $nonce );
    }

    public function get_capabilities( $onlyRater = false ) {
        $return = [
            'rate_album',
            'get_rated_albums',
        ];
        if ( ! $onlyRater ) {
            $return[] = 'ratifier_admin';
        }
        return $return;
    }

    public function update_user_management_options( $option , $old_options , $options ) {

        global $wp_roles;

        if ( $option !== 'ratifier_options' ) {
            return;
        }

        $caps = $this->get_capabilities();

        foreach ( $wp_roles->roles as $role_name => $role ) {
            if ( $role_name === 'administrator' ) {
                continue;
            }
            foreach ( $caps as $cap ) {
                if ( isset( $options[ $role_name . '_caps_' . $cap ] ) && $options[ $role_name . '_caps_' . $cap ] ) {
                    get_role( $role_name )->add_cap( $cap );
                }
                else {
                    get_role( $role_name )->remove_cap( $cap );
                }
            }
        }
    }

    public function user_management_admin_init() {
        global $wp_roles;

        add_settings_section(
            'ratifier_user_management',
            __( 'User management', $this::TEXT_DOMAIN ),
            __return_false(),
            'ratifier'
        );

        foreach ( $wp_roles->roles as $role_name => $role ) {
            if ( $role_name == 'administrator' ) {
                continue;
            }


            add_settings_field(
                $role_name . '_caps',
                $role['name'],
                array($this, 'user_management_admin_section'),
                'ratifier',
                'ratifier_user_management',
                array(
                    'label_for' => $role_name . '_caps',
                    'custom_data' => $role_name,
                )
            );
        }
    }

    public function user_management_admin_section( $args ) {
        global $wp_roles;
        $caps = $this->get_capabilities();

        foreach ( $caps as $cap ) {
            $checked = '';
            if ( isset( $wp_roles->roles[$args['custom_data']]['capabilities'][$cap] ) && $wp_roles->roles[$args['custom_data']]['capabilities'][$cap] ) {
                $checked = ' checked="checked"';
            }
            ?>
            <div style="float: left;text-align: center;padding: .2em .7em">
                <label><?php echo $cap; ?>
                    <br>

                    <input id="<?php echo $args['label_for'] . '_' . $cap; ?>" value="1" type="checkbox"<?php echo $checked; ?> name="ratifier_options[<?php echo $args['label_for'] . '_' . $cap; ?>]">

                </label>
            </div>
            <?php
        }
    }

    public function add_rating_permissions_check( $request ) {
        return current_user_can( 'rate_album' );
    }

    public function get_albums_permissions_check( $request ) {
        return current_user_can( 'get_rated_albums' );
    }

    public function disable_rest_user_update() {
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            wp_send_json_error( array( 'nope' => 'nope' ));
        }
    }

    public function restrict_access_to_admin_for_raters() {

        $is_rater = false;
        $user = wp_get_current_user();
        foreach ( $user->roles as $role ) {
            if ( $role == 'rater' ) {
                $is_rater = true;
            }
        }
        if ( is_admin() && $is_rater && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            wp_redirect( home_url() );
            exit;
        }
    }

}