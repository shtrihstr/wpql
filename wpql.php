<?php
/**
 * Plugin Name: WPQL
 */

add_action( 'rest_api_init', function() {

    if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
        return;
    }

    register_rest_route( 'wpql', 'graphql', [
        'methods' => apply_filters( 'wpql_http_methods', 'GET, POST' ),
        'accept_json' => true,
        'callback' => function ( WP_REST_Request $request ) {

            $loader = require __DIR__ . '/vendor/autoload.php';
            $loader->addPsr4( 'WPQL\\', __DIR__ . '/lib' );

            require_once __DIR__ . '/functions.php';

            do_action( 'wpql_init' );

            $wpql_server = new \WPQL\Server();
            return $wpql_server->dispatch( $request );
        },
    ]);
});
