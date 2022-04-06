<?php
defined( 'ABSPATH' ) || exit;
/**
 * Plugin Name: AB Test X
 * Plugin URI: https://3xweb.site
 * Description: Permite a criação de campanhas de teste do tipo A/B
 * Author: Douglas de Araújo
 * Author URI: https://3xweb.site/
 * Version: 1.0.0
 * Requires at least: 4.4
 * Tested up to: 5.7
 * WC requires at least: 3.0
 * WC tested up to: 5.4
 * Text Domain: abtestx
 * Domain Path: /languages
 */

/**
 * Required minimums and constants
 */


function abTestX() {

    static $plugin;

    if ( ! isset( $plugin ) ) {

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
                if ( null === self::$instance ) {
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
                add_action( 'admin_init', [ $this, 'install' ] );
                $this->includes();

                if ( is_admin() ) {
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
                if ( ! is_plugin_active( plugin_basename( __FILE__ ) ) ) {
                    return;
                }

            }
            private function includes() {
                include_once dirname( __FILE__ ) . '/src/redirects.php';

            }
            /**
             * Admin includes.
             */
            private function admin_includes() {                
                include_once dirname( __FILE__ ) . '/src/ab-test-x-custom-post.php';

            }

        }

        $plugin = _3x_abTestX::get_instance();

    }

    return $plugin;
}




function _abtest_servico_init() {
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    load_plugin_textdomain( 'abtestx', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

    // carregando scripts adicionais
    
    function _3x_abTestX_scriptLoader(){
        // Register each script
        wp_register_script(
            'woo_os_javascript', 
            plugins_url('/js/main.js', __FILE__ ), 
            array( 'jquery' ),
            false,
            true
        );
        wp_enqueue_script( 
            'woo_os_jquery_select2', 
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', 
            array('jquery'), 
            false, 
            true );

        wp_register_style('woo_os_select2_css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');

        wp_enqueue_style('woo_os_select2_css');
        wp_enqueue_script('woo_os_jquery_select2');
        wp_enqueue_script('woo_os_javascript');
    }
    add_action( 'admin_enqueue_scripts', '_3x_abTestX_scriptLoader' );

    /**
     * Carregando scripts publicos
     */
    function _3x_abTestX_publicScriptLoader($hook) {

        // create my own version codes
        // $my_js_ver  = date("ymd-Gis", filemtime( plugin_dir_path( __FILE__ ) . '/resources/js/' ));

        // 


    }
    add_action('wp_enqueue_scripts', '_3x_abTestX_publicScriptLoader');

    abTestX();
}
add_action( 'plugins_loaded', '_abtest_servico_init' );