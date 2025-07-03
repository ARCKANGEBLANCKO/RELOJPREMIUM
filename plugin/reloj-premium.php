<?php
/*
Plugin Name: Reloj Premium HR
Description: Plugin para registrar horas de entrada y salida para el departamento de RRHH.
Version: 1.0
Author: Equipo RRHH
*/

// Creaci\xC3\xB3n de la tabla al activar el plugin
function rp_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rp_clock';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        clock_time datetime NOT NULL,
        action varchar(10) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'rp_install' );

// Formulario y manejo de registro de marcas
function rp_handle_clock() {
    if ( ! is_user_logged_in() ) {
        return;
    }
    if ( isset( $_POST['rp_clock_nonce'] ) && wp_verify_nonce( $_POST['rp_clock_nonce'], 'rp_clock' ) ) {
        if ( isset( $_POST['rp_clock_in'] ) || isset( $_POST['rp_clock_out'] ) ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'rp_clock';
            $action = isset( $_POST['rp_clock_in'] ) ? 'in' : 'out';
            $wpdb->insert( $table_name, array(
                'user_id'    => get_current_user_id(),
                'clock_time' => current_time( 'mysql' ),
                'action'     => $action,
            ) );
        }
    }
}
add_action( 'init', 'rp_handle_clock' );

function rp_clock_form() {
    if ( ! is_user_logged_in() ) {
        return '<p>Debe iniciar sesi\xC3\xB3n para registrar la hora.</p>';
    }
    $html  = '<form method="post">';
    $html .= '<input type="hidden" name="rp_clock_nonce" value="' . esc_attr( wp_create_nonce( 'rp_clock' ) ) . '" />';
    $html .= '<button type="submit" name="rp_clock_in" value="1">Marcar Entrada</button> ';
    $html .= '<button type="submit" name="rp_clock_out" value="1">Marcar Salida</button>';
    $html .= '</form>';
    return $html;
}
add_shortcode( 'rp_clock', 'rp_clock_form' );

// Pagina de administraci\xC3\xB3n para ver las marcas
function rp_admin_menu() {
    add_menu_page( 'Reloj Premium', 'Reloj Premium', 'manage_options', 'rp-clock', 'rp_admin_page' );
}
add_action( 'admin_menu', 'rp_admin_menu' );

function rp_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rp_clock';
    $entries = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY clock_time DESC LIMIT 100" );
    echo '<div class="wrap"><h1>Registros de Marcas</h1>';
    echo '<table class="widefat"><thead><tr><th>Usuario</th><th>Acci\xC3\xB3n</th><th>Fecha y hora</th></tr></thead><tbody>';
    foreach ( $entries as $entry ) {
        $user = get_user_by( 'id', $entry->user_id );
        echo '<tr><td>' . esc_html( $user ? $user->user_login : $entry->user_id ) . '</td><td>' . esc_html( $entry->action ) . '</td><td>' . esc_html( $entry->clock_time ) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}
