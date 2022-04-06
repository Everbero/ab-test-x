<?php
defined( 'ABSPATH' ) || exit;
/*
* Funcão para criar e registrar o novo tipo de post
*/

function abtestx_custom_post_type() {

// TODOS OS RÓTULOS DO PAINEL ADMIN
    $labels = array(
        'name'                => __( 'Campanhas A/B', 'Post Type General Name', 'twentytwenty' ),
        'singular_name'       => __( 'Campanha A/B', 'Post Type Singular Name', 'twentytwenty' ),
        'menu_name'           => __( 'Campanhas A/B', 'twentytwenty' ),
        'parent_item_colon'   => __( 'Campanha pai', 'twentytwenty' ),
        'all_items'           => __( 'Todas as campanhas', 'twentytwenty' ),
        'view_item'           => __( 'Ver campanha', 'twentytwenty' ),
        'add_new_item'        => __( 'Criar campanha', 'twentytwenty' ),
        'add_new'             => __( 'Criar nova', 'twentytwenty' ),
        'edit_item'           => __( 'Editar campanha', 'twentytwenty' ),
        'update_item'         => __( 'Atualizar campanha', 'twentytwenty' ),
        'search_items'        => __( 'Pesquisar campanhas', 'twentytwenty' ),
        'not_found'           => __( 'Campanha não encontrada', 'twentytwenty' ),
        'not_found_in_trash'  => __( 'Nenhuma campanha encontrada no lixo', 'twentytwenty' ),
    );

// OUTRAS OPCOES DO CPT

    $args = array(
        'label'               => __( 'campanha', 'twentytwenty' ),
        'description'         => __( 'Campanhas de teste AB', 'twentytwenty' ),
        'labels'              => $labels,
        // SUPORTE
        'supports'            => array( 'title' ),
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-welcome-view-site',
        'can_export'          => true,
        'has_archive'         => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'capability_type'     => 'post',
        'show_in_rest' => true,
        // metaboxes
        'register_meta_box_cb' => 'woo_os_add_metaboxes',
    );

    // REGISTRA O CPT
    register_post_type( 'campanhas_ab', $args );

}

/* Hook no 'init' action para executar a função
*/

add_action( 'init', 'abtestx_custom_post_type', 0 );

/**
 * Definição dos metaboxes
 */
function woo_os_add_metaboxes() {

    add_meta_box(
        'woo_os_data',
        'Dados da OS',
        'woo_os_os_data',
        'campanhas_ab',
        'normal',
        'default'
    );

}
/**
 * HTML DA METABOX DA OS
 */
function woo_os_os_data() {
    global $post;
    // recupera o usuário atual
    $current_user = wp_get_current_user();
    // mostra os campos

    
    echo '<fieldset class="campos_os">
    
    <label for="link_principal"><b>Link principal</b></label><br>
    <span class="description">Este é o link da campanha, quando um usuário acessar uma página com este link, ele será redirecionado para uma das <b>páginas da campanha</b>. Você não precisa criar esta página, basta informar o link desejado para o redirecionamento:</span><br>'.get_site_url().'
    <input type="text" class="single-line" name="link_principal" value="'  . get_post_meta($post->ID, 'link_principal', true)  . '">
    <br><br>

    

    <label for="paginas_campanha"><b>Páginas na campanha</b></label><br>
    <span class="description">Estas são as páginas para onde redirecionaremos nossos usuários.</span>
    <select class="paginas_campanha" id="paginas_campanha" name="paginas_campanha[]" multiple="multiple" style="width:100%">';
    $pages = get_pages(array(
      'sort_order' => 'ASC',
      'sort_column'  => 'post_title',
  ));

    $saved_pages = get_post_meta($post->ID, 'paginas_campanha', false);

    if ( $pages ) {
        foreach( $pages as $page ) {                       
            $selected = (in_array($page->ID, $saved_pages))
            ?'selected'
            :'';

            printf('<option %1$s value="%2$s">%3$s</option>',
                $selected,
                $page->ID,
                $page->post_title
            );
        }
    }
    echo  '</select><br><br>


    <label for="link_principal"><b>Parametros Padrão</b></label><br>
    <span class="description">Caso nosso usuário acesse o link diretamente estes são os parametros que passaremos adiante, os parametros podem ser qualquer coisa no padrão key=value, combinados por `&`</span><br>
    <input type="text" class="single-line" name="parametros_padrao" value="'  . get_post_meta($post->ID, 'parametros_padrao', true)  . '">
    <br>
    </fieldset>';

}


/**
 * SALVA OS DADOS
 */
function save_campanha_data( $post_id, $post ) {

    global $wpdb, $post;
    $woo_os_meta = [];
    // Cancela a edição se o usuário não tier a permissão correta
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return $post_id;
    }

    // VERIFICANDO SE O NONCE EXISTE ANTES DE POSTAR
    if(isset($_POST['woo_os__dados'])){
        if (isset($woo_os_meta['woo_os_user_email']) || !wp_verify_nonce( $_POST['woo_os__dados'], basename(__FILE__) ) ) {
            return $post_id;
        }
    }

     // SATINIZANDO OS CAMPOS PQ NÂO DA PRA CONFIAR NO USUARIO 
    if(isset($_POST['link_principal'])){
        $woo_os_meta['link_principal'] = sanitize_text_field( $_POST['link_principal']);
    }
     // SATINIZANDO OS CAMPOS PQ NÂO DA PRA CONFIAR NO USUARIO 
    if(isset($_POST['parametros_padrao'])){
        $woo_os_meta['parametros_padrao'] = sanitize_text_field( $_POST['parametros_padrao']);
    }

    if(isset($_POST['paginas_campanha'])){
        $woo_os_product_meta_os_observacoes = $_POST['paginas_campanha'];
    }

    if(isset($woo_os_product_meta_os_observacoes)){
        delete_post_meta($post_id, 'paginas_campanha');
        foreach($woo_os_product_meta_os_observacoes as $value){
            add_post_meta( $post_id, 'paginas_campanha', $value);
        }
    }

    // META - FAZ O TRABALHO DURO DE PUBLICAR O CONTEUDO
    foreach ( $woo_os_meta as $key => $value ) {

        // Don't store custom data twice
        if ( 'revision' === $post->post_type ) {
            return;
        }

        if ( get_post_meta( $post_id, $key, false ) === false ) {
            //Se a variável não estiver corretamente definida
            return;
        }

        if ( get_post_meta( $post_id, $key, false ) ) {
            //SE JA TIVER PREENCHIDO ATUALIZA
            update_post_meta( $post_id, $key, $value );
        } else {
            // SE ESTIVER EM BRANCO CRIA
            add_post_meta( $post_id, $key, $value );
        }
        if ( ! $value ) {
            // SE APAGAR O CAMPO... APAGA
            delete_post_meta( $post_id, $key );
        }

    }

}
add_action( 'save_post', 'save_campanha_data', 1, 2 );

?>