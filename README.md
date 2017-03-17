# WPQL
GraphQL plugin for WordPress

## Instalation
1. Clone repository to `wp-content/plugins` folder
2. Run `$ composer install --no-dev`
3. Activate plugin in the admin panel

## Type System
```php
wpql_register_type( 'Post', [
    'id' => 'Int!',
    'slug' => 'String!',
    'title' => 'String',
    'content' => 'String'
] );
```
## Query System
```php
wpql_register_query( 'post', [
    'type' => 'Post',
    'args' => [
        'id' => 'Int!',
    ],
    'resolve' => 'prefix_post_resolve'
] );

function prefix_post_resolve ( $root, $args ) {
    $post = get_post( $args['id'] );
    
    return [
        'id' => $post->ID,
        'slug' => $post->post_name,
        'title' => get_the_title( $post->post_title ),
        'content' => apply_filters( 'the_content', $post->post_content ),
    ];
}
```

## HTTP Usage
Request
```bash
$ curl -H "Content-Type: application/json" \
  -X POST -d '{"query": "{ post(id: 1) { id, title } } "}' \
  https://example.com/wp-json/wpql/graphql
```
Response
```json
{
    "data": {
        "post": {
            "id": 1,
            "title": "Hello World!"
        }
    }
}
```
