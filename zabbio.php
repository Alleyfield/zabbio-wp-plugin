<?php
/**
 * @package Zabbio
 * @version 0.1
 */
/*
Plugin Name: Zabbio plugin
Plugin URI: http://wordpress.org/plugins/Zabbio/
Description: Plugin for enabling Zabbio to interact with your Wordpress site
Author: Alleyfield Ltd
Version: 0.1.1
Author URI: www.zabb.io
*/
class zabbio_options_page {
        function __construct() {
                add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        }
        function admin_menu() {
                add_options_page(
                        'Official Zabbio Plugin',
                        'Zabbio Settings',
                        'manage_options',
                        'zabbio_plugin_settings',
                        array(
                                $this,
                                'zabbio_settings_page'
                        )
                );
        }
        function  zabbio_settings_page() {
        if ( !current_user_can ( 'manage_options' ) ) {
            wp_die( 'User not authorized to manage Zabbio' );
        }
        require('inc/options-page-wrapper.php');
        }
}
new zabbio_options_page;
/*
 * Zabbio user role creation on plugin activation
 * 
 */
function add_role_and_user_for_Zabbio_on_plugin_activate() {
    add_role(
        'ZabbioApp',
        __( 'ZabbioApp' ),
        array(
            'create_posts' => true,
            'edit_others_posts' => true,
            'edit_posts' => true,
            'edit_published_posts' => true,
            'list_users' => true,
            'manage_categories' => true,
            'publish_posts' => true,
            'read' => true
        )
    );

    $user_name = 'ZabbioApp';
    $user_email = '';
    if ( !username_exists($user_name)  ) {
        $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
        $user_id = wp_insert_user( 
            array(
                'user_pass' => $random_password,
                'user_login' => $user_name,
                'role' => 'ZabbioApp',
                'user_email' => ''
            )
        );
    } else {
        $random_password = __('User already exists.  Password inherited.');
    }
}
register_activation_hook( __FILE__, 'add_role_and_user_for_Zabbio_on_plugin_activate' );

  class all_taxonomies
{
    public function __construct()
    {
        $version = '1';
        $namespace = 'zabbio/v' . $version;
        $base = 'taxonomies';
        register_rest_route($namespace, '/' . $base, array(
            'methods' => 'GET',
            'callback' => array($this, 'get_taxonomies'),
            'permission_callback' => function() {
                return current_user_can('create_posts');
            }
        ));
    }

    public function get_taxonomies($object)
    {
        $return = array();
        $args = array(
            'public' => true,
            '_builtin' => false
        );
        $output = 'objects'; // or objects
        $operator = 'and'; // 'and' or 'or'
        $taxonomies = get_taxonomies($args, $output, $operator);
        return new WP_REST_Response($taxonomies, 200);
    }
}

class insert_term extends WP_REST_Controller
{
    public function __construct()
    {
        $version = '1';
        $namespace = 'zabbio/v' . $version;
        $base = 'insert_term';
        register_rest_route($namespace, '/' . $base, array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'insert_term'),
            'args' => array(
                'term' => array(
                    'required'
                ),
                'taxonomy' => array(
                    'required'
                )
            ),
            'permission_callback' => function() {
                return current_user_can('create_posts');
            }
        ));
    }

    public function insert_term( WP_REST_Request $request)
    {
        $term = $request['term'];
        $taxonomy = $request['taxonomy'];
        $term_exists = term_exists($term, $taxonomy);
        if ( $term_exists !== 0 && $term_exists !== null ) {
            // term exists, return response with term_id
          return new WP_REST_Response($term_exists, 200);
        } else {
            // term does not exist, insert and return term_id
            $term_added = wp_insert_term($term, $taxonomy);
            return new WP_REST_Response($term_added, 200);       
        }

    }
}



  class all_terms
{
    public function __construct()
    {
        $version = '1';
        $namespace = 'zabbio/v' . $version;
        $base = 'terms';
        register_rest_route($namespace, '/' . $base, array(
            'methods' => 'GET',
            'callback' => array($this, 'get_terms_for_taxonomy'),
            'permission_callback' => function() {
                return current_user_can('create_posts');
            }
        ));
    }

    public function get_terms_for_taxonomy($object)
    {
        $return = array();
        $args = array(
            'public' => true,
            '_builtin' => false
        );
        $return = get_terms($_GET['taxonomy']);
        return new WP_REST_Response($return, 200);
    }
}

add_action('rest_api_init', function () {
    $all_terms = new all_terms;
    $insert_term = new insert_term;
    $all_taxonomies = new all_taxonomies;
});
?>
