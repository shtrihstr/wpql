<?php

namespace WPQL\Proxy\WPRest;

use WP_REST_Controller;
use WP_REST_Request;
use Exception;

abstract class ControllerProxy {

    /**
     * @var WP_REST_Controller
     */
    protected $controller;

    public function set_controller( $controller ) {
        $this->controller = $controller;
    }

    public function call_action( $action, $args, $permissions_check = null ) {
        $args['context'] = 'view';
        $request = new WP_REST_Request();
        $request->set_query_params( $args );

        if ( null != $permissions_check ) {

            $permission = call_user_func_array( [ $this->controller, $permissions_check ], [ $request ] );

            if ( is_wp_error( $permission ) ) {
                throw new Exception( $permission->get_error_message() );
            }
            elseif ( false === $permission || null === $permission ) {
                throw new Exception( __( 'Sorry, you are not allowed to do that.' ) );
            }
        }

        $response = call_user_func_array( [ $this->controller, $action ], [ $request ] );

        if ( is_wp_error( $response ) ) {
            throw new Exception( $response->get_error_message() );
        }

        return $response->get_data();
    }

    public abstract function get_types();

    public function get_wpql_type( $rest_type ) {
        switch ( $rest_type ) {
            case 'string': return 'String';
            case 'integer': return 'Int';
            case 'boolean': return 'Boolean';
            case 'number': return 'Float';
            default: return false;
        }
    }

    public function get_meta_fields( $object ) {
        $meta_fields = [];

        foreach ( get_registered_meta_keys( $object ) as $meta_key => $data ) {
            $type = $this->get_wpql_type( $data['type'] );

            if ( $data['show_in_rest'] && $type ) {
                $meta_fields[ $meta_key ] = $type;
            }
        }

        return $meta_fields;
    }

    public function get_additional_fields( $object ) {
        global $wp_rest_additional_fields;

        if ( empty( $wp_rest_additional_fields[ $object ] ) ) {
            return [];
        }

        $fields = [];

        foreach( $wp_rest_additional_fields[ $object ] as $field => $data ) {

            if ( isset( $data['get_callback'] ) && isset( $data['schema'] ) && isset( $data['schema']['type'] ) && ( $type = $this->get_wpql_type( $data['schema']['type'] ) ) ) {
                $fields[ $field ] = [
                    'type' => $type,
                    'resolve' => function( $root, $args ) use ( $data, $field, $object ) {
                        return call_user_func( $data['get_callback'], $root, $field, new WP_REST_Request(), $object );
                    }
                ];
            }
        }

        return $fields;
    }
}