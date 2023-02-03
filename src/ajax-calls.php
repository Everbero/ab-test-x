<?php

defined('ABSPATH') || exit;

function get_ab_test_report_data() {
    // Get all times for update purposes

    global $wpdb;
    $table_name = $wpdb->prefix . 'ab_test_data';
    //WHERE post_id = '$value'
    if ($_POST['campanha']) { 
        $filter_post = $_POST['campanha'];
        
        $result = $wpdb->get_results("
        SELECT post_id, post_title, cookie_hash, origin_ip, creation_time, page, params, destination, return_ip, return_time
        FROM $table_name
        LEFT JOIN $wpdb->posts
        ON $table_name.post_id = $wpdb->posts.ID
        WHERE post_id = $filter_post;
    ");

    } else {
        $result = $wpdb->get_results("
        SELECT post_id, post_title, cookie_hash, origin_ip, creation_time, page, params, destination, return_ip, return_time
        FROM $table_name
        LEFT JOIN $wpdb->posts
        ON wp_ab_test_data.post_id = wp_posts.ID;
    ");
    }

    echo json_encode($result);
    wp_die();
}
add_action('wp_ajax_get_ab_test_report_data', 'get_ab_test_report_data');