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

/**
 * ---------------------------------------------------
 * MAGIA DE LA TABLA DE ADMINISTRACIÓN Y FILTROS
 * ---------------------------------------------------
 */

// 1. Columnas bonitas de la tabla (Estados y colores)
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
            $estado_wp = get_post_status( $post_id );
            $fecha_cierre = get_post_meta( $post_id, '_sigec_fecha_cierre', true );
            $hoy = current_time( 'Y-m-d\TH:i' ); 

            if ( $estado_wp == 'publish' ) {
                echo '<span style="background-color: #e6f6ea; color: #00A32A; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; display: inline-block;">Publicada en web</span><br>';
                if ( $fecha_cierre && $fecha_cierre < $hoy ) {
                    echo '<span style="color: #dc2626; font-size: 12px; font-weight: 600; margin-top: 4px; display: inline-block;">Cerrada</span>';
                } else {
                    echo '<span style="color: #0073aa; font-size: 12px; font-weight: 600; margin-top: 4px; display: inline-block;">Abierta</span>';
                }
            } elseif ( $estado_wp == 'draft' ) {
                echo '<span style="background-color: #f0f0f0; color: #666; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; display: inline-block;">Borrador</span><br>';
                echo '<span style="color: #666; font-size: 12px; font-weight: 600; margin-top: 4px; display: inline-block;">Oculta</span>';
            } elseif ( $estado_wp == 'pending' ) {
                echo '<span style="background-color: #fef3c7; color: #b45309; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; display: inline-block;">Pendiente</span><br>';
                echo '<span style="color: #b45309; font-size: 12px; font-weight: 600; margin-top: 4px; display: inline-block;">Por aprobar</span>';
            } else {
                echo esc_html( $estado_wp );
            }
            break;

        case 'ofertas':
            echo '<span style="color: #0073aa; font-weight: 500;">0 recibidas</span>';
            break;
    }
}
add_action( 'manage_convocatoria_posts_custom_column', 'sigec_mostrar_datos_columnas_convocatoria', 10, 2 );

// 2. Ocultar opciones de visibilidad (Privada/Contraseña)
function sigec_ocultar_opciones_visibilidad() {
    global $post_type;
    if ( 'convocatoria' === $post_type ) {
        echo '<style>
            #visibility-radio-password, #visibility-radio-private,
            label[for="visibility-radio-password"], label[for="visibility-radio-private"],
            #post-visibility-select .inline-edit-password-input { display: none !important; }
            .components-panel__row.editor-post-visibility { display: none !important; }
        </style>';
    }
}
add_action( 'admin_head', 'sigec_ocultar_opciones_visibilidad' );

// 3. Filtros superiores en la tabla (Abiertas / Cerradas)
function sigec_agregar_enlaces_filtro_estado( $views ) {
    global $post_type;
    if ( $post_type == 'convocatoria' ) {
        $class_abierta = ( isset( $_GET['estado_sigec'] ) && $_GET['estado_sigec'] == 'abierta' ) ? 'current' : '';
        $class_cerrada = ( isset( $_GET['estado_sigec'] ) && $_GET['estado_sigec'] == 'cerrada' ) ? 'current' : '';
        $views['abiertas'] = sprintf( '<a href="%s" class="%s">🟢 Abiertas en Web</a>', admin_url( 'edit.php?post_type=convocatoria&estado_sigec=abierta' ), $class_abierta );
        $views['cerradas'] = sprintf( '<a href="%s" class="%s">🔴 Cerradas en Web</a>', admin_url( 'edit.php?post_type=convocatoria&estado_sigec=cerrada' ), $class_cerrada );
    }
    return $views;
}
add_filter( 'views_edit-convocatoria', 'sigec_agregar_enlaces_filtro_estado' );

function sigec_ejecutar_filtro_enlaces( $query ) {
    global $pagenow, $typenow;
    if ( is_admin() && $pagenow == 'edit.php' && $typenow == 'convocatoria' && isset( $_GET['estado_sigec'] ) ) {
        $hoy = current_time( 'Y-m-d\TH:i' ); 
        $estado = $_GET['estado_sigec'];
        $meta_query = $query->get( 'meta_query' ) ?: array();

        if ( $estado === 'abierta' ) {
            $query->set( 'post_status', 'publish' ); 
            $meta_query[] = array( 'key' => '_sigec_fecha_cierre', 'value' => $hoy, 'compare' => '>=', 'type' => 'DATETIME' );
        } elseif ( $estado === 'cerrada' ) {
            $query->set( 'post_status', 'publish' );
            $meta_query[] = array( 'key' => '_sigec_fecha_cierre', 'value' => $hoy, 'compare' => '<', 'type' => 'DATETIME' );
        }
        $query->set( 'meta_query', $meta_query );
    }
}
add_action( 'pre_get_posts', 'sigec_ejecutar_filtro_enlaces' );

// 4. Motor Backend AJAX (Para que el buscador funcione en vivo)
function sigec_ajax_filtrar_convocatorias() {
    $texto_busqueda = isset( $_POST['texto'] ) ? sanitize_text_field( $_POST['texto'] ) : '';
    $estado_filtro  = isset( $_POST['estado'] ) ? sanitize_text_field( $_POST['estado'] ) : 'todas';
    $hoy = current_time( 'Y-m-d\TH:i' );

    $args = array(
        'post_type'      => 'convocatoria',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        's'              => $texto_busqueda,
        'orderby'        => 'meta_value',
        'meta_key'       => '_sigec_fecha_cierre',
        'order'          => 'DESC'
    );

    if ( $estado_filtro === 'abiertas' ) {
        $args['meta_query'] = array( array( 'key' => '_sigec_fecha_cierre', 'value' => $hoy, 'compare' => '>=', 'type' => 'DATETIME' ) );
    } elseif ( $estado_filtro === 'cerradas' ) {
        $args['meta_query'] = array( array( 'key' => '_sigec_fecha_cierre', 'value' => $hoy, 'compare' => '<', 'type' => 'DATETIME' ) );
    }

    $query = new WP_Query( $args );
    echo sigec_generar_html_tarjetas( $query, $hoy );
    wp_die();
}
add_action( 'wp_ajax_sigec_filtrar_convocatorias', 'sigec_ajax_filtrar_convocatorias' );
add_action( 'wp_ajax_nopriv_sigec_filtrar_convocatorias', 'sigec_ajax_filtrar_convocatorias' );

// 5. Redirección de la Vista Individual (Single)
function sigec_cargar_plantilla_individual( $template ) {
    global $post;
    if ( $post->post_type === 'convocatoria' ) {
        $plugin_template = plugin_dir_path( __FILE__ ) . 'public/single-convocatoria.php';
        if ( file_exists( $plugin_template ) ) return $plugin_template;
    }
    return $template;
}
add_filter( 'single_template', 'sigec_cargar_plantilla_individual' );

// ==============================================================
// 6. ENLACE VITAL: CARGAR EL CATÁLOGO (EL QUE ARREGLA EL TEXTO PLANO)
// ==============================================================
require_once plugin_dir_path( __FILE__ ) . 'public/public-shortcodes.php';

// Cargar la interfaz de administración para Google Drive
require_once plugin_dir_path( __FILE__ ) . 'admin/admin-drive.php';

// AGREGAR EL METABOX EN EL POST TYPE CONVOCATORIA
require_once plugin_dir_path( __FILE__ ) . 'admin/admin-metabox.php';