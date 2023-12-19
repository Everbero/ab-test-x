<?php
// Garante que o código não seja executado diretamente fora do WordPress
defined('ABSPATH') || exit;
/*
 * Funcão para criar e registrar o novo tipo de post
 */

/**
 * Registra um novo tipo de postagem personalizado para Campanhas A/B no WordPress.
 * Define rótulos e configurações para exibir e gerenciar as campanhas no painel administrativo.
 * Também adiciona suporte para metaboxes personalizados.
 */
function abtestx_custom_post_type()
{
    // Rótulos para as Campanhas A/B no painel administrativo
    $labels = array(
        // Rótulos gerais
        'name' => __('Campanhas A/B', 'abtestx'),
        'singular_name' => __('Campanha A/B', 'abtestx'),
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

    // Configurações para o tipo de postagem Campanhas A/B
    $args = array(
        'label' => __('campanha', 'abtestx'),
        'description' => __('Campanhas de teste AB', 'abtestx'),
        'labels' => $labels,
        // Recursos suportados
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
        // Função para registrar metaboxes personalizados
        'register_meta_box_cb' => 'abtestx_add_metaboxes',
    );

    // Registra o tipo de postagem personalizado para Campanhas A/B
    register_post_type('campanhas_ab', $args);
}

/**
 * Hook no 'init' action para executar a função
 */
add_action('init', 'abtestx_custom_post_type', 0);

/**
 * Função para definir metaboxes personalizados para o tipo de postagem 'campanhas_ab'.
 * Adiciona um metabox chamado 'abtestx_data' para lidar com os dados da campanha.
 */
function abtestx_add_metaboxes()
{
    // Adiciona um metabox para manipular os dados da Campanha A/B
    add_meta_box(
        'abtestx_data', // ID único do metabox
        __('Dados da Campanha', 'abtestx'), // Título do metabox
        'abtestx_data_handler', // Função para lidar com o conteúdo do metabox
        'campanhas_ab', // Tipo de postagem onde o metabox será exibido
        'normal', // Contexto onde o metabox será exibido ('normal', 'advanced' ou 'side')
        'default' // Prioridade do metabox dentro do contexto
    );
}

/**
 * Manipula a exibição dos campos de metabox para as Campanhas A/B.
 * Exibe campos como Link Principal, Páginas da Campanha, Página de Obrigado e Parâmetros Padrão.
 * Esses campos permitem configurar redirecionamentos, páginas específicas e parâmetros para as campanhas.
 */
function abtestx_data_handler()
{
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

    // Obtém as páginas associadas à campanha salva para pré-seleção no menu suspenso
    $saved_pages = get_post_meta($post->ID, 'paginas_campanha', false);

    // Verifica se existem páginas disponíveis para associar
    if ($pages) {
        // Loop por cada página disponível
        foreach ($pages as $page) {
            // Verifica se a página atual está associada à campanha (selecionada)
            $selected = (in_array($page->ID, $saved_pages))
                ? 'selected'
                : '';

            // Exibe a opção da página no menu suspenso, marcando-a como selecionada, se aplicável
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
    // Obtém todas as páginas disponíveis ordenadas por título para o menu suspenso
    $pages = get_pages(array(
        'sort_order' => 'ASC',
        'sort_column' => 'post_title',
    ));

    // Obtém a página de obrigado associada à campanha atual
    $pag_obrigado = get_post_meta($post->ID, 'pagina_obrigado', false);

    // Verifica se há páginas disponíveis para mostrar no menu suspenso
    if ($pages) {
        // Loop por cada página disponível
        foreach ($pages as $page) {
            // Verifica se a página atual está associada à campanha como página de obrigado (selecionada)
            $selected = (in_array($page->ID, $pag_obrigado))
                ? 'selected'
                : '';

            // Exibe a opção da página no menu suspenso, marcando-a como selecionada, se aplicável
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
 * Salva os dados da campanha A/B
 */
function save_campanha_data($post_id, $post)
{
    global $wpdb, $post;
    $ab_test_meta = [];

    // Cancela a edição se o usuário não tiver a permissão correta
    if (!current_user_can('edit_post', $post_id)) {
        return $post_id;
    }

    // Verifica se o nonce existe antes de postar
    if (isset($_POST['woo_os__dados'])) {
        if (isset($ab_test_meta['woo_os_user_email']) || !wp_verify_nonce($_POST['woo_os__dados'], basename(__FILE__))) {
            return $post_id;
        }
    }

    // Sanitiza e salva os campos
    if (isset($_POST['link_principal'])) {
        $ab_test_meta['link_principal'] = sanitize_text_field($_POST['link_principal']);
    }
    if (isset($_POST['parametros_padrao'])) {
        $ab_test_meta['parametros_padrao'] = sanitize_text_field($_POST['parametros_padrao']);
    }

    // Salva as páginas associadas à campanha
    if (isset($_POST['paginas_campanha'])) {
        $array_paginas_meta = $_POST['paginas_campanha'];
    }

    if (isset($array_paginas_meta)) {
        delete_post_meta($post_id, 'paginas_campanha');
        foreach ($array_paginas_meta as $value) {
            add_post_meta($post_id, 'paginas_campanha', $value);
        }
    }

    // Salva a página de obrigado associada à campanha
    if (isset($_POST['pagina_obrigado'])) {
        $ab_test_meta['pagina_obrigado'] = sanitize_text_field($_POST['pagina_obrigado']);

        // Obtém o slug da página de obrigado
        $ty_post = get_post($ab_test_meta['pagina_obrigado']);
        $ab_test_meta['ty_slug'] = $ty_post->post_name;
    }

    // Atualiza os metadados da campanha A/B
    foreach ($ab_test_meta as $key => $value) {
        if ('revision' === $post->post_type) {
            return;
        }

        if (get_post_meta($post_id, $key, false) === false) {
            return;
        }

        if (get_post_meta($post_id, $key, false)) {
            update_post_meta($post_id, $key, $value);
        } else {
            add_post_meta($post_id, $key, $value);
        }
        if (!$value) {
            delete_post_meta($post_id, $key);
        }
    }
}
add_action('save_post', 'save_campanha_data', 1, 2);


// Adiciona uma nova coluna na tela de edição do post customizado
add_filter('manage_campanhas_ab_posts_columns', 'set_custom_edit_ab_test');
function set_custom_edit_ab_test($columns)
{
    // Adiciona uma nova coluna chamada 'comandos' à lista de colunas
    $columns['comandos'] = __('Comandos', 'ab_test_x');
    return $columns;
}


// Adiciona dados às colunas personalizadas para o tipo de post 'campanhas_ab':
add_action('manage_campanhas_ab_posts_custom_column', 'custom_ab_test_column', 10, 2);
function custom_ab_test_column($column, $post_id)
{
    // Verifica se a coluna é 'comandos'
    if ($column === 'comandos') {
        // Adiciona um botão para zerar os relatórios do post
        _e('<button type="button" value=' . $post_id . ' class="zerador button blue"><span class="dashicons dashicons-trash"></span>Zerar relatórios</button>', 'ab_test_x');

        // Verifica se o modo de depuração está ativado
        if (WP_DEBUG === 'true') {
            // Adiciona um botão para corrigir URLs quando o modo de depuração está ativado
            echo '<hr>';
            _e('<button type="button" value=' . $post_id . ' class="corretor button blue"><span class="dashicons dashicons-admin-tools"></span>Corrigir URLs</button>', 'ab_test_x');
        }
    }
}


// Adiciona uma ação quando um post é excluído
add_action('delete_post', 'delete_report_database_data', 10);

function delete_report_database_data($post_id)
{
    global $wpdb;

    // Verifica se o post excluído é do tipo 'campanhas_ab'
    if (get_post_type($post_id) === 'campanhas_ab') {

        // Obtém o nome da tabela usando o prefixo do WordPress
        $table_name = $wpdb->prefix . 'ab_test_data';

        // Prepara a consulta para selecionar dados relacionados ao post a ser excluído
        $query_data = $wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d", $post_id);

        // Obtém qualquer dado existente na tabela relacionada a este post
        $any_data = $wpdb->get_var($query_data);

        // Se houver dados associados a este post na tabela
        if ($any_data) {
            // Prepara a consulta para remover os dados relacionados ao post
            $query_remove = $wpdb->prepare("DELETE FROM $table_name WHERE post_id = %d", $post_id);
            
            // Executa a consulta para remover os dados
            $wpdb->query($query_remove);
        }
    }
}