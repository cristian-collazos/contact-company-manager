<?php
/**
 * Plugin Name: Contacts & Companies Manager
 * Description: Plugin to manage contacts and companies in WordPres.
 * Version: 1.0
 * Author: Cristian Collazos
 * Text Domain: contact-company-manager
 */

if (!defined('ABSPATH')) {
    exit; // Evita el acceso directo
}

// Incluir la clase principal del plugin
require_once plugin_dir_path(__FILE__) . 'includes/class-gcc-plugin.php';

// Inicializar el plugin
new GCC_Plugin();

// Registrar hooks de activación y desactivación
register_activation_hook(__FILE__, array('GCC_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('GCC_Plugin', 'deactivate'));
