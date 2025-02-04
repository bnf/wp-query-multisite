# WordPress Query Multisite
This is a plugin version of [miguelpeixe/WP_Query_Multisite](https://github.com/miguelpeixe/WP_Query_Multisite), which is a custom version of [WP_Query_Multisite](https://github.com/ericandrewlewis/WP_Query_Multisite) by [ericandrewlewis](https://github.com/ericandrewlewis) to support multisite post queries.

-----------------

## Example usage

```php
$query = new WP_Query(['multisite' => '1']);
while($query->have_posts()) : $query->the_post();
    echo $blog_id . get_the_title() . "<BR>";
endwhile; 
wp_reset_postdata();
```

To modify what sites are queried, create a 'sites' element in the $args in the constructor parameter, with a sub-element of either 'sites__in' or 'sites__not_in', which will be an array similar to 'posts__in' in the WP_Query object. 

```php
$args = [
    'multisite' => 1,
    'sites__in' => [1, 2, 3, 5]
];
$query = new WP_Query($args);
while($query->have_posts()) : $query->the_post();
    echo $blog_id . get_the_title() . "<BR>";
endwhile; 
wp_reset_postdata();
```

### Automatic multisite search example

On your functions.php:

```php
function my_multisite_search($query) {
    if(is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->is_search) {
        $query->set('multisite', 1);
    }
}
add_action('pre_get_posts', 'my_multisite_search');
```
