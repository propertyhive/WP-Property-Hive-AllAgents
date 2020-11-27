<?php
/**
 * Installation related functions and actions.
 *
 * @author 		PropertyHive
 * @category 	Admin
 * @package 	PropertyHive/Classes
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'PH_AllAgents_Install' ) ) :

/**
 * PH_AllAgents_Install Class
 */
class PH_AllAgents_Install {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		register_activation_hook( PH_ALLAGENTS_PLUGIN_FILE, array( $this, 'install' ) );
		register_deactivation_hook( PH_ALLAGENTS_PLUGIN_FILE, array( $this, 'deactivate' ) );
		register_uninstall_hook( PH_ALLAGENTS_PLUGIN_FILE, array( 'PH_AllAgents_Install', 'uninstall' ) );

		add_action( 'admin_init', array( $this, 'install_actions' ) );
		add_action( 'admin_init', array( $this, 'check_version' ), 5 );
	}

	/**
	 * check_version function.
	 *
	 * @access public
	 * @return void
	 */
	public function check_version() {
	    if ( 
	    	! defined( 'IFRAME_REQUEST' ) && 
	    	( get_option( 'propertyhive_allagents_version' ) != PHAA()->version ) 
	    ) {
			$this->install();
		}
	}

	/**
	 * Deactivate Property Hive AllAgents Add-On
	 */
	public function deactivate() {

		$timestamp = wp_next_scheduled( 'phallagentscronhook' );
        wp_unschedule_event($timestamp, 'phallagentscronhook' );
        wp_clear_scheduled_hook('phallagentscronhook');

	}

	/**
	 * Uninstall Property Hive AllAgents Add-On
	 */
	public function uninstall() {

		$timestamp = wp_next_scheduled( 'phallagentscronhook' );
        wp_unschedule_event($timestamp, 'phallagentscronhook' );
        wp_clear_scheduled_hook('phallagentscronhook');

        delete_option( 'propertyhive_allagents' );
	}

	/**
	 * Install actions
	 */
	public function install_actions() {



	}

	/**
	 * Install Property Hive AllAgents Add-On
	 */
	public function install() {
        
		$this->create_cron();

        // Update version
        update_option( 'propertyhive_allagents_version', PHAA()->version );
	}

	/**
	 * Setup cron
	 *
	 * Sets up the automated cron to cache reviews
	 *
	 * @access public
	 */
	public function create_cron() {
	    
        $timestamp = wp_next_scheduled( 'phallagentscronhook' );
        wp_unschedule_event($timestamp, 'phallagentscronhook' );
        wp_clear_scheduled_hook('phallagentscronhook');
        
        $next_schedule = time() - 60;
        wp_schedule_event( $next_schedule, 'hourly', 'phallagentscronhook' );

    }

}

endif;

return new PH_AllAgents_Install();