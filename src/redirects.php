<?php
defined('ABSPATH') || exit;
//função para pegar o ip do usuário
function get_visitor_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        //to check ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        //to check ip is from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // match first IP address for possibility that the captured IP address string
    // has more than one IP due to two HTTP_X_FORWARDED_FOR layers of proxies
    $re = '/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/s';
    $matches = array();
    if (preg_match($re, $ip, $matches, PREG_OFFSET_CAPTURE, 0) === 1) {
        $ip = $matches[0][0];
    }
    return apply_filters('wpb_get_ip', $ip);
}

// função que verifica a existencia de um hash no DB
function find_report_hash($cookie_data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ab_test_data';
    $hash = $cookie_data['hash'];

    $result = $wpdb->get_results("
        SELECT *
        FROM  $table_name
            WHERE cookie_hash = '$hash'
    ");
    // error_log('query is '.json_encode($result));
    return $result;

}
// função para salvar os dados de relatórios
function save_report_data($cookie_data) {

    global $wpdb;
    $table_name = $wpdb->prefix . 'ab_test_data';

    //gravo os dados do cookie gerado no banco de dados
    if (!find_report_hash($cookie_data)) {

        $wpdb->insert(
            $table_name,
            array(
                'post_id' => $cookie_data['campaing'],
                'cookie_hash' => $cookie_data['hash'],
                'origin_ip' => $cookie_data['ip'],
                'creation_time' => current_time('mysql'),
                'page' => $cookie_data['page'],
                'params' => esc_html($cookie_data['params']),
                'destination' => $cookie_data['destination'],
            )
        );
    }
}
// função para atualizar os relatórios
function update_report_data($cookie_data) {

    global $wpdb;

    $table_name = $wpdb->prefix . 'ab_test_data';
    // verifico se já existe um registro para este hash
    if (!find_report_hash($cookie_data)) {
        // se não houver crio um registro apenas com o retorno
        $insert = $wpdb->insert(
            $table_name,
            array(
                'post_id' => $cookie_data['campaing'],
                'cookie_hash' => $cookie_data['hash'],
                'return_ip' => $cookie_data['ip'],
                'return_time' => current_time('mysql'),
            )
        );
    // mas se houver atualizo o registro com os dados do retorno
    } else {
        $wpdb->update(
            $table_name,
            array(
                'return_ip' => $cookie_data['return_ip'],
                'return_time' => current_time('mysql')),
            array('cookie_hash' => $cookie_data['hash'],
            )
        );
    }

}
// função que executa os redirecionamentos
function redirect_if_any() {

    if (is_admin()) {
        return;
    }

    // faço uma lista com todas as campanhas
    $campanhas = get_posts(['post_type' => 'campanhas_ab', 'fields' => 'ids']);
    // reservo a variável para os links de campanhas atuais
    $links_das_campanhas;
    // listo as campanhas por id da página e link da campanha
    foreach ($campanhas as $campanha) {
        $links_das_campanhas[] = [
            'ID' => $campanha,
            'link' => get_post_meta($campanha, 'link_principal', true),
            'ty_page' => get_post_meta($campanha, 'ty_slug', true),
        ];
    }
    //armazeno a página atual em uma variável
    $current_page = explode('?', str_replace('/', '', $_SERVER["REQUEST_URI"]))[0];

    //error_log("posts is: " . json_encode($campanhas) . " message is: " . json_encode($links_das_campanhas) . " redirect is: " . json_encode($_SERVER["REQUEST_URI"]) . " user ip is: " . get_visitor_ip());
    // começo a procurar se o url acessado está em alguma das campanhas
    foreach ($links_das_campanhas as $key => $value) {
        // se na url acessada estiver um dos links principais de qualquer campanha

        if ($current_page === $value['link']) {
            // debug, a qual página foi ligada
            // error_log('linked to '.$value['ID']."_".$value['link']);

            // pego os parametros do url pra repassar,
            // se estiverem vazios, pego a lista de parametros padrao da campanha
            $parametros = (!empty($_SERVER['QUERY_STRING']))
            ? $_SERVER['QUERY_STRING']
            : get_post_meta($value['ID'], 'parametros_padrao', true);
            // faz uma lista com as página da campanha desejada
            $paginas = get_post_meta($value['ID'], 'paginas_campanha', false);
            // sorteio um indice desta lista
            $indice_aleatorio = array_rand($paginas, 1);
            // pega o slug da página
            $versao = get_post_field('post_name', get_post($paginas[$indice_aleatorio]));
            // pego o URL da página que eu sorteei
            $page_link = get_page_uri($paginas[$indice_aleatorio]);
            

            //crio um cookie para este acesso
            $arr_cookie_options = array(
                'expires' => time() + 60 * 60 * 24 * 30,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'], // leading dot for compatibility or use subdomain
                'secure' => true, // or false
                'httponly' => true, // or false
                'samesite' => 'None', // None || Lax  || Strict
            );
            //pego o ip do usuário
            $user_ip = get_visitor_ip();
            $hash = md5($user_ip . current_time('mysql'));
            //preparo os dados para o cookie
            $cookie_data['campaing'] = $value['ID'];
            $cookie_data['hash'] = $hash;
            $cookie_data['ip'] = $user_ip;
            $cookie_data['date'] = current_time('mysql');
            $cookie_data['page'] = $value['link'];
            $cookie_data['params'] = $parametros;
            $cookie_data['destination'] = $page_link;
            $cookie_data['versao'] = $versao;

            //salvo o cookie no navegador
            setcookie('teste_ab', json_encode($cookie_data), $arr_cookie_options);
            save_report_data($cookie_data);
            // faço o redirecionamento repassando os parâmetros recebidos para a página sorteada
            header('Location: ' . $page_link . '/?' . $parametros . '&versao=' . $versao);

            // finaliza a execução
            exit();
        }
        // se na url acessada estiver uma das páginas de obrigado
        if ($current_page === $value['ty_page']) {
            // error_log('ty to '.$value['ID']."_".$value['link']);
            if (isset($_COOKIE["teste_ab"])) {

                // print_r($_COOKIE);
                $cookie_data = json_decode(stripslashes($_COOKIE["teste_ab"]), true);
                //pego o ip do usuário

                $cookie_data['return_ip'] = get_visitor_ip();
                $cookie_data['campaing'] = $value['ID'];

                update_report_data($cookie_data);
                $versao = $cookie_data['versao'];

                // apaga os cookies
                foreach ($_COOKIE as $key => $value) {
                    if ($key === 'teste_ab') {
                        unset($_COOKIE['teste_ab']);
                        setcookie('teste_ab', null, -1, '/', $_SERVER['HTTP_HOST']);
                    }
                }
                header('Location: ' . $_SERVER["REQUEST_URI"] ."/".$_SERVER['QUERY_STRING']. '/?' . $cookie_data['params'] . '&versao=' . $versao);
            } 
            // em caso de acessos diretos, por enquanto desativado
            // else {
            //     // defino um novo hash para este acesso
            //     $user_ip = get_visitor_ip();
            //     $cookie_data['hash'] = md5($user_ip);
            //     $cookie_data['ip'] = $user_ip;
            //     $cookie_data['return_ip'] = $user_ip;
            //     $cookie_data['campaing'] = $value['ID'];
            //     // solicito a atualização
            //     update_report_data($cookie_data);
            //     error_log(json_encode($cookie_data));   
            // }
        }

    }
}

add_action('template_redirect', 'redirect_if_any');