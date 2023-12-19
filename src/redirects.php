<?php
// Garante que o código não seja executado diretamente fora do WordPress
defined('ABSPATH') || exit;
/**
 * Verifica se um endereço IP está dentro de um determinado intervalo CIDR.
 *
 * @param string $ip O endereço IP a ser verificado.
 * @param string $range O intervalo CIDR no formato IP/CIDR (eg. 127.0.0.1/24).
 * @return bool Retorna true se o endereço IP estiver dentro do intervalo especificado, caso contrário, retorna false.
 */
function ip_in_range($ip, $range)
{
    // Adiciona a máscara padrão se não estiver presente no intervalo
    if (strpos($range, '/') == false) {
        $range .= '/32';
    }
    // $range is in IP/CIDR format eg 127.0.0.1/24
    // Divide o intervalo em endereço IP e máscara CIDR
    list($range, $netmask) = explode('/', $range, 2);

    // Converte o endereço IP e o intervalo para valores decimais
    $range_decimal = ip2long($range);
    $ip_decimal = ip2long($ip);

    // Calcula a máscara de wildcard e a máscara de rede
    $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
    $netmask_decimal = ~$wildcard_decimal;

    // Verifica se o endereço IP está dentro do intervalo usando operadores de bits
    return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
}

/**
 * Verifica se um endereço IP está dentro dos intervalos de IPs do Facebook, Google ou Cloudflare.
 *
 * @param string $ip_address O endereço IP a ser verificado.
 * @param array $fb_ips Um array contendo intervalos de IPs associados ao Facebook.
 * @param array $google_ips Um array contendo intervalos de IPs associados ao Google.
 * @param array $cloudfare_ips Um array contendo intervalos de IPs associados ao Cloudflare.
 *
 * @return bool Retorna true se o endereço IP estiver dentro de um dos intervalos especificados, caso contrário, retorna false.
 */
function serial_ip_verification($ip_address, $fb_ips, $google_ips, $cloudfare_ips)
{
    // Itera pelos intervalos de IPs do Facebook
    foreach ($fb_ips as $ips) {
        // Verifica se o endereço IP está dentro do intervalo atual
        if (ip_in_range($ip_address, $ips)) {
            // Retorna true se o IP estiver no intervalo do Facebook
            return true;
        }
    }
    // Itera pelos intervalos de IPs do Google
    foreach ($google_ips as $ips) {
        // Verifica se o endereço IP está dentro do intervalo atual
        if (ip_in_range($ip_address, $ips)) {
            // Retorna true se o IP estiver no intervalo do Google
            return true;
        }
    }
    // Itera pelos intervalos de IPs do Cloudflare
    foreach ($cloudfare_ips as $ips) {
        // Verifica se o endereço IP está dentro do intervalo atual
        if (ip_in_range($ip_address, $ips)) {
            return true;
        }
    }
    // Retorna false se o IP não estiver em nenhum dos intervalos especificados
    return false;
}

/**
 * Obtém o endereço IP do visitante.
 *
 * A função verifica várias fontes possíveis para encontrar o endereço IP do visitante,
 * incluindo proxies e cabeçalhos HTTP especiais.
 *
 * @return string O endereço IP do visitante.
 */
function get_visitor_ip()
{
    // Verifica se o IP está presente no cabeçalho HTTP_CF_CONNECTING_IP (Cloudflare)
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    // Verifica se o IP está presente no cabeçalho HTTP_CLIENT_IP
    elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        // Para verificar se o IP é proveniente de uma conexão compartilhada à internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Verifica se o IP está presente no cabeçalho HTTP_X_FORWARDED_FOR (Proxy)
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Para verificar se o IP é proveniente de um proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        // Se nenhum dos cabeçalhos anteriores estiver presente, usa o endereço IP remoto padrão
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // A regex para extrair o primeiro IP de uma possível lista de IPs
    $re = '/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/s';
    $matches = array();

    // Verifica se há um padrão de IP na string e captura o primeiro IP
    if (preg_match($re, $ip, $matches, PREG_OFFSET_CAPTURE, 0) === 1) {
        $ip = $matches[0][0];
    }
    // Aplica filtros sobre o IP capturado antes de retorná-lo
    return apply_filters('wpb_get_ip', $ip);
}

/**
 * Gera um identificador único universal (UUID) versão 4.
 *
 * @param string|null $data Dados para gerar o UUID, se não fornecido, gera dados aleatórios.
 * @return string Um UUID versão 4 no formato de string.
 */
function generate_guidv4($data = null)
{
    // Gera 16 bytes (128 bits) de dados aleatórios ou utiliza os dados passados para a função.
    $data = $data ?? random_bytes(16);
    assert(strlen($data) == 16);

    // Define a versão para 0100 no quarto byte.
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Define os bits 6-7 para 10 no nono byte.
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Formata e retorna o UUID como uma string de 36 caracteres.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Procura um registro no banco de dados associado a um determinado hash de cookie.
 *
 * @param array $cookie_data Os dados do cookie contendo o hash a ser procurado.
 * @return array|null Retorna os resultados da busca no banco de dados ou null se não houver correspondência.
 */
function find_report_hash($cookie_data)
{
    global $wpdb;
    // Obtém o nome da tabela do banco de dados usando o prefixo do WordPress
    $table_name = $wpdb->prefix . 'ab_test_data';
    // Extrai o hash do cookie fornecido
    $hash = $cookie_data['hash'];
    // Executa uma consulta SQL para procurar registros com o hash fornecido na tabela
    $result = $wpdb->get_results("
        SELECT *
        FROM  $table_name
            WHERE cookie_hash = '$hash'
    ");
    // error_log('query is '.json_encode($result));
    // Retorna os resultados da busca no banco de dados
    return $result;

}
/**
 * Salva os dados do relatório no banco de dados, se o registro ainda não existir.
 *
 * @param array $cookie_data Os dados do cookie a serem salvos no banco de dados.
 * @return void
 */
function save_report_data($cookie_data)
{

    global $wpdb;
    $table_name = $wpdb->prefix . 'ab_test_data';

    // Verifica se o registro já existe no banco de dados com base no hash do cookie
    if (!find_report_hash($cookie_data)) {
        // Insere os dados do cookie no banco de dados se o registro não existir
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
/**
 * Atualiza os dados do relatório no banco de dados com informações de retorno, se o registro existir.
 * Caso contrário, salva os dados antes de atualizar.
 *
 * @param array $cookie_data Os dados do cookie a serem atualizados no banco de dados.
 * @return void
 */
function update_report_data($cookie_data)
{

    global $wpdb;

    $table_name = $wpdb->prefix . 'ab_test_data';
    // Verifica se o registro já existe com base no hash do cookie
    if (!find_report_hash($cookie_data)) {
        // Se o registro não existir, salva antes de atualizar com informações de retorno
        save_report_data($cookie_data);
    }

    // Atualiza os dados de retorno no banco de dados para o registro existente
    $wpdb->update(
        $table_name,
        array(
            'return_ip' => $cookie_data['return_ip'],
            'return_time' => current_time('mysql')),
        array('cookie_hash' => $cookie_data['hash'],
        )
    );

}
/**
 * Verifica a versão do endereço IP fornecido (IPv4 ou IPv6).
 *
 * @param string $user_ip O endereço IP a ser verificado.
 * @return bool|null Retorna true se for um endereço IPv4, false se for IPv6 ou null se não for nenhum dos dois.
 */
function check_ip_version($user_ip)
{
    // Verifica se é um endereço IPv4
    if (filter_var($user_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        // Se for IPv4, retorna true
        // error_log('found ipv4'); // (possível registro de log para fins de depuração)
        return true;
    }
    // Verifica se é um endereço IPv6
    if (filter_var($user_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // Se for IPv6, retorna false
        // error_log('found ipv6'); // (possível registro de log para fins de depuração)
        return false;
    }

    // Se não for nem IPv4 nem IPv6, retorna null
    return null;
}

/**
 * Redireciona usuários com base em campanhas e páginas específicas, armazenando dados em cookies e no banco de dados.
 *
 * Este código verifica se o usuário está em uma página correspondente a uma campanha ou a uma página de obrigado.
 * Se estiver em uma página de campanha, cria um cookie e salva os dados no banco de dados antes de redirecionar.
 * Se estiver em uma página de obrigado e existir o cookie correspondente, atualiza os dados no banco de dados antes de redirecionar.
 * Caso contrário, não executa nenhuma ação.
 */
function redirect_if_any()
{
    // Verifica se o usuário está na área administrativa do WordPress e interrompe a execução
    if (is_admin()) {
        return;
    }

    // Obtém todas as campanhas cadastradas no WordPress
    $campanhas = get_posts(['post_type' => 'campanhas_ab', 'fields' => 'ids', 'numberposts' => -1,]);
    // Inicializa uma lista vazia para os links das campanhas
    $links_das_campanhas = [];
    // Monta uma lista com as campanhas e seus links associados
    foreach ($campanhas as $campanha) {
        $links_das_campanhas[] = [
            'ID' => $campanha,
            'link' => get_post_meta($campanha, 'link_principal', true),
            'ty_page' => get_post_meta($campanha, 'ty_slug', true),
        ];
    }
    // Obtém a página atual da URL
    $current_page = explode('?', str_replace('/', '', $_SERVER["REQUEST_URI"]))[0];

    // error_log("posts is: " . json_encode($campanhas) . " message is: " . json_encode($links_das_campanhas) . " redirect is: " . json_encode($_SERVER["REQUEST_URI"]) . " user ip is: " . get_visitor_ip());
    // Itera sobre as campanhas para verificar se o usuário está em alguma delas
    foreach ($links_das_campanhas as $key => $value) {
        // Verifica se o usuário está em uma página de campanha correspondente

        // Verifica se a página atual corresponde a um link de campanha específico
        if ($current_page === $value['link']) {
            // Registra um log indicando a qual página foi associada
            // error_log('linked to '.$value['ID']."_".$value['link']);

            // Obtém os parâmetros da URL para repassar, se estiverem vazios, usa os parâmetros padrão da campanha
            $parametros = (!empty($_SERVER['QUERY_STRING']))
                ? $_SERVER['QUERY_STRING']
                : get_post_meta($value['ID'], 'parametros_padrao', true);

            // Obtém uma lista de páginas associadas à campanha
            $paginas = get_post_meta($value['ID'], 'paginas_campanha', false);

            // Seleciona aleatoriamente uma página da lista
            $indice_aleatorio = array_rand($paginas, 1);

            // Obtém o slug (identificador) da página
            $versao = get_post_field('post_name', get_post($paginas[$indice_aleatorio]));

            // Obtém o URL da página sorteada
            $page_link = get_page_uri($paginas[$indice_aleatorio]);

            // Cria opções para o cookie
            $arr_cookie_options = array(
                'expires' => time() + 60 * 60 * 24 * 30,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'], // Ponto inicial para compatibilidade ou use subdomínio
                'secure' => true, // ou false
                'httponly' => true, // ou false
                'samesite' => 'None', // None || Lax  || Strict
            );

            // Obtém o endereço IP do usuário
            $user_ip = get_visitor_ip();

            // Verifica se o endereço IP do usuário não está em uma lista proibida
            if (check_ip_version($user_ip)) {
                $ip_forbidden = (new subnetInterpreter)->serial_ipv4_verification($user_ip);
            } else {
                $ip_forbidden = (new subnetInterpreter)->serial_ipv6_verification($user_ip);
            }

            // Se o IP do usuário não estiver proibido
            if (!$ip_forbidden) {
                // Gera um hash para o cookie
                $hash = generate_guidv4();

                // Prepara os dados para o cookie
                $cookie_data['campaing'] = $value['ID'];
                $cookie_data['hash'] = $hash;
                $cookie_data['ip'] = $user_ip;
                $cookie_data['date'] = current_time('mysql');
                $cookie_data['page_is'] = $value['link'];
                $cookie_data['params'] = $parametros;
                $cookie_data['destination'] = $page_link;
                $cookie_data['versao'] = $versao;
                $cookie_data['referer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'no_ref';

                // Salva o cookie no navegador do usuário
                setcookie('teste_ab', json_encode($cookie_data), $arr_cookie_options);

                // Salva os dados do cookie no banco de dados
                save_report_data($cookie_data);
            }

            // Redireciona para a página sorteada, repassando os parâmetros recebidos
            header('Location: ' . $page_link . '/?' . $parametros . '&versao=' . $versao);

            // Finaliza a execução do script
            exit();
        }

        // Verifica se a página atual corresponde a uma página de agradecimento (ty_page)
        if ($current_page === $value['ty_page']) {
            // Registra um log indicando a tentativa de redirecionamento para a página de agradecimento atual
            // error_log('ty to '.$value['ID']."_".$value['link']);

            // Obtém o endereço IP do usuário
            $user_ip = get_visitor_ip();

            // Verifica se o endereço IP é do tipo IPv4 ou IPv6 e executa a verificação de permissão do IP
            if (check_ip_version($user_ip)) {
                $ip_forbidden = (new subnetInterpreter)->serial_ipv4_verification($user_ip);
            } else {
                $ip_forbidden = (new subnetInterpreter)->serial_ipv6_verification($user_ip);
            }

            // Verifica se o cookie 'teste_ab' existe e se o endereço IP não está proibido
            if (isset($_COOKIE["teste_ab"]) && !$ip_forbidden) {
                // Decodifica os dados do cookie 'teste_ab' em um array
                $cookie_data = json_decode(stripslashes($_COOKIE["teste_ab"]), true);

                // Atualiza os dados do cookie com o endereço IP de retorno e o ID da campanha atual
                $cookie_data['return_ip'] = $user_ip;
                $cookie_data['campaing'] = $value['ID'];

                // Atualiza os dados no banco de dados
                update_report_data($cookie_data);

                // Obtém a versão da página da campanha
                $versao = $cookie_data['versao'];

                // Remove o cookie 'teste_ab' ao limpar todos os cookies existentes
                foreach ($_COOKIE as $key => $value) {
                    if ($key === 'teste_ab') {
                        unset($_COOKIE['teste_ab']);
                        setcookie('teste_ab', null, -1, '/', $_SERVER['HTTP_HOST']);
                    }
                }

                // Redireciona o usuário para a mesma página de agradecimento com os parâmetros do cookie
                header('Location: ' . $_SERVER["REQUEST_URI"] . "/" . $_SERVER['QUERY_STRING'] . '/?' . $cookie_data['params'] . '&versao=' . $versao);
            }
        }


    }
}

// Adiciona ação para redirecionar se necessário durante o carregamento do template
add_action('template_redirect', 'redirect_if_any');