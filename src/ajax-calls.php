<?php
// Garante que o código não seja executado diretamente fora do WordPress
defined('ABSPATH') || exit;
/**
 * Determina as condições de consulta com base nos dados recebidos por POST.
 *
 * @param array $post_data Dados recebidos via POST.
 * @return array Array contendo as condições da consulta geradas com base nos dados recebidos.
 */
function determine_query($post_data)
{
    // Inicialização dos prefixos para a construção da consulta
    $query_h['where_prefix'] = '';
    $query_h['and_prefix'] = '';

    // Verifica e estabelece a condição da data inicial da consulta
    if ($_POST['start'] && (strlen($_POST['start']) > 0)) {
        if ($_POST['campanha']) {
            $query_h['start_date'] = "AND creation_time >= '" . $_POST['start'] . " 00:00:00'";
        } else {
            $query_h['start_date'] = "creation_time >= '" . $_POST['start'] . " 00:00:00'";
        }
    } else {
        $query_h['start_date'] = '';
    }

    // Verifica e estabelece a condição da data final da consulta
    if ($_POST['end'] && (strlen($_POST['end']) > 0)) {
        if ($_POST['campanha']) {
            $query_h['end_date'] = "AND creation_time <= '" . $_POST['end'] . " 23:59:59'";
        } else {
            $query_h['end_date'] = "creation_time <= '" . $_POST['end'] . " 23:59:59'";
        }
    } else {
        $query_h['end_date'] = '';
    }

    // Estabelece os prefixos WHERE e AND com base nas condições da data
    if ((strlen($_POST['start']) > 0) || (strlen($_POST['end']) > 0)) {
        $query_h['where_prefix'] = "WHERE";
    }
    if ((strlen($_POST['start']) > 0) && (strlen($_POST['end']) > 0)) {
        $query_h['and_prefix'] = "AND";
    }

    // Retorna o array contendo as condições da consulta
    return $query_h;
}

/**
 * Callback para recuperar os dados do relatório de testes A/B por AJAX.
 * Gera uma consulta SQL com base nos filtros de data e campanha recebidos via POST e retorna os resultados em formato JSON.
 */
function get_ab_test_report_data()
{
    // Obtenção dos tempos para fins de atualização

    global $wpdb;
    $table_name = $wpdb->prefix . 'ab_test_data';

    // Determina as condições da consulta com base nos dados recebidos via POST
    $query_d = determine_query($_POST);
    $start_date = $query_d['start_date'];
    $end_date = $query_d['end_date'];
    $where_prefix = $query_d['where_prefix'];
    $and_prefix = $query_d['and_prefix'];

    // Constrói a consulta SQL baseada nos filtros de campanha e datas
    if ($_POST['campanha']) {
        $filter_post = $_POST['campanha'];

        $result = $wpdb->get_results("
        SELECT post_id, post_title, cookie_hash, origin_ip, creation_time, page_is, params, destination, return_ip, return_time
        FROM $table_name
        LEFT JOIN $wpdb->posts
        ON $table_name.post_id = $wpdb->posts.ID
        WHERE post_id = $filter_post
        $start_date
        $end_date;
    ");
    } else {
        $result = $wpdb->get_results("
        SELECT post_id, post_title, cookie_hash, origin_ip, creation_time, page_is, params, destination, return_ip, return_time
        FROM $table_name
        LEFT JOIN $wpdb->posts
        ON $table_name.post_id = $wpdb->posts.ID
        $where_prefix
        $start_date
        $and_prefix
        $end_date ;
    ");
    }

    // Retorna os resultados da consulta em formato JSON
    echo json_encode($result);
    wp_die();
}

// Adiciona ação AJAX para recuperar os dados do relatório de testes A/B
add_action('wp_ajax_get_ab_test_report_data', 'get_ab_test_report_data');

/**
 * Callback para recuperar os dados do relatório da página de testes A/B via AJAX.
 * Gera uma consulta SQL com base nos filtros de data e campanha recebidos via POST e retorna os resultados em formato JSON.
 */
function get_ab_page_report_data()
{
    // Obtém todos os horários para fins de atualização

    global $wpdb;
    $table_name = $wpdb->prefix . 'ab_test_data';

    // Determina as condições da consulta com base nos dados recebidos via POST
    $query_d = determine_query($_POST);
    $start_date = $query_d['start_date'];
    $end_date = $query_d['end_date'];
    $where_prefix = $query_d['where_prefix'];
    $and_prefix = $query_d['and_prefix'];

    // Constrói a consulta SQL baseada nos filtros de campanha e datas
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
    // Retorna os resultados da consulta em formato JSON
    echo json_encode($result);
    wp_die();
}

// Adiciona ação AJAX para recuperar os dados do relatório da página de testes A/B
add_action('wp_ajax_get_ab_page_report_data', 'get_ab_page_report_data');

/**
 * Callback para excluir dados do relatório com base na campanha recebida via POST.
 * Se a campanha for especificada, executa uma consulta DELETE no banco de dados para remover os dados correspondentes.
 * Retorna o resultado da exclusão em formato JSON.
 */
function delete_report_data()
{
    // Verifica se a campanha foi enviada via POST
    if ($_POST['campanha']) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ab_test_data';
        $campanha = $_POST['campanha'];

        // Executa a consulta DELETE para remover os dados correspondentes à campanha
        $result = $wpdb->delete(
            $table_name,
            array(
                'post_id' => $campanha // valor na coluna a ser alvo para exclusão
            ),
            array(
                '%d' // formato do valor a ser alvo para exclusão
            )
        );

        // Retorna o resultado da exclusão em formato JSON
        echo json_encode($result);
        wp_die();
    } else {
        // Caso contrário, encerra a execução sem fazer nada
        wp_die();
    }
}

// Adiciona ação AJAX para excluir dados do relatório
add_action('wp_ajax_delete_report_data', 'delete_report_data');

/**
 * Callback para corrigir os dados do relatório com base na campanha recebida via POST.
 * Se a campanha for especificada, executa uma consulta SELECT no banco de dados para recuperar os dados da campanha.
 * Decodifica os parâmetros URL e atualiza os dados no banco de dados.
 * Retorna os resultados da correção em formato JSON.
 */
function fix_report_data()
{
    // Verifica se a campanha foi enviada via POST
    if ($_POST['campanha']) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ab_test_data';
        $campanha = $_POST['campanha'];

        // Consulta SELECT para recuperar os dados da campanha
        $query = ("
            SELECT id, params
            FROM $table_name
            WHERE post_id = $campanha;
        ");

        // Obtém os resultados da consulta
        $results = $wpdb->get_results($query);

        // Decodifica os parâmetros URL para cada resultado
        foreach ($results as $result) {
            $result->params = urldecode($result->params);
        }

        // Atualiza os dados corrigidos no banco de dados
        foreach ($results as $transform) {
            $update = $wpdb->update(
                $table_name,
                array(
                    'params' => $transform->params,
                ),
                array(
                    'id' => $transform->id
                )
            );
        }

        // Retorna os resultados da correção em formato JSON
        echo json_encode($results);
        wp_die();
    } else {
        // Caso contrário, encerra a execução sem fazer nada
        wp_die();
    }
}

// Adiciona ação AJAX para corrigir os dados do relatório
add_action('wp_ajax_fix_report_data', 'fix_report_data');
