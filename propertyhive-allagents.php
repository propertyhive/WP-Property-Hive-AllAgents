<?php
/**
 * Plugin Name: Property Hive AllAgents
 * Plugin Uri: http://wp-property-hive.com/addons/allagents/
 * Description: Quickly and easily display ratings and reviews from AllAgents
 * Version: 1.0.0
 * Author: PropertyHive
 * Author URI: http://wp-property-hive.com
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'PH_AllAgents' ) ) :

final class PH_AllAgents {

    /**
     * @var string
     */
    public $version = '1.0.0';

    /**
     * @var Property Hive The single instance of the class
     */
    protected static $_instance = null;
    
    /**
     * Main Property Hive AllAgents Instance
     *
     * Ensures only one instance of Property Hive AllAgents is loaded or can be loaded.
     *
     * @static
     * @return Property Hive AllAgents - Main instance
     */
    public static function instance() 
    {
        if ( is_null( self::$_instance ) ) 
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {

        $this->id    = 'allagents';
        $this->label = __( 'AllAgents', 'propertyhive' );

        // Define constants
        $this->define_constants();

        // Include required files
        $this->includes();

        //add_action( 'wp_enqueue_scripts', array( $this, 'load_allagents_scripts' ) );
        //add_action( 'wp_enqueue_scripts', array( $this, 'load_allagents_styles' ) );

        add_shortcode( 'allagents', array( $this, 'propertyhive_allagents_shortcode' ) );
    }

    /**
     * Define PH AllAgents Constants
     */
    private function define_constants() 
    {
        define( 'PH_ALLAGENTS_PLUGIN_FILE', __FILE__ );
        define( 'PH_ALLAGENTS_VERSION', $this->version );
    }

    private function includes()
    {
        //include_once( dirname( __FILE__ ) . "/includes/class-ph-allagents-install.php" );
    }

    public function propertyhive_allagents_shortcode( $atts )
    {
        $atts = shortcode_atts( array(

        ), $atts );

        /*wp_enqueue_style( 'ph-allagents' );

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'ph-allagents' );*/

        ob_start();

        /*$template = locate_template( array('propertyhive/allagents.php') );
        if ( !$template )
        {
            include( dirname( PH_ALLAGENTS_PLUGIN_FILE ) . '/templates/allagents.php' );
        }
        else
        {
            include( $template );
        }*/

        return ob_get_clean();
    }

    /*public function load_allagents_scripts() {

        $assets_path = str_replace( array( 'http:', 'https:' ), '', untrailingslashit( plugins_url( '/', __FILE__ ) ) ) . '/assets/';

        wp_register_script( 
            'ph-allagents', 
            $assets_path . 'js/propertyhive-allagents.js', 
            array(), 
            PH_ALLAGENTS_VERSION,
            true
        );
    }

    public function load_allagents_styles() {

        $assets_path = str_replace( array( 'http:', 'https:' ), '', untrailingslashit( plugins_url( '/', __FILE__ ) ) ) . '/assets/';

        wp_register_style( 
            'ph-allagents', 
            $assets_path . 'css/propertyhive-allagents.css', 
            array(), 
            PH_ALLAGENTS_VERSION
        );
    }*/
}

endif;

/**
 * Returns the main instance of PH_AllAgents to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return PH_AllAgents
 */
function PHAA() {
    return PH_AllAgents::instance();
}

PHAA();