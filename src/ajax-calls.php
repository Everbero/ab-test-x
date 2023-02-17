<?php

defined('ABSPATH') || exit;
function determine_query($post_data){
     
    $query_h['where_prefix'] = '';
    $query_h['and_prefix'] = '';

    if($_POST['start'] && (strlen($_POST['start']) > 0)){
        if($_POST['campanha']){
            $query_h['start_date'] = "AND creation_time >= '". $_POST['start']. "'";
        }else{
            $query_h['start_date'] = "creation_time >= '". $_POST['start']. "'";
        }
        
    }else{
        $query_h['start_date'] = '';
    }

    if($_POST['end'] && (strlen($_POST['end']) > 0)){
        if($_POST['campanha']){
            $query_h['end_date'] = "AND creation_time <= '" .$_POST['end']. "'";
        }else{
            $query_h['end_date'] = "creation_time <= '" .$_POST['end']. "'";
        }
    }else{
        $query_h['end_date'] = '';
    }
    if((strlen($_POST['start']) > 0) || (strlen($_POST['end']) > 0)){
        $query_h['where_prefix'] = "WHERE";
    }
    if((strlen($_POST['start']) > 0) && (strlen($_POST['end']) > 0)){
        $query_h['and_prefix'] = "AND";
    }

    return $query_h;
}
function get_ab_test_report_data() {
    // Get all times for update purposes

    global $wpdb;
    $table_name = $wpdb->prefix . 'ab_test_data';

    $query_d = determine_query($_POST);
    $start_date = $query_d['start_date'];
    $end_date = $query_d['end_date']; 
    $where_prefix = $query_d['where_prefix'];
    $and_prefix = $query_d['and_prefix'];
    

    //WHERE post_id = '$value'
    if ($_POST['campanha']) {
        $filter_post = $_POST['campanha'];

        $result = $wpdb->get_results("
        SELECT post_id, post_title, cookie_hash, origin_ip, creation_time, page, params, destination, return_ip, return_time
        FROM $table_name
        LEFT JOIN $wpdb->posts
        ON $table_name.post_id = $wpdb->posts.ID
        WHERE post_id = $filter_post
        $start_date
        $end_date;
    ");
    } else {
        $result = $wpdb->get_results("
        SELECT post_id, post_title, cookie_hash, origin_ip, creation_time, page, params, destination, return_ip, return_time
        FROM $table_name
        LEFT JOIN $wpdb->posts
        ON wp_ab_test_data.post_id = wp_posts.ID
        $where_prefix
        $start_date
        $and_prefix
        $end_date ;
    ");
    }

    echo json_encode($result);
    wp_die();
}
add_action('wp_ajax_get_ab_test_report_data', 'get_ab_test_report_data');

function get_ab_page_report_data() {
    // Get all times for update purposes

    global $wpdb;
    $table_name = $wpdb->prefix . 'ab_test_data';
    

    $query_d = determine_query($_POST);
    $start_date = $query_d['start_date'];
    $end_date = $query_d['end_date']; 
    $where_prefix = $query_d['where_prefix'];
    $and_prefix = $query_d['and_prefix'];

    if ($_POST['campanha']) {
        $filter_post = $_POST['campanha'];
        
        $query = "
            SELECT
                IFNULL(destination, 'acesso direto') as pagina,
                post_title,
                count(post_id) as acessos,
                count(return_time) as conversoes,
                count(return_time) / count(post_id) * 100 as porcentagem
            FROM
                    $table_name
                    LEFT JOIN $wpdb->posts ON $table_name.post_id = $wpdb->posts.ID
            WHERE
                    post_id =  $filter_post
            $start_date
            $end_date    
            GROUP BY
                    destination
            ORDER BY
                    acessos DESC;
        ";

        $result = $wpdb->get_results($query);

    } else {
        $query = "
            SELECT
                IFNULL(destination, 'acesso direto') as pagina,
                post_title,
                count(post_id) as acessos,
                count(return_time) as conversoes,
                count(return_time) / count(post_id) * 100 as porcentagem
            FROM
                    $table_name
                    LEFT JOIN $wpdb->posts ON $table_name.post_id = $wpdb->posts.ID
            $where_prefix
            $start_date
            $and_prefix
            $end_date 
            GROUP BY
                    destination
            ORDER BY
                    acessos DESC;
        ";
        $result = $wpdb->get_results($query);
    }
    echo json_encode($result);
    wp_die();
}
add_action('wp_ajax_get_ab_page_report_data', 'get_ab_page_report_data');

function delete_report_data(){
    if($_POST['campanha']){
        global $wpdb;
        $table_name = $wpdb->prefix . 'ab_test_data';
        $campanha = $_POST['campanha'];

        $result = $wpdb->delete(
            $table_name,
            array(
                'post_id' => $campanha // value in column to target for deletion
            ),
            array(
                '%d' // format of value being targeted for deletion
            )
        );

        echo json_encode($result);
        wp_die();
    }else{
        wp_die();
    }
    
}
add_action('wp_ajax_delete_report_data', 'delete_report_data');