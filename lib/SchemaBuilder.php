<?php

namespace WPQL;

use WPQL\Type\TypeFactory;
use WPQL\Type\TypeMapper;

class SchemaBuilder {

    public static function build( array $schema  ) {
        $types = [];
        $type_mapper = new TypeMapper( $types );
        $types = ['123'];
        foreach( $schema as $name => $fields ) {
            $types[ $name ] = TypeFactory::build( $name, $fields, $type_mapper );
        }

        return $types;
    }

}