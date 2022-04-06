<?php

function redirect_if_any(){

    if (is_admin()) return;
    
    $campanhas = get_posts(['post_type' => 'campanhas_ab']);

    foreach ($campanhas as $key => $campanha) {
        $links_das_campanhas[] = [
            'ID' => $campanha->ID,
            'link' => '/'.get_post_meta($campanha->ID, 'link_principal', true)
        ];
    }

    foreach ($links_das_campanhas as $key => $value){
        if(isset($_SERVER["REDIRECT_URL"]) && ($_SERVER["REDIRECT_URL"] === $value['link'])){
            
            // debug, a qual página foi ligada
            // error_log('linked to'.$value['ID']."_".$value['link']);

            // se os parametros estiverem vazios, pego a lista de parametros padrao do post
            $parametros = ( !empty($_SERVER['QUERY_STRING']) ) 
            ? $_SERVER['QUERY_STRING']  
            : get_post_meta($value['ID'], 'parametros_padrao', true);

            // faz uma lista com as página da campanha
            $paginas = get_post_meta($value['ID'], 'paginas_campanha', false);
            // sorteio um indice desta lista
            $indice_aleatorio = array_rand($paginas, 1);

            // pego o URL da página que eu sorteei
            $page_link = get_page_link($paginas[$indice_aleatorio]);
            
            // faço o redirecionamento repassando os parâmetros recebidos para a página sorteada
            header('Location: '.$page_link.'/?'.$parametros);
            
            // finaliza a execução
            exit();
        }
    }
}

add_action('init','redirect_if_any');
