<?php

namespace WPQL\Proxy\WPRest;
use WP_REST_Users_Controller;

class UsersControllerProxy extends ControllerProxy {

    public function __construct() {
        $this->set_controller( new WP_REST_Users_Controller() );
    }

    private function get_object_fields() {
        $fields = [
            'id' => 'Int!',
            'name' => 'String',
            'url' => 'String',
            'description' => 'String',
            'link' => 'String',
            'slug' => 'String',
            'uuid' => [
                'type' => 'ID!',
                'resolve' => function ( $object ) {
                    return 'user-' . $object['id'];
                }
            ],
        ];

        foreach ( $this->get_additional_fields( 'user' ) as $field => &$data ) {
            if ( ! isset( $fields[ $field ] ) ) {
                $fields[ $field ] = &$data;
            }
        }

        $fields['avatar'] = $this->get_avatar_field();

        foreach ( $this->get_posts_fields() as $field => &$data ) {
            $fields[ $field ] = &$data;
        }

        return $fields;
    }

    private function get_posts_fields() {
        $fields = [];

        foreach ( get_post_types( ['show_in_rest' => true] ) as $post_type ) {

            $post_type_object = NameGenerator::get_type_name( $post_type );
            $post_query_items = NameGenerator::get_query_name( $post_type, 'items' );

            $posts_proxy = new PostsControllerProxy( $post_type );

            $fields[ $post_query_items ] = [
                'type' => "[$post_type_object!]",
                'args' => $posts_proxy->get_collection_args( [ 'author', 'author_exclude' ] ),
                'resolve' => function( $user, $args ) use ( $posts_proxy ) {
                    $args['author'] = [ $user['id'] ];
                    return $posts_proxy->call_action( 'get_items', $args, 'get_items_permissions_check' );
                }
            ];
        }

        return $fields;
    }

    private function get_avatar_field() {
        $type_avatar = NameGenerator::get_type_name( 'user', 'avatar' );

        return [
            'type' => "$type_avatar!",
            'args' => [
                'size' => [
                    'type' => 'Int',
                    'defaultValue' => 96,
                ],
            ],
            'resolve' => function( $user, $args ) {
                return get_avatar_data( $user['id'], $args );
            }
        ];
    }

    public function get_collection_args( array $exclude = [] ) {
        $args = [
            'page' => [
                'type' => 'Int',
                'defaultValue' => 1,
            ],
            'per_page' => [
                'type' => 'Int',
                'defaultValue' => 10,
            ],
            'search' => 'String',
            'exclude' => '[Int!]',
            'include' => '[Int!]',
            'offset' => 'Int',
            'order' => [
                'type' => 'String',
                'defaultValue' => 'asc',
            ],
            'orderby' => [
                'type' => 'String',
                'defaultValue' => 'name',
            ],
            'slug' => 'String',
        ];

        if ( ! empty( $exclude ) ) {
            foreach ( $exclude as $value ) {
                unset( $args[ $value ] );
            }
        }

        return $args;
    }

    public function get_types() {

        $type_object = NameGenerator::get_type_name( 'user' );
        $type_meta = NameGenerator::get_type_name( 'user', 'meta' );
        $type_avatar = NameGenerator::get_type_name( 'user', 'avatar' );

        $query_item = NameGenerator::get_query_name( 'user', 'item' );
        $query_items = NameGenerator::get_query_name( 'user', 'items' );
        $query_current = NameGenerator::get_query_name( 'user', 'current' );


        $types = [
            'Query' => [
                $query_item => [
                    'type' => $type_object,
                    'args' => [
                        'id' => 'Int'
                    ],
                    'resolve' => function( $root, $args ) {
                        return $this->call_action( 'get_item', $args, 'get_item_permissions_check' );
                    }
                ],
                $query_items => [
                    'type' => "[$type_object!]",
                    'args' => $this->get_collection_args(),
                    'resolve' => function( $root, $args ) {
                        return $this->call_action( 'get_items', $args, 'get_items_permissions_check' );
                    }
                ],
                $query_current => [
                    'type' => $type_object,
                    'resolve' => function( $root, $args ) {
                        $id = get_current_user_id();

                        if ( ! $id ) {
                            return null;
                        }

                        return $this->call_action( 'get_item', [ 'id' => $id ], 'get_item_permissions_check' );
                    }
                ],
            ],
        ];

        $object_fields = $this->get_object_fields();
        $meta_fields = $this->get_meta_fields( 'user' );

        if ( ! empty( $meta_fields ) ) {
            $post_fields['meta'] = "$type_meta!";
            $types[ $type_meta ] = $meta_fields;
        }

        $types[ $type_object ] = $object_fields;
        $types[ $type_avatar ] = [
            'url' => 'String!',
            'size' => 'Int!',
        ];

        return $types;
    }
}