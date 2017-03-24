<?php

namespace WPQL\Proxy\WPRest;
use WP_REST_Posts_Controller;

class PostsControllerProxy extends ControllerProxy {

    private $post_type;

    public function __construct( $post_type ) {
        $this->post_type = $post_type;
        $this->set_controller( new WP_REST_Posts_Controller( $this->post_type ) );
    }

    private function get_object_fields() {
        $fields = [
            'date' => 'String',
            'date_gmt' => 'String',
            'guid' => 'Html!',
            'id' => 'Int!',
            'link' => 'String',
            'modified' => 'String',
            'modified_gmt' => 'String',
            'slug' => 'String',
            'status' => 'String',
            'title' => 'Html!',
            'content' => 'Html!',
            'excerpt' => 'Html!',
            'comment_status' => 'String',
            'ping_status' => 'String',
            'format' => 'String',
            'sticky' => 'Boolean',
            'template' => 'String',
            'uuid' => [
                'type' => 'ID!',
                'resolve' => function ( $object ) {
                    return 'post-' . $object['id'];
                }
            ],
            'author' => [
                'type' => NameGenerator::get_type_name( 'user' ),
                'resolve' => function( $post, $args ) {
                    if ( empty( $post['author'] ) ) {
                        return null;
                    }

                    $users_proxy = new UsersControllerProxy();
                    return $users_proxy->call_action( 'get_item', [ 'id' => $post['author'] ], 'get_item_permissions_check' );
                }
            ],
            'featured' => [
                'type' => NameGenerator::get_type_name( 'attachment' ),
                'resolve' => function( $post, $args ) {
                    if ( empty( $post['featured_media'] ) ) {
                        return null;
                    }

                    $users_proxy = new AttachmentsControllerProxy();
                    return $users_proxy->call_action( 'get_item', [ 'id' => $post['featured_media'] ], 'get_item_permissions_check' );
                },
            ],
        ];

        foreach ( $this->get_additional_fields( $this->post_type ) as $field => &$data ) {
            if ( ! isset( $fields[ $field ] ) ) {
                $fields[ $field ] = &$data;
            }
        }

        foreach ( $this->get_terms_fields() as $field => &$data ) {
            $fields[ $field ] = &$data;
        }

        return $fields;
    }


    private function get_terms_fields() {
        $fields = [];
        $taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), ['show_in_rest' => true] );

        foreach ( $taxonomies as $taxonomy ) {

            $term_type_object = NameGenerator::get_type_name( $taxonomy->name );
            $term_query_items = NameGenerator::get_query_name( $taxonomy->name, 'items' );

            $terms_proxy = new TermsControllerProxy( $taxonomy->name );

            $fields[ $term_query_items ] = [
                'type' => "[$term_type_object!]",
                'args' => $terms_proxy->get_collection_args( ['post'] ),
                'resolve' => function( $post, $args ) use ( $terms_proxy ) {
                    $args['post'] = $post['id'];
                    return $terms_proxy->call_action( 'get_items', $args, 'get_items_permissions_check' );
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
            'after' => 'String',
            'author' => '[Int!]',
            'author_exclude' => '[Int!]',
            'before' => 'String',
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
            'slug' => 'String',
            'status' => [
                'type' => 'String',
                'defaultValue' => 'publish',
            ],
            'sticky' => 'Boolean',
        ];

        if ( ! empty( $exclude ) ) {
            foreach ( $exclude as $value ) {
                unset( $args[ $value ] );
            }
        }

        return $args;
    }

    public function get_types() {

        $type_object = NameGenerator::get_type_name( $this->post_type );
        $type_meta = NameGenerator::get_type_name( $this->post_type, 'meta' );

        $query_item = NameGenerator::get_query_name( $this->post_type, 'item' );
        $query_items = NameGenerator::get_query_name( $this->post_type, 'items' );

        $types = [
            'Query' => [
                $query_item => [
                    'type' => $type_object,
                    'args' => [
                        'id' => 'Int'
                    ],
                    'resolve' => function( $root, $args ) {
                        // todo: add getting by slug and uuid
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
        $meta_fields = $this->get_meta_fields( $this->post_type );

        if ( ! empty( $meta_fields ) ) {
            $post_fields['meta'] = "$type_meta!";
            $types[ $type_meta ] = $meta_fields;
        }

        $types[ $type_object ] = $object_fields;

        return $types;
    }
}