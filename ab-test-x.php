<?php
defined('ABSPATH') || exit;
/**
 * Plugin Name: AB Test X
 * Plugin URI: https://3xweb.site
 * Description: Permite a criação de campanhas de teste do tipo A/B
 * Author: Douglas de Araújo
 * Author URI: https://3xweb.site/
 * Version: 1.2.2
 * Requires at least: 4.4
 * Tested up to: 5.9
 * Text Domain: abtestx
 * Domain Path: /languages
 */

/**
 * Required minimums and constants
 */

function abTestX() {

    static $plugin;

    if (!isset($plugin)) {

        class _3x_abTestX {

            /**
             * The *Singleton* instance of this class
             *
             * @var Singleton
             */
            private static $instance;

            /**
             * Returns the *Singleton* instance of this class.
             *
             * @return Singleton The *Singleton* instance.
             */
            public static function get_instance() {
                if (null === self::$instance) {
                    self::$instance = new self();
                }
                return self::$instance;
            }

            /**
             * Private clone method to prevent cloning of the instance of the
             * *Singleton* instance.
             *
             * @return void
             */
            public function __clone() {}

            /**
             * Private unserialize method to prevent unserializing of the *Singleton*
             * instance.
             *
             * @return void
             */
            public function __wakeup() {}

            /**
             * Protected constructor to prevent creating a new instance of the
             * *Singleton* via the `new` operator from outside of this class.
             */
            public function __construct() {
                add_action('admin_init', [$this, 'install']);
                $this->includes();

                if (is_admin()) {
                    $this->admin_includes();
                }

            }
            /**
             * Handles upgrade routines.
             *
             * @since 3.1.0
             * @version 3.1.0
             */
            public function install() {
                if (!is_plugin_active(plugin_basename(__FILE__))) {
                    return;
                }

            }
            private function includes() {
                include_once dirname(__FILE__) . '/src/redirects.php';
                include_once dirname(__FILE__) . '/src/iptools/subnet-interpreter-class.php';

            }
            /**
             * Admin includes.
             */
            private function admin_includes() {
                include_once dirname(__FILE__) . '/src/ab-test-x-custom-post.php';
                include_once dirname(__FILE__) . '/src/reports.php';
                include_once dirname(__FILE__) . '/src/ajax-calls.php';
                

            }

        }

        $plugin = _3x_abTestX::get_instance();

    }

    return $plugin;
}

function _abtest_servico_init() {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    load_plugin_textdomain('abtestx', false, plugin_basename(dirname(__FILE__)) . '/languages');

    // carregando scripts adicionais

    function _3x_abTestX_scriptLoader() {
        // Register each script
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
            true);

        wp_register_style('ab_test_select2_css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');

        wp_enqueue_style('ab_test_select2_css');
        wp_enqueue_script('ab_test_jquery_select2');
        wp_enqueue_script('ab_test_javascript');

    }
    add_action('admin_enqueue_scripts', '_3x_abTestX_scriptLoader');

    abTestX();
}
add_action('plugins_loaded', '_abtest_servico_init');

//custom database functions
global $jal_db_version;
$jal_db_version = '1.0';

function ab_data_table_install() {
    global $wpdb;
    global $jal_db_version;

    $table_name = $wpdb->prefix . 'ab_test_data';

    $charset_collate = $wpdb->get_charset_collate();

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

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    add_option('ab_test_x_db_version', $jal_db_version);
}

register_activation_hook(__FILE__, 'ab_data_table_install');