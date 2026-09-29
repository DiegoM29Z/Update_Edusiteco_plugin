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
        'show_in_rest'       => true,
    );

    register_post_type( 'convocatoria', $args );
}
add_action( 'init', 'sigec_registrar_cpt_convocatorias' );
/**
 * 1. Crear los Meta Boxes en el panel de Convocatorias
 */
function sigec_agregar_metaboxes_convocatoria() {
    add_meta_box(
        'sigec_datos_convocatoria',          // ID del Meta Box
        'Datos de la Licitación',            // Título visible para la rectora
        'sigec_renderizar_metabox_datos',    // Función que dibuja los campos (HTML)
        'convocatoria',                      // Pantalla donde aparecerá (nuestro CPT)
        'normal',                            // Posición
        'high'                               // Prioridad
    );
}
add_action( 'add_meta_boxes', 'sigec_agregar_metaboxes_convocatoria' );

/**
 * 2. Dibujar los campos (HTML) del Meta Box
 */
function sigec_renderizar_metabox_datos( $post ) {
    // Generar un token de seguridad (Nonce)
    wp_nonce_field( 'sigec_guardar_datos', 'sigec_nonce' );

    // Recuperar los valores actuales si ya existen en la base de datos
    $presupuesto = get_post_meta( $post->ID, '_sigec_presupuesto', true );
    $fecha_cierre = get_post_meta( $post->ID, '_sigec_fecha_cierre', true );

    // Imprimir los campos HTML
    ?>
    <div style="display: flex; gap: 20px; padding: 10px 0;">
        <div style="flex: 1;">
            <label for="sigec_presupuesto"><strong>Presupuesto Estimado (COP):</strong></label><br>
            <input type="number" id="sigec_presupuesto" name="sigec_presupuesto" value="<?php echo esc_attr( $presupuesto ); ?>" style="width: 100%; margin-top: 5px;" placeholder="Ej: 120000000">
        </div>
        <div style="flex: 1;">
            <label for="sigec_fecha_cierre"><strong>Fecha de Cierre:</strong></label><br>
            <input type="date" id="sigec_fecha_cierre" name="sigec_fecha_cierre" value="<?php echo esc_attr( $fecha_cierre ); ?>" style="width: 100%; margin-top: 5px;">
        </div>
    </div>
    <p style="color: #666; font-size: 12px;">Estos datos serán visibles para los proveedores en la página pública de la convocatoria.</p>
    <?php
}

/**
 * 3. Guardar los datos cuando la rectora haga clic en "Publicar" o "Actualizar"
 */
function sigec_guardar_metaboxes_convocatoria( $post_id ) {
    // Verificaciones de seguridad
    if ( ! isset( $_POST['sigec_nonce'] ) || ! wp_verify_nonce( $_POST['sigec_nonce'], 'sigec_guardar_datos' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Guardar el Presupuesto
    if ( isset( $_POST['sigec_presupuesto'] ) ) {
        update_post_meta( $post_id, '_sigec_presupuesto', sanitize_text_field( $_POST['sigec_presupuesto'] ) );
    }

    // Guardar la Fecha de Cierre
    if ( isset( $_POST['sigec_fecha_cierre'] ) ) {
        update_post_meta( $post_id, '_sigec_fecha_cierre', sanitize_text_field( $_POST['sigec_fecha_cierre'] ) );
    }
}
add_action( 'save_post', 'sigec_guardar_metaboxes_convocatoria' );