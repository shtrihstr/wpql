<?php

namespace WPQL\Type;

use WPQL\Exception\InvalidTypeException;
use GraphQL\Type\Definition\Type;


class TypeMapper {

    private $scalar_types = [];
    private $object_types = [];


    public function __construct( &$object_types_list ) {
        $this->object_types = &$object_types_list;
        $this->scalar_types = [
            'ID' => Type::id(),
            'String' => Type::string(),
            'Float' => Type::float(),
            'Int' => Type::int(),
            'Boolean' => Type::boolean(),
        ];
    }

    private function parse_recursive( $string ) {

        if ( preg_match( '/\!$/', $string ) ) {
            return Type::nonNull( $this->parse_recursive( preg_replace( '/\!$/', '', $string ) ) );
        }

        if ( preg_match( '/^\[(.*)\]$/', $string, $match ) ) {
            return Type::listOf( $this->parse_recursive( $match[1] ) );
        }

        if ( isset( $this->scalar_types[ $string ] ) ) {
            return $this->scalar_types[ $string ];
        }

        if ( isset( $this->object_types[ $string ] ) ) {
            return $this->object_types[ $string ];
        }

        return function() use ( $string ) {

            if ( isset( $this->object_types[ $string ] ) ) {
                return $this->object_types[ $string ];
            }
            else {
                throw new InvalidTypeException( "Invalid object type '$string'." );
            }

        };
    }

    public function get_type( $string ) {
        if ( ! is_string( $string ) ) {
            return $string;
        }

        return $this->parse_recursive( str_replace( ' ', '', $string ) );
    }
}