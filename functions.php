<?php

use WPQL\Schema;

function wpql_register_type( $name, $fields ) {
    Schema::set_type( $name, $fields );
    $types[ $name ] = $fields;
}

function wpql_register_query( $query, $args ) {
    wpql_register_type( 'Query', [
        $query => $args,
    ] );
}
