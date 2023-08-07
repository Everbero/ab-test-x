<?php
defined('ABSPATH') || exit;
// Enter your code here, enjoy!
/**
 * Check if a given ip is in a network
 * @param  string $ip    IP to check in IPV4 format eg. 127.0.0.1
 * @param  string $range IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed
 * @return boolean true if the ip is in this range / false if not.
 */
function ip_in_range($ip, $range) {
    if (strpos($range, '/') == false) {
        $range .= '/32';
    }
    // $range is in IP/CIDR format eg 127.0.0.1/24
    list($range, $netmask) = explode('/', $range, 2);
    $range_decimal = ip2long($range);
    $ip_decimal = ip2long($ip);
    $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
    $netmask_decimal = ~$wildcard_decimal;
    return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
}

function serial_ip_verification($ip_address, $fb_ips, $google_ips, $cloudfare_ips) {
    foreach ($fb_ips as $ips) {
        if (ip_in_range($ip_address, $ips)) {
            return true;
        }
    }
    foreach ($google_ips as $ips) {
        if (ip_in_range($ip_address, $ips)) {
            return true;
        }
    }
    foreach ($cloudfare_ips as $ips) {
        if (ip_in_range($ip_address, $ips)) {
            return true;
        }
    }
    return false;
}

//função para pegar o ip do usuário
function get_visitor_ip() {
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
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
function generate_guidv4($data = null) {
    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = $data ?? random_bytes(16);
    assert(strlen($data) == 16);

    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
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
    //apenas se já não existir este registro
    if (!find_report_hash($cookie_data)) {

        $wpdb->insert(
            $table_name,
            array(
                'post_id' => $cookie_data['campaing'],
                'cookie_hash' => $cookie_data['hash'],
                'origin_ip' => $cookie_data['ip'],
                'creation_time' => current_time('mysql'),
                'page_is' => $cookie_data['page_is'],
                'params' => urldecode(esc_html($cookie_data['params'])),
                'destination' => $cookie_data['destination'],
                'referer' => $cookie_data['referer'],
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
        // se não exisitir salvo antes de atualizar com a conversão.
        save_report_data($cookie_data);
    }

    $wpdb->update(
        $table_name,
        array(
            'return_ip' => $cookie_data['return_ip'],
            'return_time' => current_time('mysql')),
        array('cookie_hash' => $cookie_data['hash'],
        )
    );

}
// testa a versão do ip
// retorna true para ipv4 e false para ipv6
function check_ip_version($user_ip) {
    //Check for IPv4
    if (filter_var($user_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        // error_log('found ipv4');
        return true;
    } 
    //Check for IPv6
    if (filter_var($user_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // error_log('found ipv6');
        return false;
    }
}
// função que executa os redirecionamentos
function redirect_if_any() {

    if (is_admin()) {
        return;
    }

    // faço uma lista com todas as campanhas
    $campanhas = get_posts(['post_type' => 'campanhas_ab', 'fields' => 'ids', 'numberposts' => -1,]);
    // reservo a variável para os links de campanhas atuais
    $links_das_campanhas = [];
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

    // error_log("posts is: " . json_encode($campanhas) . " message is: " . json_encode($links_das_campanhas) . " redirect is: " . json_encode($_SERVER["REQUEST_URI"]) . " user ip is: " . get_visitor_ip());
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
            // se o ip do usuário não estiver em uma lista favorecida,
            //criamos o cookie e salvamos os dados, caso contrário apenas executa o teste
            
            
            if(check_ip_version($user_ip)){
                $ip_forbiden = (new subnetInterpreter)->serial_ipv4_verification($user_ip);
            }else{
                $ip_forbiden = (new subnetInterpreter)->serial_ipv6_verification($user_ip);
            }
            
            if (!$ip_forbiden) {

                $hash = generate_guidv4();
                //preparo os dados para o cookie
                $cookie_data['campaing'] = $value['ID'];
                $cookie_data['hash'] = $hash;
                $cookie_data['ip'] = $user_ip;
                $cookie_data['date'] = current_time('mysql');
                $cookie_data['page_is'] = $value['link'];
                $cookie_data['params'] = $parametros;
                $cookie_data['destination'] = $page_link;
                $cookie_data['versao'] = $versao;
                $cookie_data['referer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'no_ref';
                //salvo o cookie no navegador
                setcookie('teste_ab', json_encode($cookie_data), $arr_cookie_options);
                save_report_data($cookie_data);
            }

            // faço o redirecionamento repassando os parâmetros recebidos para a página sorteada
            header('Location: ' . $page_link . '/?' . $parametros . '&versao=' . $versao);

            // finaliza a execução
            exit();
        }
        // se na url acessada estiver uma das páginas de obrigado
        if ($current_page === $value['ty_page']) {
            // error_log('ty to '.$value['ID']."_".$value['link']);

            $user_ip = get_visitor_ip();
            
            
            if(check_ip_version($user_ip)){
                $ip_forbiden = (new subnetInterpreter)->serial_ipv4_verification($user_ip);
            }else{
                $ip_forbiden = (new subnetInterpreter)->serial_ipv6_verification($user_ip);
            }
            
            // se existir o cookie e o ip não for proibido
            
            if (isset($_COOKIE["teste_ab"]) && !$ip_forbiden) {

                // print_r($_COOKIE);
                $cookie_data = json_decode(stripslashes($_COOKIE["teste_ab"]), true);
                //pego o ip do usuário

                $cookie_data['return_ip'] = $user_ip;
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
                header('Location: ' . $_SERVER["REQUEST_URI"] . "/" . $_SERVER['QUERY_STRING'] . '/?' . $cookie_data['params'] . '&versao=' . $versao);
            }
        }

    }
}

add_action('template_redirect', 'redirect_if_any');