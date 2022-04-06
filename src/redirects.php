<?php

function redirect_if_any(){

    if (is_admin()) return;
    
    // faço uma lista com todas as campanhas
    $campanhas = get_posts(['post_type' => 'campanhas_ab']);

    // listo as campanhas por id da página e link da campanha
    foreach ($campanhas as $key => $campanha) {
        $links_das_campanhas[] = [
            'ID' => $campanha->ID,
            'link' => '/'.get_post_meta($campanha->ID, 'link_principal', true)
        ];
    }
    // começo a procurar se o url acessado está em alguma das campanhas
    foreach ($links_das_campanhas as $key => $value){
        // se na url acessada estiver um dos links principais de qualquer campanha
        if(isset($_SERVER["REDIRECT_URL"]) && ($_SERVER["REDIRECT_URL"] === $value['link'])){
            // debug, a qual página foi ligada
            // error_log('linked to'.$value['ID']."_".$value['link']);

            // pego os parametros do url pra repassar,
            // se estiverem vazios, pego a lista de parametros padrao da campanha
            $parametros = ( !empty($_SERVER['QUERY_STRING']) ) 
            ? $_SERVER['QUERY_STRING']  
            : get_post_meta($value['ID'], 'parametros_padrao', true);
            // faz uma lista com as página da campanha desejada
            $paginas = get_post_meta($value['ID'], 'paginas_campanha', false);
            // sorteio um indice desta lista
            $indice_aleatorio = array_rand($paginas, 1);
            // pega o slug da página
            $versao =  get_post_field( 'post_name', get_post($paginas[$indice_aleatorio]) );
            // pego o URL da página que eu sorteei
            $page_link = get_page_link($paginas[$indice_aleatorio]);            
            // faço o redirecionamento repassando os parâmetros recebidos para a página sorteada
            header('Location: '.$page_link.'/?'.$parametros.'&versao='.$versao);

            // finaliza a execução
            exit();
        }
    }
}

add_action('init','redirect_if_any');
