<?php
defined('ABSPATH') || exit;
/*
 * Funcão para criar e registrar o novo tipo de post
 */

function abtestx_custom_post_type() {

    // TODOS OS RÓTULOS DO PAINEL ADMIN
    $labels = array(
        'name' => __('Campanhas A/B', 'Post Type General Name', 'abtestx'),
        'singular_name' => __('Campanha A/B', 'Post Type Singular Name', 'abtestx'),
        'menu_name' => __('Campanhas A/B', 'abtestx'),
        'parent_item_colon' => __('Campanha pai', 'abtestx'),
        'all_items' => __('Todas as campanhas', 'abtestx'),
        'view_item' => __('Ver campanha', 'abtestx'),
        'add_new_item' => __('Criar campanha', 'abtestx'),
        'add_new' => __('Criar nova', 'abtestx'),
        'edit_item' => __('Editar campanha', 'abtestx'),
        'update_item' => __('Atualizar campanha', 'abtestx'),
        'search_items' => __('Pesquisar campanhas', 'abtestx'),
        'not_found' => __('Campanha não encontrada', 'abtestx'),
        'not_found_in_trash' => __('Nenhuma campanha encontrada no lixo', 'abtestx'),
    );

    // OUTRAS OPCOES DO CPT

    $args = array(
        'label' => __('campanha', 'abtestx'),
        'description' => __('Campanhas de teste AB', 'abtestx'),
        'labels' => $labels,
        // SUPORTE
        'supports' => array('title'),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_admin_bar' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-welcome-view-site',
        'can_export' => true,
        'has_archive' => false,
        'exclude_from_search' => true,
        'publicly_queryable' => false,
        'capability_type' => 'post',
        'show_in_rest' => true,
        // metaboxes
        'register_meta_box_cb' => 'abtestx_add_metaboxes',
    );

    // REGISTRA O CPT
    register_post_type('campanhas_ab', $args);
}

/* Hook no 'init' action para executar a função
 */

add_action('init', 'abtestx_custom_post_type', 0);

/**
 * Definição dos metaboxes
 */
function abtestx_add_metaboxes() {

    add_meta_box(
        'abtestx_data',
        __('Dados da Campanha', 'abtestx'),
        'abtestx_data_handler',
        'campanhas_ab',
        'normal',
        'default'
    );
}
/**
 * HTML DA METABOX DA OS
 */
function abtestx_data_handler() {
    global $post;
    // recupera o usuário atual
    $current_user = wp_get_current_user();
    // mostra os campos

    echo '<fieldset class="campos_os">

    <label for="link_principal"><b>' . __('Link principal', 'abtestx') . '</b></label><br>
    <span class="description">' .
    __('Este é o link da campanha, quando um usuário acessar uma página com este link, ele será redirecionado para uma das <b>páginas da campanha</b>. Você não precisa criar esta página, basta informar o link desejado para o redirecionamento', 'abtestx') .
    '</span><br>' . get_site_url() . '/
    <input type="text" class="single-line" name="link_principal" value="' . get_post_meta($post->ID, 'link_principal', true) . '">
    <br><br>



    <label for="paginas_campanha"><b>' . __('Páginas da campanha', 'abtestx') . '</b></label><br>
    <span class="description">' .
    __('Estas são as páginas para onde redirecionaremos nossos usuários.', 'abtestx') .
        '</span>
    <select class="paginas_campanha" id="paginas_campanha" name="paginas_campanha[]" multiple="multiple" style="width:100%">';
    $pages = get_pages(array(
        'sort_order' => 'ASC',
        'sort_column' => 'post_title',
    ));

    $saved_pages = get_post_meta($post->ID, 'paginas_campanha', false);

    if ($pages) {
        foreach ($pages as $page) {
            $selected = (in_array($page->ID, $saved_pages))
            ? 'selected'
            : '';

            printf(
                '<option %1$s value="%2$s">%3$s</option>',
                $selected,
                $page->ID,
                $page->post_title
            );
        }
    }
    echo '</select><br><br>

    <label for="pagina_obrigado"><b>' . __('Página de obrigado', 'abtestx') . '</b></label><br>
    <span class="description">' .
    __('Esta é a página onde o usuário é direcionado após a compra. Nesta página será verificado se existe um cookie de conversão disponível.<br> <b> Não use a mesma página de obrigado para várias campanhas</b>', 'abtestx') .
        '</span>
    <select class="pagina_obrigado" id="pagina_obrigado" name="pagina_obrigado" style="width:100%">';
    $pages = get_pages(array(
        'sort_order' => 'ASC',
        'sort_column' => 'post_title',
    ));

    $pag_obrigado = get_post_meta($post->ID, 'pagina_obrigado', false);

    if ($pages) {
        foreach ($pages as $page) {
            $selected = (in_array($page->ID, $pag_obrigado))
            ? 'selected'
            : '';

            printf(
                '<option %1$s value="%2$s">%3$s</option>',
                $selected,
                $page->ID,
                $page->post_title
            );
        }
    }
    echo '</select><br><br>


    <label for="link_principal"><b>' . __('Parametros Padrão', 'abtestx') . '</b></label><br>
    <span class="description">' .
    __('Caso nosso usuário acesse o link diretamente estes são os parametros que passaremos adiante, os parametros podem ser qualquer coisa no padrão key=value, combinados por `&`', 'abtestx') .
    '</span><br>
    <input type="text" class="single-line" name="parametros_padrao" placeholder="param=value&param2=value2..." value="' . get_post_meta($post->ID, 'parametros_padrao', true) . '">
    <br>
    </fieldset>';
}

/**
 * SALVA OS DADOS
 */
function save_campanha_data($post_id, $post) {

    global $wpdb, $post;
    $ab_test_meta = [];
    // Cancela a edição se o usuário não tier a permissão correta
    if (!current_user_can('edit_post', $post_id)) {
        return $post_id;
    }

    // VERIFICANDO SE O NONCE EXISTE ANTES DE POSTAR
    if (isset($_POST['woo_os__dados'])) {
        if (isset($ab_test_meta['woo_os_user_email']) || !wp_verify_nonce($_POST['woo_os__dados'], basename(__FILE__))) {
            return $post_id;
        }
    }

    // SATINIZANDO OS CAMPOS PQ NÂO DA PRA CONFIAR NO USUARIO
    if (isset($_POST['link_principal'])) {
        $ab_test_meta['link_principal'] = sanitize_text_field($_POST['link_principal']);
    }
    // SATINIZANDO OS CAMPOS PQ NÂO DA PRA CONFIAR NO USUARIO
    if (isset($_POST['parametros_padrao'])) {
        $ab_test_meta['parametros_padrao'] = sanitize_text_field($_POST['parametros_padrao']);
    }

    if (isset($_POST['paginas_campanha'])) {
        $array_paginas_meta = $_POST['paginas_campanha'];
    }

    if (isset($array_paginas_meta)) {
        delete_post_meta($post_id, 'paginas_campanha');
        foreach ($array_paginas_meta as $value) {
            add_post_meta($post_id, 'paginas_campanha', $value);
        }
    }

    if (isset($_POST['pagina_obrigado'])) {
        $ab_test_meta['pagina_obrigado'] = sanitize_text_field($_POST['pagina_obrigado']);

        $ty_post = get_post($ab_test_meta['pagina_obrigado']);
        $ab_test_meta['ty_slug'] = $ty_post->post_name;

    }

    // META - FAZ O TRABALHO DURO DE PUBLICAR O CONTEUDO
    foreach ($ab_test_meta as $key => $value) {

        // Don't store custom data twice
        if ('revision' === $post->post_type) {
            return;
        }

        if (get_post_meta($post_id, $key, false) === false) {
            //Se a variável não estiver corretamente definida
            return;
        }

        if (get_post_meta($post_id, $key, false)) {
            //SE JA TIVER PREENCHIDO ATUALIZA
            update_post_meta($post_id, $key, $value);
        } else {
            // SE ESTIVER EM BRANCO CRIA
            add_post_meta($post_id, $key, $value);
        }
        if (!$value) {
            // SE O CAMPO ESTIVER EM BRANCO APAGA
            delete_post_meta($post_id, $key);
        }
    }
}
add_action('save_post', 'save_campanha_data', 1, 2);

// Novos comandos para o post customizado
add_filter('manage_campanhas_ab_posts_columns', 'set_custom_edit_ab_test');
function set_custom_edit_ab_test($columns) {
    $columns['comandos'] = __('Comandos', 'ab_test_x');
    return $columns;
}

// Add the data to the custom columns for the book post type:
add_action('manage_campanhas_ab_posts_custom_column', 'custom_ab_test_column', 10, 2);
function custom_ab_test_column($column, $post_id) {
    if ($column === 'comandos') {

        _e('<button type="button" value=' . $post_id . ' class="zerador button blue"><span class="dashicons dashicons-trash"></span>Zerar relatórios</button>', 'ab_test_x');
        if  (WP_DEBUG === 'true')  {
            echo '<hr>';
            _e('<button type="button" value=' . $post_id . ' class="corretor button blue"><span class="dashicons dashicons-admin-tools"></span></span>Corrigir URLs</button>', 'ab_test_x');

        }
    }

}

add_action('delete_post', 'delete_report_database_data', 10);

function delete_report_database_data($post_id) {
    global $wpdb;
    if (get_post_type($post_id) === 'campanhas_ab') {

        $table_name = $wpdb->prefix . 'ab_test_data';
        $query_data = $wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d", $post_id);

        $any_data = $wpdb->get_var($query_data);

        if ($any_data) {
            $query_remove = $wpdb->prepare("DELETE FROM $table_name WHERE post_id = %d", $post_id);
            $wpdb->query($query_remove);
        }
    }

}