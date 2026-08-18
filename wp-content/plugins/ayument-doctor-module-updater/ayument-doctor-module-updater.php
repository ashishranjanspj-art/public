<?php
/**
 * Plugin Name: AyuMent Doctor Module Updater
 * Description: Safely replaces the AyuMent Core doctor module with the supplied tested version, keeps a backup, then deactivates itself.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

register_activation_hook( __FILE__, 'ayument_doctor_module_updater_run' );

function ayument_doctor_module_updater_run() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        wp_die( 'You do not have permission to update the AyuMent Doctor module.' );
    }

    $target = WP_PLUGIN_DIR . '/ayument-core/ayument-doctors.php';
    $source = plugin_dir_path( __FILE__ ) . 'ayument-doctors.php';

    if ( ! file_exists( $source ) ) {
        wp_die( 'The replacement doctor module could not be found in the updater package.' );
    }

    if ( ! file_exists( $target ) ) {
        wp_die( 'The existing AyuMent Core doctor module was not found at: ' . esc_html( $target ) );
    }

    // Keep a timestamped backup before replacing the live module.
    $backup = WP_PLUGIN_DIR . '/ayument-core/ayument-doctors-backup-' . gmdate( 'Ymd-His' ) . '.php';
    if ( ! @copy( $target, $backup ) ) {
        wp_die( 'Could not create a backup of the existing doctor module. No changes were made.' );
    }

    if ( ! @copy( $source, $target ) ) {
        wp_die( 'Could not replace the doctor module. Your original file is still intact.' );
    }

    // Deactivate this one-time updater. AyuMent Core remains active.
    deactivate_plugins( plugin_basename( __FILE__ ) );

    wp_safe_redirect( admin_url( 'plugins.php?ayument_doctor_updated=1' ) );
    exit;
}

add_action( 'admin_notices', function () {
    if ( ! empty( $_GET['ayument_doctor_updated'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>AyuMent Doctor Module updated successfully.</strong> A backup of the previous module was created automatically. The updater has been deactivated.</p></div>';
    }
} );
