<?php

namespace WPQL\Type;

use WPQL\Exception\InvalidTypeException;
use GraphQL\Type\Definition\ObjectType;

class TypeFactory {

    private $name;
    private $fields = [];

    /**
     * @var TypeMapper
     */
    private $type_mapper;

    private function __construct( $name, $fields, $type_mapper ) {
        $this->name = $name;
        $this->type_mapper = $type_mapper;
        $this->fields = $this->filter_fields( $fields );
    }

    public static function build( $name, $fields, $type_mapper ) {
        $factory = new static( $name, $fields, $type_mapper  );
        return $factory->make_object();
    }

    private function make_object() {
        return new ObjectType([
            'name' => $this->name,
            'fields' => $this->fields,
        ]);
    }

    private function filter_fields( $fields ) {

        return $this->map_types( $fields );
    }

    private function map_types( $fields ) {
        $mapped_fields = [];
        foreach ( $fields as $field => $data ) {

            // only type
            if ( is_string( $data ) ) {

                $mapped_fields[ $field ] = [
                    'type' => $this->type_mapper->get_type( $data ),
                ];
            }
            elseif ( is_array( $data ) ) {

                if ( ! isset( $data['type'] ) ) {
                    throw new InvalidTypeException( "Type property must be declared for '$field'." );
                }

                $mapped_fields[ $field ] = [
                    'type' => $this->type_mapper->get_type( $data['type'] ),
                ];

                if ( ! empty( $data['args'] ) ) {
                    $mapped_fields[ $field ]['args'] = $this->map_types( $data['args'] );
                }

                if ( isset ( $data['defaultValue'] ) ) {
                    $mapped_fields[ $field ]['defaultValue'] = $data['defaultValue'];
                }

                if ( isset ( $data['resolve'] ) ) {
                    $mapped_fields[ $field ]['resolve'] = $data['resolve'];
                }

            }
            else {
                throw new InvalidTypeException( "Type property must be declared for '$field'." );
            }
        }

        return $mapped_fields;
    }
}