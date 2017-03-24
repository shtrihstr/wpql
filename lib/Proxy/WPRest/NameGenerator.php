<?php

namespace WPQL\Proxy\WPRest;

use Doctrine\Common\Inflector\Inflector;

class NameGenerator {

    private static $names = [
        'user' => [
            'type' => [
                'object' => 'User',
                'meta' => 'UserMeta',
                'avatar' => 'Avatar',
            ],
            'query' => [
                'item' => 'user',
                'items' => 'users',
                'current' => 'me',
            ],
        ],
    ];

    private function __construct() {}


    public static function get_type_name( $resource, $object = 'object' ) {

        if ( 'post_tag' === $resource ) {
            $resource = 'tag';
        }

        if ( ! isset( static::$names[ $resource ] ) ) {
            static::$names[ $resource ]  = [
                'type' => [],
                'query' => [],
            ];
        }

        if ( ! isset( static::$names[ $resource ]['type'][ $object ] ) ) {

            $resource_object_name = Inflector::classify( $resource );

            static::$names[ $resource ]['type'] = [
                'object' => $resource_object_name,
                'meta' => $resource_object_name . 'Meta',
            ];
        }

        return apply_filters( "wpql_rest_{$resource}_name_{$object}", static::$names[ $resource ]['type'][ $object ] );
    }

    public static function get_query_name( $resource, $type ) {

        if ( 'post_tag' === $resource ) {
            $resource = 'tag';
        }

        if ( ! isset( static::$names[ $resource ] ) ) {
            static::$names[ $resource ]  = [
                'type' => [],
                'query' => [],
            ];
        }

        if ( ! isset( static::$names[ $resource ]['query'][ $type ] ) ) {

            $singular = Inflector::singularize( $resource );
            $plural = Inflector::pluralize( $resource );

            if ( $singular == $plural ) {
                $plural .= '_items';
            }

            static::$names[ $resource ]['query'] = [
                'item' => $singular,
                'items' => $plural,
            ];
        }

        return static::$names[ $resource ]['query'][ $type ];
    }
}