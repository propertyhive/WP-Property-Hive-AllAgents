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

        add_filter( "plugin_action_links_" . plugin_basename( __FILE__ ), array( $this, 'plugin_add_settings_link' ) );

        if ( is_plugin_active( 'propertyhive/propertyhive.php' ) )
        {
            // Property Hive is active. Display in Property Hive settings area
            add_filter( 'propertyhive_settings_tabs_array', array( $this, 'add_settings_tab' ), 19 );
            add_action( 'propertyhive_settings_' . $this->id, array( $this, 'output' ) );
            add_action( 'propertyhive_settings_save_' . $this->id, array( $this, 'save' ) );
            add_action( 'propertyhive_admin_field_allagents_widgets', array( $this, 'allagents_widgets' ) );
        }
        else
        {
            // Property Hive is not active. Display as normal settings page

        }

        add_action( 'phallagentscronhook', array( $this, 'cache_reviews' ) );

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
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        include_once( dirname( __FILE__ ) . "/includes/class-ph-allagents-install.php" );
    }

    public function plugin_add_settings_link( $links )
    {
        if ( is_plugin_active( 'propertyhive/propertyhive.php' ) )
        {
            // Property Hive is active
            $settings_link = '<a href="' . admin_url('admin.php?page=ph-settings&tab=allagents') . '">' . __( 'Settings' ) . '</a>';
            array_push( $links, $settings_link );
        }
        else
        {
            // Property Hive is not active

        }
        return $links;
    }

    public function cache_reviews()
    {
        $current_settings = get_option( 'propertyhive_allagents', array() );

        $widgets = isset($current_settings['widgets']) ? $current_settings['widgets'] : array();

        if ( is_array($widgets) && !empty($widgets) )
        {
            foreach ( $widgets as $i => $widget )
            {
                if (
                    isset($widget['integration_type']) && $widget['integration_type'] == 'api' &&
                    isset($widget['api_key']) && trim($widget['api_key']) != '' 
                )
                {
                    $overall = array();
                    $reviews = array();

                    // Overall
                    $response = wp_remote_get(
                        'https://www.allagents.co.uk/api/v1/firms/[your-firm-link]/',
                        array(
                            'headers' => array(
                                'APIKEY' => $widget['api_key'],
                            ),
                            'timeout' => 30
                        )
                    );

                    if ( is_array( $response ) && !is_wp_error( $response ) ) 
                    {
                        $headers = $response['headers']; // array of http header lines
                        $body = $response['body']; // use the content

                        $body = json_decode($body);

                        if ( $body !== FALSE )
                        {
                            $overall = $body;
                        }
                    }

                    // Reviews
                    $response = wp_remote_get(
                        'https://www.allagents.co.uk/api/v1/firms/[your-firm-link]/reviews/',
                        array(
                            'headers' => array(
                                'APIKEY' => $widget['api_key'],
                            ),
                            'timeout' => 30
                        )
                    );

                    if ( is_array( $response ) && !is_wp_error( $response ) ) 
                    {
                        $headers = $response['headers']; // array of http header lines
                        $body = $response['body']; // use the content

                        $body = json_decode($body);

                        if ( $body !== FALSE )
                        {
                            $reviews = $body;
                        }
                    }

                    $current_settings['widgets'][$i]['overall'] = $overall;
                    $current_settings['widgets'][$i]['reviews'] = $reviews;
                }
            }
        }

        update_option( 'propertyhive_allagents', $current_settings );
    }

    /**
     * Add a new settings tab to the Property Hive settings tabs array.
     *
     * @param array $settings_tabs Array of Property Hive setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of Property Hive setting tabs & their labels, including the Subscription tab.
     */
    public function add_settings_tab( $settings_tabs ) {
        $settings_tabs[$this->id] = $this->label;
        return $settings_tabs;
    }

    /**
     * Uses the Property Hive admin fields API to output settings.
     *
     * @uses propertyhive_admin_fields()
     * @uses self::get_settings()
     */
    public function output() {

        global $current_section, $hide_save_button;

        switch ($current_section)
        {
            case "addwidget":
            {
                propertyhive_admin_fields( self::get_widget_settings() );
                break;
            }
            case "editwidget":
            {
                propertyhive_admin_fields( self::get_widget_settings() );
                break;
            }
            default:
            {
                $hide_save_button = true;
                propertyhive_admin_fields( self::get_allagents_settings() );
            }
        }
    }

    /**
     * Get map search settings
     *
     * @return array Array of settings
     */
    public function get_allagents_settings() {

        $current_settings = get_option( 'propertyhive_allagents', array() );

        $settings = array(

            array( 'title' => __( 'AllAgents Settings', 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'allagents_settings' ),

            array(
                'type'      => 'allagents_widgets',
            ),

            array( 'type' => 'sectionend', 'id' => 'allagents_settings')

        );

        return $settings;
    }

    /**
     * Output list of widgets
     *
     * @access public
     * @return void
     */
    public function allagents_widgets() {
        global $wpdb, $post;
        
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                &nbsp;
            </th>
            <td class="forminp forminp-button">
                <a href="<?php echo admin_url( 'admin.php?page=ph-settings&tab=allagents&section=addwidget' ); ?>" class="button alignright"><?php echo __( 'Add New Widget', 'propertyhive' ); ?></a>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php _e( 'Widgets', 'propertyhive' ) ?></th>
            <td class="forminp">
                <table class="ph_widgets widefat" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="name"><?php _e( 'Name', 'propertyhive' ); ?></th>
                            <th class="shortcode"><?php _e( 'Shortcode', 'propertyhive' ); ?></th>
                            <th class="settings">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                            $current_allagentsoptions = get_option( 'propertyhive_allagents' );
                            $widgets = array();
                            if ($current_allagentsoptions !== FALSE)
                            {
                                if (isset($current_allagentsoptions['widgets']))
                                {
                                    $widgets = $current_allagentsoptions['widgets'];
                                }
                            }

                            if (!empty($widgets))
                            {
                                $upload_dir = wp_upload_dir();

                                foreach ($widgets as $i => $widget)
                                {
                                    echo '<tr>';
                                        echo '<td class="name">' . $widget['name'] . '</td>';
                                        echo '<td class="shortcode"><code>[allagents id="' . ($i + 1) . '"]</code></td>';
                                        echo '<td class="settings">
                                            <a class="button" href="' . admin_url( 'admin.php?page=ph-settings&tab=allagents&section=editwidget&id=' . $i ) . '">' . __( 'Edit', 'propertyhive' ) . '</a>
                                        </td>';
                                    echo '</tr>';
                                }
                            }
                            else
                            {
                                echo '<tr>';
                                    echo '<td align="center" colspan="4">' . __( 'No widgets exist', 'propertyhive' ) . '</td>';
                                echo '</tr>';
                            }
                        ?>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
                &nbsp;
            </th>
            <td class="forminp forminp-button">
                <a href="<?php echo admin_url( 'admin.php?page=ph-settings&tab=allagents&section=addwidget' ); ?>" class="button alignright"><?php echo __( 'Add New Widget', 'propertyhive' ); ?></a>
            </td>
        </tr>
        <?php
    }

    /**
     * Get add/edit widget settings
     *
     * @return array Array of settings
     */
    public function get_widget_settings() {

        global $current_section, $post;

        $current_id = ( !isset( $_REQUEST['id'] ) ) ? '' : sanitize_title( $_REQUEST['id'] );

        $widget_details = array();

        if ($current_id != '')
        {
            // We're editing one

            $current_allagents_options = get_option( 'propertyhive_allagents' );

            $wigdets = $current_allagents_options['widgets'];

            if (isset($wigdets[$current_id]))
            {
                $widget_details = $wigdets[$current_id];
            }
        }

        $settings = array(
            array( 'title' => __( ( $current_section == 'addwidget' ? 'Add Widget' : 'Edit Widget' ), 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'widget_settings' )
        );

        $settings[] = array(
            'title'     => __( 'Name', 'propertyhive' ),
            'id'        => 'name',
            'type'      => 'text',
            'default'   => isset($widget_details['name']) && $widget_details['name'] != '' ? $widget_details['name'] : 'AllAgents Widget',
            'desc'      => __( 'If creating multiple widgets (i.e. one per branch) this allows you to differentiate between them', 'propertyhive' )
        );

        $settings[] = array(
            'title'     => __( 'Integration Type', 'propertyhive' ),
            'id'        => 'integration_type',
            'type'      => 'radio',
            'default'   => ( isset($widget_details['integration_type']) && in_array($widget_details['integration_type'], array('widget', 'api')) ? $widget_details['integration_type'] : 'widget'),
            'options'   => array(
                'widget' => '<strong>' . __( 'AllAgents Widget', 'propertyhive' ) . '</strong> ',
                'api' => '<strong>' . __( 'AllAgents API', 'propertyhive' ) . '</strong> - Available to AllAgents Premium Support Plus members',
            ),
        );

        $settings[] = array(
            'title'     => __( 'Widget Code', 'propertyhive' ),
            'id'        => 'widget_code',
            'type'      => 'textarea',
            'default'   => ( isset($widget_details['widget_code']) ? stripslashes($widget_details['widget_code']) : ''),
            'css'       => 'max-width:450px; width:100%; height:95px;',
            'desc'      => '<a href="https://www.allagents.co.uk/widget/" target="_blank">' . __( 'Obtain Widget Code', 'propertyhive' ) . '</a>'
        );

        $settings[] = array(
            'title'     => __( 'API Key', 'propertyhive' ),
            'id'        => 'api_key',
            'type'      => 'text',
            'default'   => ( isset($widget_details['api_key']) ? $widget_details['api_key'] : ''),
            'desc'      => 'The API key can be obtained from within your <a href="https://www.allagents.co.uk/properties/users/login/" target="_blank">AllAgents account</a>'
        );

        $settings[] = array(
            'title'     => __( 'Show Reviews For', 'propertyhive' ),
            'id'        => 'show_reviews_for',
            'type'      => 'radio',
            'default'   => ( isset($widget_details['show_reviews_for']) && in_array($widget_details['show_reviews_for'], array('firm', 'branch')) ? $widget_details['show_reviews_for'] : 'firm'),
            'options'   => array(
                'firm' => 'Entire Firm',
                'branch' => 'Individual Branch',
            ),
        );

        $settings[] = array(
            'title'     => __( 'Firm Link', 'propertyhive' ),
            'id'        => 'firm_link',
            'type'      => 'text',
            'default'   => isset($widget_details['firm_link'])? $widget_details['firm_link'] : '',
        );

        $settings[] = array(
            'title'     => __( 'Branch Link', 'propertyhive' ),
            'id'        => 'branch_link',
            'type'      => 'text',
            'default'   => isset($widget_details['branch_link'])? $widget_details['branch_link'] : '',
        );

        $settings[] = array(
            'id'        => 'custom_js',
            'type'      => 'html',
            'html'      => '<script>
                jQuery(document).ready(function()
                {
                    jQuery(\'input[name=integration_type][type=radio]\').change(function() { ph_toggle_allagents_options(); });
                    jQuery(\'input[name=show_reviews_for][type=radio]\').change(function() { ph_toggle_allagents_options(); });

                    ph_toggle_allagents_options();
                });

                function ph_toggle_allagents_options()
                {
                    var selected_val = jQuery(\'input[name=integration_type][type=radio]:checked\').val();

                    jQuery(\'#row_widget_code\').hide();
                    jQuery(\'#row_api_key\').hide();
                    jQuery(\'#row_show_reviews_for\').hide();
                    jQuery(\'#row_branch_link\').hide();

                    if ( selected_val == \'widget\' )
                    {
                        jQuery(\'#row_widget_code\').show();
                    }
                    if ( selected_val == \'api\' )
                    {
                        jQuery(\'#row_api_key\').show();
                        jQuery(\'#row_show_reviews_for\').show();

                        var selected_val = jQuery(\'input[name=show_reviews_for][type=radio]:checked\').val();

                        if ( selected_val == \'branch\' )
                        {
                            jQuery(\'#row_branch_link\').show();
                        }
                    }
                }
            </script>'
        );

            
        $settings[] = array( 'type' => 'sectionend', 'id' => 'widget_settings');

        return $settings ;
    }

    /**
     * Uses the Property Hive options API to save settings.
     *
     * @uses propertyhive_update_options()
     * @uses self::get_settings()
     */
    public function save() {

        global $current_section;

        switch ($current_section)
        {
            case 'addwidget': 
            {
                $current_allagents_options = get_option( 'propertyhive_allagents' );
                        
                if ($current_allagents_options === FALSE)
                {
                    // This is a new option
                    $new_allagents_options = array();
                    $new_allagents_options['widgets'] = array();
                }
                else
                {
                    $new_allagents_options = $current_allagents_options;
                }

                $widget = array(
                    'name' => sanitize_text_field($_POST['name']),
                    'integration_type' => isset($_POST['integration_type']) && in_array($_POST['integration_type'], array('widget', 'api')) ? sanitize_text_field($_POST['integration_type']) : 'widget',
                    'widget_code' => $_POST['widget_code'],
                    'api_key' => sanitize_text_field($_POST['api_key']),
                    'show_reviews_for' => isset($_POST['show_reviews_for']) && in_array($_POST['show_reviews_for'], array('firm', 'branch')) ? sanitize_text_field($_POST['show_reviews_for']) : 'firm',
                    'firm_link' => sanitize_text_field($_POST['firm_link']),
                    'branch_link' => sanitize_text_field($_POST['branch_link']),
                );

                $new_allagents_options['widgets'][] = $widget;

                update_option( 'propertyhive_allagents', $new_allagents_options );

                PH_Admin_Settings::add_message( __( 'Widget added successfully', 'propertyhive' ) . ' ' . '<a href="' . admin_url( 'admin.php?page=ph-settings&tab=allagents' ) . '">' . __( 'Return to AllAgents Options', 'propertyhive' ) . '</a>' );
                break;
            }
            case 'editwidget': 
            {
                $current_id = ( !isset( $_REQUEST['id'] ) ) ? '' : sanitize_title( $_REQUEST['id'] );
                $current_allagents_options = get_option( 'propertyhive_allagents' );

                $new_allagents_options = $current_allagents_options;

                $widget = array(
                    'name' => sanitize_text_field($_POST['name']),
                    'integration_type' => isset($_POST['integration_type']) && in_array($_POST['integration_type'], array('widget', 'api')) ? sanitize_text_field($_POST['integration_type']) : 'widget',
                    'widget_code' => $_POST['widget_code'],
                    'api_key' => sanitize_text_field($_POST['api_key']),
                    'show_reviews_for' => isset($_POST['show_reviews_for']) && in_array($_POST['show_reviews_for'], array('firm', 'branch')) ? sanitize_text_field($_POST['show_reviews_for']) : 'firm',
                    'firm_link' => sanitize_text_field($_POST['firm_link']),
                    'branch_link' => sanitize_text_field($_POST['branch_link']),
                );

                $new_allagents_options['widgets'][$current_id] = array_merge( $current_allagents_options['widgets'][$current_id], $widget );

                update_option( 'propertyhive_allagents', $new_allagents_options );
                        
                PH_Admin_Settings::add_message( __( 'Widget details updated successfully', 'propertyhive' ) . ' ' . '<a href="' . admin_url( 'admin.php?page=ph-settings&tab=allagents' ) . '">' . __( 'Return to AllAgents Options', 'propertyhive' ) . '</a>' );
                
                break;
            }
        }
        
        do_action( 'phallagentscronhook' );
    }

    public function propertyhive_allagents_shortcode( $atts )
    {
        $atts = shortcode_atts( array(
            'id' => ''
        ), $atts );

        /*wp_enqueue_style( 'ph-allagents' );

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'ph-allagents' );*/

        ob_start();

        if ( $atts['id'] == '' )
        {
            echo __( 'No id attribute passed in shortcode. Please see settings page for full shortcode.', 'propertyhive' );
            return ob_get_clean();
        }

        $current_settings = get_option( 'propertyhive_allagents', array() );

        $widgets = isset($current_settings['widgets']) ? $current_settings['widgets'] : array();

        $atts['id'] = $atts['id']-1;

        if ( !isset($widgets[$atts['id']]) )
        {
            echo 'No widget with id ' . $atts['id'] . ' exists.';
            return ob_get_clean();
        }

        $widget = $widgets[$atts['id']];

        if ( isset($widget['integration_type']) )
        {
            switch ( $widget['integration_type'] )
            {
                case "widget":
                {
                    echo stripslashes($widget['widget_code']);
                    break;
                }
                case "api":
                {

                    break;
                }
            }
        }

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