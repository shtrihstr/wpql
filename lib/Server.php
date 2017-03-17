<?php

namespace WPQL;

use WP_REST_Request;
use Exception;
use GraphQL;

class Server {

    public function dispatch( WP_REST_Request $request ) {
        $request_params = $this->_parse_request( $request );

        try {
            $schema = SchemaBuilder::build( Schema::get_schema() );
        }
        catch ( Exception $e ) {
            return [
                'data' => null,
                'errors' => [
                    'message' => $e->getMessage(),
                ]
            ];
        }

        $graph_ql_schema = new GraphQL\Schema( [
            'query' => $schema['Query'],
        ]);

        return GraphQL\GraphQL::execute( $graph_ql_schema, $request_params['query'], null, /* $context */ null, $request_params['variables'], $request_params['operation_name'] );
    }

    protected function _parse_request( WP_REST_Request $request ) {

        $query = '';
        $variables = null;
        $operation_name = null;

        if ( 'GET' === $request->get_method() ) {
            $params = $request->get_params();

            if ( isset( $params['query'] ) ) {
                $query = $params['query'];
            }

            if ( isset( $params['variables'] ) ) {
                $variables = json_decode( $params['variables'] );
            }

            if ( isset( $params['operationName'] ) ) {
                $operation_name = $params['operationName'];
            }
        }
        elseif ( 'POST' === $request->get_method() ) {

            $content_type = $request->get_content_type();

            if ( preg_match( '/(graphql|json)/', $content_type['value'] ) ) {
                $params = $request->get_json_params();
            }
            else {
                $params = $request->get_body_params();
            }

            if ( isset( $params['query'] ) ) {
                $query = $params['query'];
            }

            if ( isset( $params['variables'] ) ) {
                $variables = $params['variables'];
            }

            if ( isset( $params['operationName'] ) ) {
                $operation_name = $params['operationName'];
            }
        }

        return [
            'query' => $query,
            'variables' => $variables,
            'operation_name' => $operation_name,
        ];
    }

}