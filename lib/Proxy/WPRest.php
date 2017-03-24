<?php

namespace WPQL\Proxy;

use WPQL\Proxy\WPRest\AttachmentsControllerProxy;
use WPQL\Proxy\WPRest\PostsControllerProxy;
use WPQL\Proxy\WPRest\UsersControllerProxy;
use WPQL\Proxy\WPRest\TermsControllerProxy;

class WPRest implements ProxyInterface {

    public function register() {

        wpql_register_type( 'Html', [
            'rendered' => 'String',
            'raw' => 'String',
            'protected' => 'Boolean',
        ] );

        wpql_register_type( 'Image', [
            'src' => 'String!',
            'width' => 'Int',
            'height' => 'Int',
            'src_set' => 'String',
        ] );

        foreach ( get_post_types( ['show_in_rest' => true] ) as $post_type ) {
            if ( 'attachment' === $post_type ) {
                $posts_proxy = new AttachmentsControllerProxy();
            }
            else {
                $posts_proxy = new PostsControllerProxy( $post_type );
            }

            foreach ( $posts_proxy->get_types() as $name => $fields ) {
                wpql_register_type( $name, $fields );
            }
        }

        $users_proxy = new UsersControllerProxy();

        foreach ( $users_proxy->get_types() as $name => $fields ) {
            wpql_register_type( $name, $fields );
        }

        $terms_proxy = new TermsControllerProxy( 'category' );

        foreach ( $terms_proxy->get_types() as $name => $fields ) {
            wpql_register_type( $name, $fields );
        }

        foreach ( get_taxonomies( ['show_in_rest' => true] ) as $taxonomy ) {
            $terms_proxy = new TermsControllerProxy( $taxonomy );

            foreach ( $terms_proxy->get_types() as $name => $fields ) {
                wpql_register_type( $name, $fields );
            }
        }

    }
}