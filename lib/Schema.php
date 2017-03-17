<?php

namespace WPQL;

class Schema {

    private static $types = [
        'Query' => [],
    ];

    private function __construct() {}

    public static function set_type( $name, array $fields ) {
        if ( 'Query' === $name ) {
            foreach ( $fields as $query => $args ) {
                static::$types[ $name ][ $query ] = $args;
            }
        }
        else {
            static::$types[ $name ] = $fields;
        }
    }

    public static function get_schema() {
        return static::$types;
    }
}