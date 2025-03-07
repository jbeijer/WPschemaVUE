<?php
/**
 * Avinstallationsprocess för WPschemaVUE
 *
 * Denna fil körs när pluginet avinstalleras (tas bort) från WordPress.
 * Den hanterar borttagning av data som pluginet har skapat.
 *
 * @package WPschemaVUE
 */

// Om WordPress inte anropar denna fil, avsluta
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Inkludera deactivator-klassen
require_once plugin_dir_path(__FILE__) . 'includes/class-deactivator.php';

// Anropa uninstall-metoden
WPschemaVUE_Deactivator::uninstall();
