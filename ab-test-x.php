<?php
// Garante que o código não seja executado diretamente fora do WordPress
defined('ABSPATH') || exit;
/**
 * Plugin Name: AB Test X
 * Plugin URI: https://3xweb.site
 * Description: Permite a criação de campanhas de teste do tipo A/B
 * Author: Douglas de Araújo
 * Author URI: https://3xweb.site/
 * Version: 1.2.3
 * Requires at least: 4.4
 * Tested up to: 6.3
 * Text Domain: abtestx
 * Domain Path: /languages
 */

/**
 * Required minimums and constants
 */

/**
 * Esta função cria e gerencia a instância Singleton do plugin AB Test X.
 * Utiliza uma classe interna (_3x_abTestX) para manipulação e inclusão de arquivos necessários.
 */
function abTestX()
{
    // Variável estática para armazenar a instância do plugin
    static $plugin;

    // Verifica se a instância ainda não foi criada
    if (!isset($plugin)) {

        // Classe interna responsável pela instância Singleton
        class _3x_abTestX
        {
            // Instância Singleton da classe
            private static $instance;

            // Método para obter a instância Singleton desta classe
            public static function get_instance()
            {
                if (null === self::$instance) {
                    self::$instance = new self();
                }
                return self::$instance;
            }

            // Métodos mágicos para prevenir clonagem e desserialização da instância
            public function __clone() {}
            public function __wakeup() {}

            // Construtor protegido para prevenir a criação de uma nova instância fora desta classe
            public function __construct()
            {
                // Adiciona ação de inicialização do admin e inclui arquivos necessários
                add_action('admin_init', [$this, 'install']);
                $this->includes();

                // Se estiver no painel de administração, inclui arquivos específicos
                if (is_admin()) {
                    $this->admin_includes();
                }
            }

            // Método para lidar com atualizações do plugin
            public function install()
            {
                // Verifica se o plugin está ativo antes de executar atualizações
                if (!is_plugin_active(plugin_basename(__FILE__))) {
                    return;
                }
            }

            // Método privado para inclusão de arquivos gerais
            private function includes()
            {
                include_once dirname(__FILE__) . '/src/redirects.php';
                include_once dirname(__FILE__) . '/src/iptools/subnet-interpreter-class.php';
            }

            // Método privado para inclusão de arquivos específicos do admin
            private function admin_includes()
            {
                include_once dirname(__FILE__) . '/src/ab-test-x-custom-post.php';
                include_once dirname(__FILE__) . '/src/reports.php';
                include_once dirname(__FILE__) . '/src/ajax-calls.php';
            }
        }

        // Cria uma instância Singleton do plugin, usando a classe interna
        $plugin = _3x_abTestX::get_instance();
    }

    return $plugin;
}


/**
 * Inicializa o serviço do plugin AB Test X, carregando os scripts e definindo o carregamento dos arquivos de idioma.
 */
function _abtest_servico_init()
{
    // Carrega o arquivo de idioma do plugin
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    load_plugin_textdomain('abtestx', false, plugin_basename(dirname(__FILE__)) . '/languages');

    // Função interna para carregar scripts adicionais
    function _3x_abTestX_scriptLoader()
    {
        // Registra e enfileira cada script necessário
        wp_register_script(
            'ab_test_javascript',
            plugins_url('/js/main.js', __FILE__),
            array('jquery'),
            false,
            true
        );
        wp_enqueue_script(
            'ab_test_jquery_select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            array('jquery'),
            false,
            true
        );

        wp_register_style('ab_test_select2_css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');

        wp_enqueue_style('ab_test_select2_css');
        wp_enqueue_script('ab_test_jquery_select2');
        wp_enqueue_script('ab_test_javascript');
    }

    // Adiciona a ação para carregar os scripts quando estiver no admin
    add_action('admin_enqueue_scripts', '_3x_abTestX_scriptLoader');

    // Inicializa o plugin AB Test X
    abTestX();
}

// Chama a função de inicialização do serviço ao carregar os plugins
add_action('plugins_loaded', '_abtest_servico_init');

// Funções customizadas do banco de dados
global $jal_db_version;
$jal_db_version = '1.0';

function ab_data_table_install()
{
    global $wpdb;
    global $jal_db_version;

    // Nome da tabela a ser criada
    $table_name = $wpdb->prefix . 'ab_test_data';

    // Obtém o charset e collate do banco de dados atual
    $charset_collate = $wpdb->get_charset_collate();

    // Consulta SQL para criar a tabela
    $sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        cookie_hash varchar(255)  NULL,
        origin_ip varchar(50) NULL,
		creation_time datetime NULL, /*DEFAULT '0000-00-00 00:00:00'*/
		page_is varchar(255)  NULL,
		params varchar(255) NULL,
        destination varchar(255) NULL,
        referer varchar(255) NULL,
        return_ip varchar(50) NULL,
        return_time datetime NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

    // Inclui o arquivo necessário para executar a atualização da tabela no banco de dados
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Executa a consulta SQL e cria a tabela no banco de dados
    dbDelta($sql);

    // Adiciona a versão do banco de dados como uma opção no WordPress
    add_option('ab_test_x_db_version', $jal_db_version);
}

// Registra a função de instalação da tabela para ser executada durante a ativação do plugin
register_activation_hook(__FILE__, 'ab_data_table_install');
