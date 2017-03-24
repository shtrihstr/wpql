<?php

namespace WPQL\Proxy\WPRest;
use WP_REST_Attachments_Controller;

class AttachmentsControllerProxy extends ControllerProxy {

    public function __construct() {
        $this->set_controller( new WP_REST_Attachments_Controller( 'attachment' ) );
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
            'comment_status' => 'String',
            'ping_status' => 'String',
            'alt_text' => 'String',
            'caption' => 'String',
            'description' => 'String',
            'media_type' => 'String',
            'mime_type' => 'String',
            'source_url' => 'String',
            'uuid' => [
                'type' => 'ID!',
                'resolve' => function ( $object ) {
                    return 'post-' . $object['id'];
                }
            ],
        ];

        foreach ( $this->get_additional_fields( 'attachment' ) as $field => &$data ) {
            if ( ! isset( $fields[ $field ] ) ) {
                $fields[ $field ] = &$data;
            }
        }

        $fields[ 'image' ] = [
            'type' => 'Image',
            'args' => [
                'size' => 'String',
                'width' => 'Int',
                'height' => 'Int',
            ],
            'resolve' => function( $attachment, $args ) {
                if ( 'image' != $attachment['media_type'] ) {
                    return null;
                }

                $size = 'full';

                if ( ! empty( $args['size'] ) ) {
                    $size = $args['size'];
                }
                elseif ( isset( $args['width'] ) || isset( $args['height'] ) ) {
                    $size = [
                        isset( $args['width'] ) ? $args['width'] : 0,
                        isset( $args['height'] ) ? $args['height'] : 0,
                    ];
                }

                $img_data = wp_get_attachment_image_src( $attachment['id'], $size );

                if ( ! $img_data ) {
                    return null;
                }

                $src_set = wp_get_attachment_image_srcset( $attachment['id'], $size );
                if ( ! $src_set ) {
                    $src_set = null;
                }

                return [
                    'src' => $img_data[0],
                    'src_set' => $src_set,
                    'width' => $img_data[1],
                    'height' => $img_data[2],
                ];
            },
        ];

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
            'media_type' => 'String',
            'mime_type' => 'String',
        ];

        if ( ! empty( $exclude ) ) {
            foreach ( $exclude as $value ) {
                unset( $args[ $value ] );
            }
        }

        return $args;
    }

    public function get_types() {

        $type_object = NameGenerator::get_type_name( 'attachment' );
        $type_meta = NameGenerator::get_type_name( 'attachment', 'meta' );

        $query_item = NameGenerator::get_query_name( 'attachment', 'item' );
        $query_items = NameGenerator::get_query_name( 'attachment', 'items' );

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
        $meta_fields = $this->get_meta_fields( 'attachment' );

        if ( ! empty( $meta_fields ) ) {
            $post_fields['meta'] = "$type_meta!";
            $types[ $type_meta ] = $meta_fields;
        }

        $types[ $type_object ] = $object_fields;

        return $types;
    }
}