<?php
/**
 * Plugin Name: SIGEC-WP
 * Plugin URI: https://github.com/DiegoM29Z/SIGEC-WP
 * Description: Sistema integral de convocatorias para WordPress. Módulos de administración (Rectoría) y portal de recepción de ofertas (Proveedores) con integración a Google Drive.
 * Version:     1.0.0
 * Author:      Diego Muñoz y Nicol Anacona
 * License:     GPL2
 * Text Domain: sigec-wp
 */

// Evitar el acceso directo al archivo por seguridad
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente
}

// Aquí comenzaremos a escribir la lógica en el siguiente paso...
function sigec_registrar_cpt_convocatorias() {
    $labels = array(
        'name'               => 'Convocatorias',
        'singular_name'      => 'Convocatoria',
        'menu_name'          => 'SIGEC - Compras',
        'name_admin_bar'     => 'Convocatoria',
        'add_new'            => 'Añadir Convocatoria',
        'add_new_item'       => 'Añadir Nueva Convocatoria',
        'new_item'           => 'Nueva Convocatoria',
        'edit_item'          => 'Editar Convocatoria',
        'view_item'          => 'Ver Convocatoria',
        'all_items'          => 'Todas las Convocatorias',
        'search_items'       => 'Buscar Convocatorias',
        'not_found'          => 'No se encontraron convocatorias.',
    );

   $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'menu_icon'          => 'dashicons-portfolio',
        'menu_position'      => 26, // Controla la posición exacta en el menú lateral
        'supports'           => array( 'title', 'editor', 'author' ),
        'show_in_rest'       => true,
    );
    
    register_post_type( 'convocatoria', $args );
}
add_action( 'init', 'sigec_registrar_cpt_convocatorias' );