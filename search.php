<?php
namespace bike_fun_cal;

/**
 * Make the built-in WordPress search also seach for events in the calendar.
 *
 * References:
 * http://codex.wordpress.org/Custom_Queries
 */

// Add the calevent table to a search query, so that we can use its
// fields in 'posts_join'
add_filter('posts_join', function($join) {
    global $wp_query, $wpdb;        
    global $calevent_table_name;

    if (is_search()) {
        $join .= sprintf('LEFT JOIN %s ON %s.ID = %s.wordpress_id',
                 $calevent_table_name, $wpdb->posts, $calevent_table_name);
    }

    return $join;
});

// Rewrite searches so that they search both the usual WordPress fields, but also the
// custom fields.
add_filter('posts_where', function($old_where) {
    global $wp_query, $wpdb;        
    global $calevent_table_name;

    if (is_search()) {
        $search_term = $wp_query->get('s');
        // Don't search title; WordPress is already doing that by seaching the post's title.
        $fields_to_search = array('descr', 'locname', 'address', 'name', 'tinytitle');

        $new_where = '';
        foreach ($fields_to_search as $field) {
            if ($new_where != '') {
                $new_where .= ' OR ';
            }
            $new_where .= sprintf('%s.%s LIKE \'%%%s%%\'',
                                  $calevent_table_name,
                                  $field,
                                  mysql_real_escape_string($search_term));
        }

        // Make the query be $old_where OR $new_where. But there are formatting complications.
        // 
        // $old_where is formatted something like " AND ... AND ... OR ... "
        // We have to:
        // 1. Still begin with an AND (so WordPress can stick this after 1=1)
        // 2. Stick in another 1=1, so the AND in $old_where matches something.
        return sprintf('AND ((1=1 %s)  OR ( %s ))', $old_where, $new_where);
    }
    else {
        return $old_where;
    }
});


?>