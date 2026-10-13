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
        'supports'           => array( 'title', 'editor', 'author' ),
        'show_in_rest'       => false,
    );

    register_post_type( 'convocatoria', $args );
}
add_action( 'init', 'sigec_registrar_cpt_convocatorias' );



/**
 * 4. Personalizar las columnas en el listado de Convocatorias
 */
function sigec_columnas_personalizadas_convocatoria( $columns ) {
    $nuevas_columnas = array();
    $nuevas_columnas['cb'] = $columns['cb']; 
    $nuevas_columnas['title'] = 'Título de la Convocatoria';
    $nuevas_columnas['date'] = $columns['date']; // <-- Fecha nativa reubicada aquí
    $nuevas_columnas['presupuesto'] = 'Presupuesto Estimado';
    $nuevas_columnas['fecha_cierre'] = 'Fecha de Cierre';
    $nuevas_columnas['estado_convocatoria'] = 'Estado';
    $nuevas_columnas['ofertas'] = 'Ofertas Recibidas';
    
    return $nuevas_columnas;
}
add_filter( 'manage_convocatoria_posts_columns', 'sigec_columnas_personalizadas_convocatoria' );

function sigec_mostrar_datos_columnas_convocatoria( $column, $post_id ) {
    switch ( $column ) {
        case 'presupuesto':
            $presupuesto = get_post_meta( $post_id, '_sigec_presupuesto', true );
            echo $presupuesto ? '$' . number_format( $presupuesto, 0, ',', '.' ) . ' COP' : '—';
            break;

        case 'fecha_cierre':
            $fecha = get_post_meta( $post_id, '_sigec_fecha_cierre', true );
            echo $fecha ? date( 'd-m-Y', strtotime( $fecha ) ) : '—';
            break;

        case 'estado_convocatoria':
            $estado = get_post_status( $post_id );
            if ( $estado == 'publish' ) {
                echo '<span style="background-color: #e6f6ea; color: #00A32A; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px;">Publicada</span>';
            } elseif ( $estado == 'draft' ) {
                echo '<span style="background-color: #f0f0f0; color: #666; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px;">Borrador</span>';
            } else {
                echo esc_html( $estado );
            }
            break;

        case 'ofertas':
            // Este dato se conectará después cuando armemos el formulario público
            echo '<span style="color: #0073aa; font-weight: 500;">0 recibidas</span>';
            break;
    }
}
add_action( 'manage_convocatoria_posts_custom_column', 'sigec_mostrar_datos_columnas_convocatoria', 10, 2 );

// Cargar la interfaz de administración para Google Drive
require_once plugin_dir_path( __FILE__ ) . 'admin/admin-drive.php';

// AGREGAR EL METABOX EN EL POST TYPE CONVOCATORIA
require_once plugin_dir_path( __FILE__ ) . 'admin/admin-metabox.php';