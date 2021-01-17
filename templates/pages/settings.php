<?php
/**
 * Displays the settings page.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

// Determines which tab to display.
$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general_settings';

?>
<div class="wrap">
	<div id="revisr_settings">
		<h1><?php _e( 'Revisr - Settings', 'revisr' ); ?></h1>
		<?php settings_errors(); ?>


		<h2 class="nav-tab-wrapper">
		    <a href="?page=revisr_settings&tab=general_settings" class="nav-tab <?php echo $active_tab == 'general_settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'General', 'revisr' ); ?></a>
		    <a href="?page=revisr_settings&tab=remote_settings" class="nav-tab <?php echo $active_tab == 'remote_settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Remote', 'revisr' ); ?></a>
		    <a href="?page=revisr_settings&tab=database_dump_settings" class="nav-tab <?php echo $active_tab == 'database_dump_settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Database Dump', 'revisr' ); ?></a>
		    <a href="?page=revisr_settings&tab=help" class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Help', 'revisr' ); ?></a>
		</h2>

		<form class="revisr-settings-form" method="post" action="options.php">
			<?php

				// Renders the settings page.
				switch ( $active_tab ) {

					case 'general_settings':
						settings_fields( 'revisr_general_settings' );
	            		do_settings_sections( 'revisr_general_settings' );
	            		break;

            		case 'remote_settings':
			            settings_fields( 'revisr_remote_settings' );
	            		do_settings_sections( 'revisr_remote_settings' );
	            		break;

            		case 'database_dump_settings':
			            settings_fields( 'revisr_db_dump_settings' );
	            		do_settings_sections( 'revisr_db_dump_settings' );
	            		break;

            		case 'help':
            			include REVISR_PATH . 'templates/pages/help.php';
            			break;
				}

				if ( 'help' !== $active_tab ) {
					submit_button();
				}
		    ?>
		</form>
	</div>
</div>
