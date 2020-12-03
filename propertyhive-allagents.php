<?php
/**
 * Plugin Name: Property Hive AllAgents Review Embed
 * Plugin Uri: http://wp-property-hive.com/addons/allagents/
 * Description: Quickly and easily display ratings and reviews from AllAgents, plus customisation options
 * Version: 1.0.1
 * Author: PropertyHive
 * Author URI: http://wp-property-hive.com
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'PH_AllAgents' ) ) :

final class PH_AllAgents {

    /**
     * @var string
     */
    public $version = '1.0.1';

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
            add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        }

        add_action( 'phallagentscronhook', array( $this, 'cache_reviews' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'load_allagents_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'load_allagents_styles' ) );

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

    public function add_settings_page()
    {
        add_options_page( 'AllAgents', 'AllAgents', 'manage_options', 'allagents', array( $this, 'render_plugin_settings_page' ) );
    }

    public function render_plugin_settings_page()
    {
        global $current_section;

        $current_section = isset($_GET['section']) ? $_GET['section'] : '';

        if ( ! empty( $_POST ) )
        {
            $this->save_rendered_settings();
            $current_section = '';
        }

?>
<div class="wrap">
    <h1>AllAgents Settings</h1>

    <?php
        switch ( $current_section )
        {
            case "addwidget":
            {
                $this->render_settings();
                break;
            }
            case "editwidget":
            {
                $this->render_settings();
                break;
            }
            default:
            {
    ?>
    <table cellpadding="5" cellspacing="0" width="98%">
        <?php
            $this->allagents_widgets(false);
        ?>
    </table>
    <?php
            }
        }
    ?>
</div>
<?php
    }

    private function render_settings()
    {
        $settings = $this->get_widget_settings();

        $current_id = isset($_GET['id']) ? (int)$_GET['id'] : '';

        $widget_details = array();

        $current_allagents_options = get_option( 'propertyhive_allagents' );

        $widgets = isset($current_allagents_options['widgets']) ? $current_allagents_options['widgets'] : array();
        $num_widgets = count($widgets);

        if ($current_id != '')
        {
            // We're editing one
            if (isset($widgets[$current_id]))
            {
                $widget_details = $widgets[$current_id];
            }
        }

        echo '<form action="" method="post"><table class="form-table">';
                
        foreach ( $settings as $setting )
        {
            $description = $setting['desc'];

            switch ($setting['type'])
            {
                case "text":
                case "number":
                case "color":
                {
                    echo '
                    <tr valign="top" id="row_' . esc_attr($setting['id']) . '">
                        <th scope="row" class="titledesc">
                            <label for="' . esc_attr($setting['id']) . '">' . $setting['title'] . '</label>
                        </th>
                        <td class="forminp forminp-text">
                            <input
                                name="' . esc_attr($setting['id']) . '"
                                id="' . esc_attr($setting['id']) . '"
                                type="' . esc_attr($setting['type']) . '"
                                value="' . esc_attr($setting['default']) . '"
                            />
                            ' . $description . '
                        </td>
                    </tr>';
                    break;
                }
                case "textarea":
                {
                    echo '
                    <tr valign="top" id="row_' . esc_attr($setting['id']) . '">
                        <th scope="row" class="titledesc">
                            <label for="' . esc_attr($setting['id']) . '">' . $setting['title'] . '</label>
                        </th>
                        <td class="forminp forminp-text">
                            ' . $description . ($description != '' ? '<br>' : '') . '
                            <textarea
                                style="width:100%; max-width:480px; height:105px;"
                                name="' . esc_attr($setting['id']) . '"
                                id="' . esc_attr($setting['id']) . '"
                                type="' . esc_attr($setting['type']) . '">' . $setting['default'] . '</textarea>
                        </td>
                    </tr>';
                    break;
                }
                case "radio":
                {
                    echo '
                    <tr valign="top" id="row_' . esc_attr($setting['id']) . '">
                        <th scope="row" class="titledesc">
                            <label for="' . esc_attr($setting['id']) . '">' . $setting['title'] . '</label>
                        </th>
                        <td class="forminp forminp-text">
                            <fieldset>
                                ' . $description . '
                                <ul>';
                                    foreach ( $setting['options'] as $key => $val ) {
                                        ?>
                                        <li>
                                            <label><input
                                                name="<?php echo esc_attr( $setting['id'] ); ?>"
                                                value="<?php echo $key; ?>"
                                                type="radio"
                                                <?php checked( $key, $setting['default'] ); ?>
                                                /> <?php echo $val ?></label>
                                        </li>
                                        <?php
                                    }
                                echo '</ul>
                            </fieldset>
                        </td>
                    </tr>';
                    break;
                }
                case "checkbox":
                {
                    echo '
                    <tr valign="top" id="row_' . esc_attr($setting['id']) . '">
                        <th scope="row" class="titledesc">
                            <label for="' . esc_attr($setting['id']) . '">' . $setting['title'] . '</label>
                        </th>
                        <td class="forminp forminp-text">
                            <label for="' . esc_attr($setting['id']) . '">
                            <input
                                name="' . esc_attr($setting['id']) . '"
                                id="' . esc_attr($setting['id']) . '"
                                type="checkbox"
                                value="1"
                                ' . checked( $setting['default'], 'yes', false) . '
                            /> ' . $description . '
                        </label>
                        </td>
                    </tr>';
                    break;
                }
                case "html":
                {
                    echo $setting['html'];
                    break;
                }
                case "title":
                case "sectionend":
                {

                    break;
                }
                default: 
                {
                    echo 'unknown setting type ' . $setting['type'];
                }
            }
        }

        echo '</table>

        ' . wp_nonce_field('propertyhive-settings') . '
        <input name="save" class="button-primary" type="submit" value="' . __( 'Save changes', 'propertyhive' ) . '">
        <a class="button" href="' . admin_url('options-general.php?page=allagents') . '">' . __( 'Cancel', 'propertyhive' ) . '</a>

        </form>';
    }

    private function save_rendered_settings()
    {
        if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'propertyhive-settings' ) )
                die( __( 'Action failed. Please refresh the page and retry.', 'propertyhive' ) );

        $this->save();
    }

    public function plugin_add_settings_link( $links )
    {
        if ( is_plugin_active( 'propertyhive/propertyhive.php' ) )
        {
            // Property Hive is active
            $settings_link = '<a href="' . admin_url('admin.php?page=ph-settings&tab=allagents') . '">' . __( 'Settings' ) . '</a>';
        }
        else
        {
            // Property Hive is not active
            $settings_link = '<a href="' . admin_url('options-general.php?page=allagents') . '">' . __( 'Settings' ) . '</a>';
        }
        array_push( $links, $settings_link );
        return $links;
    }

    public function cache_reviews()
    {
        require( __DIR__ . '/cron.php' );
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
    public function allagents_widgets( $property_hive_active = true ) {
        global $wpdb, $post;
        
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                &nbsp;
            </th>
            <td class="forminp forminp-button">
                <a href="<?php echo $property_hive_active ? admin_url( 'admin.php?page=ph-settings&tab=allagents&section=addwidget' ) : admin_url( 'options-general.php?page=allagents&section=addwidget' ); ?>" class="button alignright"><?php echo __( 'Add New Widget', 'propertyhive' ); ?></a>
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
                                            <a class="button" href="' . ( $property_hive_active ? admin_url( 'admin.php?page=ph-settings&tab=allagents&section=editwidget&id=' . $i ) : admin_url( 'options-general.php?page=allagents&section=editwidget&id=' . $i ) ) . '">' . __( 'Edit', 'propertyhive' ) . '</a>
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
                <a href="<?php echo $property_hive_active ? admin_url( 'admin.php?page=ph-settings&tab=allagents&section=addwidget' ) : admin_url( 'options-general.php?page=allagents&section=addwidget' ); ?>" class="button alignright"><?php echo __( 'Add New Widget', 'propertyhive' ); ?></a>
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

        $current_allagents_options = get_option( 'propertyhive_allagents' );

        $widgets = isset($current_allagents_options['widgets']) ? $current_allagents_options['widgets'] : array();
        $num_widgets = count($widgets);

        if ($current_id != '')
        {
            // We're editing one

            if (isset($widgets[$current_id]))
            {
                $widget_details = $widgets[$current_id];
            }
        }

        $settings = array(
            array( 'title' => __( ( $current_section == 'addwidget' ? 'Add Widget' : 'Edit Widget' ), 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'widget_settings' )
        );

        $settings[] = array(
            'title'     => __( 'Name', 'propertyhive' ),
            'id'        => 'name',
            'type'      => 'text',
            'default'   => isset($widget_details['name']) && $widget_details['name'] != '' ? $widget_details['name'] : 'AllAgents Widget ' . ($num_widgets+1),
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
            'title'     => __( 'Display', 'propertyhive' ),
            'id'        => 'display',
            'type'      => 'radio',
            'default'   => ( isset($widget_details['display']) && in_array($widget_details['display'], array('list', 'carousel')) ? $widget_details['display'] : 'list'),
            'options'   => array(
                'list' => 'List',
                'carousel' => 'Carousel',
            ),
        );

        $settings[] = array(
            'title'     => __( 'Number Of Reviews Displayed', 'propertyhive' ),
            'id'        => 'number_reviews',
            'type'      => 'number',
            'default'   => isset($widget_details['number_reviews'])? $widget_details['number_reviews'] : '20',
        );

        $settings[] = array(
            'title'     => __( 'Show Header', 'propertyhive' ),
            'id'        => 'display_header',
            'type'      => 'checkbox',
            'default'   => ( !isset($widget_details['display_header']) || ( isset($widget_details['display_header']) && $widget_details['display_header'] == 1 ) ? 'yes' : ''),
        );

        $settings[] = array(
            'title'     => __( 'Header Background Colour', 'propertyhive' ),
            'id'        => 'header_background_colour',
            'type'      => 'color',
            'default'   => isset($widget_details['header_background_colour'])? $widget_details['header_background_colour'] : '#0d47a1',
        );

        $settings[] = array(
            'title'     => __( 'Show Footer', 'propertyhive' ),
            'id'        => 'display_footer',
            'type'      => 'checkbox',
            'default'   => ( !isset($widget_details['display_footer']) || ( isset($widget_details['display_footer']) && $widget_details['display_footer'] == 1 ) ? 'yes' : ''),
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
                    jQuery(\'#row_firm_link\').hide();
                    jQuery(\'#row_branch_link\').hide();
                    jQuery(\'#row_display\').hide();
                    jQuery(\'#row_number_reviews\').hide();
                    jQuery(\'#row_display_header\').hide();
                    jQuery(\'#row_header_background_colour\').hide();
                    jQuery(\'#row_display_footer\').hide();

                    if ( selected_val == \'widget\' )
                    {
                        jQuery(\'#row_widget_code\').show();
                    }
                    if ( selected_val == \'api\' )
                    {
                        jQuery(\'#row_api_key\').show();
                        jQuery(\'#row_show_reviews_for\').show();
                        jQuery(\'#row_firm_link\').show();
                        jQuery(\'#row_display\').show();
                        jQuery(\'#row_display_header\').show();
                        jQuery(\'#row_number_reviews\').show();
                        jQuery(\'#row_header_background_colour\').show();
                        jQuery(\'#row_display_footer\').show();

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
                    'widget_code' => wp_kses($_POST['widget_code'], array(
                        'div' => array(
                            'id' => array(),
                        ),
                        'script' => array(
                            'type' => array(),
                            'src' => array(),
                        ),
                    )),
                    'api_key' => sanitize_text_field($_POST['api_key']),
                    'show_reviews_for' => isset($_POST['show_reviews_for']) && in_array($_POST['show_reviews_for'], array('firm', 'branch')) ? sanitize_text_field($_POST['show_reviews_for']) : 'firm',
                    'firm_link' => sanitize_text_field($_POST['firm_link']),
                    'branch_link' => sanitize_text_field($_POST['branch_link']),
                    'number_reviews' => (int)$_POST['number_reviews'],
                    'display' => isset($_POST['display']) && in_array($_POST['display'], array('list', 'carousel')) ? sanitize_text_field($_POST['display']) : 'list',
                    'display_header' => isset($_POST['display_header']) ? sanitize_text_field($_POST['display_header']) : '',
                    'header_background_colour' => sanitize_hex_color($_POST['header_background_colour']),
                    'display_footer' => isset($_POST['display_footer']) ? sanitize_text_field($_POST['display_footer']) : '',
                );

                $new_allagents_options['widgets'][] = $widget;

                update_option( 'propertyhive_allagents', $new_allagents_options );

                if ( is_plugin_active( 'propertyhive/propertyhive.php' ) ) { PH_Admin_Settings::add_message( __( 'Widget added successfully', 'propertyhive' ) . ' ' . '<a href="' . admin_url( 'admin.php?page=ph-settings&tab=allagents' ) . '">' . __( 'Return to AllAgents Options', 'propertyhive' ) . '</a>' ); }
                
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
                    'widget_code' => wp_kses($_POST['widget_code'], array(
                        'div' => array(
                            'id' => array(),
                        ),
                        'script' => array(
                            'type' => array(),
                            'src' => array(),
                        ),
                    )),
                    'api_key' => sanitize_text_field($_POST['api_key']),
                    'show_reviews_for' => isset($_POST['show_reviews_for']) && in_array($_POST['show_reviews_for'], array('firm', 'branch')) ? sanitize_text_field($_POST['show_reviews_for']) : 'firm',
                    'firm_link' => sanitize_text_field($_POST['firm_link']),
                    'branch_link' => sanitize_text_field($_POST['branch_link']),
                    'number_reviews' => (int)$_POST['number_reviews'],
                    'display' => isset($_POST['display']) && in_array($_POST['display'], array('list', 'carousel')) ? sanitize_text_field($_POST['display']) : 'list',
                    'display_header' => isset($_POST['display_header']) ? sanitize_text_field($_POST['display_header']) : '',
                    'header_background_colour' => sanitize_hex_color($_POST['header_background_colour']),
                    'display_footer' => isset($_POST['display_footer']) ? sanitize_text_field($_POST['display_footer']) : '',
                );

                $new_allagents_options['widgets'][$current_id] = array_merge( $current_allagents_options['widgets'][$current_id], $widget );

                update_option( 'propertyhive_allagents', $new_allagents_options );
                        
                if ( is_plugin_active( 'propertyhive/propertyhive.php' ) ) { PH_Admin_Settings::add_message( __( 'Widget details updated successfully', 'propertyhive' ) . ' ' . '<a href="' . admin_url( 'admin.php?page=ph-settings&tab=allagents' ) . '">' . __( 'Return to AllAgents Options', 'propertyhive' ) . '</a>' ); }
                
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
                    if ( !isset($widget['api_key']) || ( isset($widget['api_key']) && $widget['api_key'] == '' ) )
                    {
                        echo 'No API key entered';
                        return ob_get_clean();
                    }
                    if ( !isset($widget['firm_link']) || ( isset($widget['firm_link']) && $widget['firm_link'] == '' ) )
                    {
                        echo 'No firm link entered';
                        return ob_get_clean();
                    }
                    if ( $widget['show_reviews_for'] == 'branch' && ( !isset($widget['branch_link']) || ( isset($widget['branch_link']) && $widget['branch_link'] == '' ) ) )
                    {
                        echo 'No branch link entered';
                        return ob_get_clean();
                    }

                    wp_enqueue_style('ph-allagents');
                    if ( isset($widget['display']) && $widget['display'] == 'carousel' )
                    {
                        wp_enqueue_style('ph-allagents-slick');
                        wp_enqueue_script('ph-allagents-slick');
                        wp_enqueue_script('ph-allagents-slick-init');
                    }

                    $overall = isset($widget['overall']) && !empty($widget['overall']) ? $widget['overall'] : array();
                    $rating = isset($overall->rating) ? $overall->rating : 0;
                    $stars = isset($overall->rating) ? ceil($overall->rating) : 0;
                    $votes = isset($overall->votes) ? ceil($overall->votes) : 0;
                    $header_background_colour = isset($widget['header_background_colour']) && !empty($widget['header_background_colour']) ? $widget['header_background_colour'] : '';
                    $show_reviews_for = $widget['show_reviews_for'];
                    $firm_link = $widget['firm_link'];
                    $branch_link = $widget['branch_link'];

                    echo '<div class="allagents-widget">';

                    if ( !isset($widget['display_header']) || ( isset($widget['display_header']) && $widget['display_header'] != '' ) )
                    {
                        $assets_path = str_replace( array( 'http:', 'https:' ), '', untrailingslashit( plugins_url( '/', __FILE__ ) ) ) . '/assets/';

                        $template = locate_template( array('propertyhive/allagents-header.php') );
                        if ( !$template )
                        {
                            include( dirname( PH_ALLAGENTS_PLUGIN_FILE ) . '/templates/allagents-header.php' );
                        }
                        else
                        {
                            include( $template );
                        }
                    }

                    $reviews = isset($widget['reviews']) && !empty($widget['reviews']) ? $widget['reviews'] : array();
                    if ( isset($widget['number_reviews']) && !empty($widget['number_reviews']) )
                    {
                        $reviews = array_slice($reviews, 0, (int)$widget['number_reviews']);
                    }
                    else
                    {
                        $reviews = array_slice($reviews, 0, 20);
                    }

                    $template = locate_template( array('propertyhive/allagents-reviews.php') );
                    if ( !$template )
                    {
                        include( dirname( PH_ALLAGENTS_PLUGIN_FILE ) . '/templates/allagents-reviews.php' );
                    }
                    else
                    {
                        include( $template );
                    }

                    if ( !isset($widget['display_footer']) || ( isset($widget['display_footer']) && $widget['display_footer'] != '' ) )
                    {
                        $template = locate_template( array('propertyhive/allagents-footer.php') );
                        if ( !$template )
                        {
                            include( dirname( PH_ALLAGENTS_PLUGIN_FILE ) . '/templates/allagents-footer.php' );
                        }
                        else
                        {
                            include( $template );
                        }
                    }

                    echo '</div>';

                    break;
                }
            }
        }

        return ob_get_clean();
    }

    public function load_allagents_scripts() {

        $assets_path = str_replace( array( 'http:', 'https:' ), '', untrailingslashit( plugins_url( '/', __FILE__ ) ) ) . '/assets/';

        wp_register_script( 
            'ph-allagents-slick', 
            $assets_path . 'js/slick.min.js', 
            array('jquery'), 
            '1.8.1',
            true
        );

        wp_register_script( 
            'ph-allagents-slick-init', 
            $assets_path . 'js/slick-init.js', 
            array('jquery', 'ph-allagents-slick'), 
            PH_ALLAGENTS_VERSION,
            true
        );
    }

    public function load_allagents_styles() {

        $assets_path = str_replace( array( 'http:', 'https:' ), '', untrailingslashit( plugins_url( '/', __FILE__ ) ) ) . '/assets/';

        wp_register_style( 
            'ph-allagents-slick', 
            $assets_path . 'css/slick.css', 
            array(), 
            '1.8.1'
        );

        wp_register_style( 
            'ph-allagents', 
            $assets_path . 'css/propertyhive-allagents.css', 
            array(), 
            PH_ALLAGENTS_VERSION
        );
    }
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