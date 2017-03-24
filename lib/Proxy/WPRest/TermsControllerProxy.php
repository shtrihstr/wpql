<?php

namespace WPQL\Proxy\WPRest;
use WP_REST_Terms_Controller;

class TermsControllerProxy extends ControllerProxy {

    private $taxonomy;

    public function __construct( $taxonomy ) {
        $this->taxonomy = $taxonomy;
        $this->set_controller( new WP_REST_Terms_Controller( $this->taxonomy ) );
    }

    private function get_object_fields() {
        $fields = [
            'id' => 'Int!',
            'count' => 'Int!',
            'description' => 'String',
            'link' => 'String',
            'name' => 'String',
            'slug' => 'String',
            'uuid' => [
                'type' => 'ID!',
                'resolve' => function ( $object ) {
                    return 'term-' . $object['id'];
                }
            ],
        ];

        foreach ( $this->get_additional_fields( $this->taxonomy ) as $field => &$data ) {
            if ( ! isset( $fields[ $field ] ) ) {
                $fields[ $field ] = &$data;
            }
        }

        if ( is_taxonomy_hierarchical( $this->taxonomy ) ) {
            $fields['parent'] = [
                'type' => NameGenerator::get_type_name( $this->taxonomy ),
                'resolve' => function( $child_term, $_ ) {
                    $id = $child_term['parent'];

                    if ( ! $id ) {
                        return null;
                    }

                    $term_proxy = new static( $this->taxonomy );
                    $term_proxy->call_action( 'get_items', [ 'id' => $id ], 'get_items_permissions_check' );
                }
            ];
        }

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
                'args' => $posts_proxy->get_collection_args( [ $post_query_items ] ),
                'resolve' => function( $term, $args ) use ( $posts_proxy, $post_query_items ) {
                    // todo: implement mapping $post_query_items => rest property
                    $args[ $post_query_items ] = [ $term['id'] ];
                    return $posts_proxy->call_action( 'get_items', $args, 'get_items_permissions_check' );
                }
            ];
        }

        return $fields;
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
                'defaultValue' => 'desc',
            ],
            'orderby' => [
                'type' => 'String',
                'defaultValue' => 'date',
            ],
            'hide_empty' => 'Boolean',
            'parent' => 'Int',
            'post' => 'Int',
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

        $type_object = NameGenerator::get_type_name( $this->taxonomy );
        $type_meta = NameGenerator::get_type_name( $this->taxonomy, 'meta' );

        $query_item = NameGenerator::get_query_name( $this->taxonomy, 'item' );
        $query_items = NameGenerator::get_query_name( $this->taxonomy, 'items' );

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
            ],
        ];

        $object_fields = $this->get_object_fields();
        $meta_fields = $this->get_meta_fields( $this->taxonomy );

        if ( ! empty( $meta_fields ) ) {
            $post_fields['meta'] = "$type_meta!";
            $types[ $type_meta ] = $meta_fields;
        }

        $types[ $type_object ] = $object_fields;

        return $types;
    }
}