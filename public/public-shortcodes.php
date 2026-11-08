<?php
/**
 * SIGEC-WP · Shortcodes públicos (despachador)
 *
 * Cada shortcode solo prepara sus datos y delega el HTML a public/views/.
 *   - Estilos:            assets/css/sigec-public.css
 *   - Buscador AJAX:      assets/js/sigec-buscador.js
 *   - Login / Registro:   includes/class-sigec-auth.php
 *   - Encolado de assets: sigec_encolar_assets_publicos() en sigec-wp.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Carga una vista de public/views/ y devuelve su HTML.
 *
 * @param string $vista Nombre del archivo sin extensión (ej. 'vista-catalogo').
 * @param array  $datos Variables que estarán disponibles dentro de la vista.
 * @return string
 */
function sigec_cargar_vista( $vista, $datos = array() ) {
    $archivo = __DIR__ . '/views/' . $vista . '.php';
    if ( ! file_exists( $archivo ) ) {
        return '';
    }

    extract( $datos, EXTR_SKIP );
    ob_start();
    include $archivo;
    return ob_get_clean();
}

/**
 * Respaldo: asegura que los estilos (y opcionalmente el JS) estén encolados
 * aunque el shortcode se use en un lugar que no detecta has_shortcode()
 * (por ejemplo, un widget).
 */
function sigec_asegurar_assets( $con_buscador = false ) {
    wp_enqueue_style( 'sigec-public' );
    if ( $con_buscador ) {
        wp_enqueue_script( 'sigec-buscador' );
    }
}


/* ==============================================================
 * 1. SHORTCODE: CATÁLOGO PÚBLICO
 * Uso: [sigec_convocatorias_abiertas]
 * ============================================================== */
function sigec_renderizar_catalogo_publico() {
    sigec_asegurar_assets( true );

    // Consulta inicial (carga todas)
    $query = new WP_Query( array(
        'post_type'      => 'convocatoria',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_key'       => '_sigec_fecha_cierre',
        'orderby'        => 'meta_value',
        'order'          => 'DESC',
    ) );
    $hoy = current_time( 'Y-m-d\\TH:i' );

    return sigec_cargar_vista( 'vista-catalogo', array(
        'html_tarjetas' => sigec_generar_html_tarjetas( $query, $hoy ),
    ) );
}
add_shortcode( 'sigec_convocatorias_abiertas', 'sigec_renderizar_catalogo_publico' );

/**
 * Prepara los datos de cada convocatoria y dibuja las tarjetas.
 * Usada por la carga normal del catálogo y por el AJAX del buscador
 * (sigec_ajax_filtrar_convocatorias() en sigec-wp.php).
 */
function sigec_generar_html_tarjetas( $query, $hoy ) {
    $tarjetas = array();
    $logueado = is_user_logged_in();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id      = get_the_ID();
            $presupuesto  = get_post_meta( $post_id, '_sigec_presupuesto', true );
            $fecha_cierre = get_post_meta( $post_id, '_sigec_fecha_cierre', true );

            // Lógica de Abierta/Cerrada
            $estado  = ( $fecha_cierre && $fecha_cierre >= $hoy ) ? 'Abierta' : 'Cerrada';
            $cerrada = ( $estado === 'Cerrada' );

            $tarjetas[] = array(
                'post_id'                => $post_id,
                'titulo'                 => get_the_title(),
                'permalink'              => get_permalink(),
                'estado'                 => $estado,
                'clase_badge'            => $cerrada ? 'cerrada' : 'abierta',
                'presupuesto_formateado' => $presupuesto ? '$' . number_format( $presupuesto, 0, ',', '.' ) . ' COP' : 'No definido',
                'fecha_formateada'       => $fecha_cierre ? date_i18n( 'd \d\e F, Y', strtotime( $fecha_cierre ) ) : 'Sin fecha',
                'clase_btn_participar'   => $cerrada ? 'sigec-btn-participar sigec-btn-disabled' : 'sigec-btn-participar',
                'texto_btn_participar'   => $cerrada ? 'Cerrada' : 'Participar <span class="dashicons dashicons-arrow-right-alt" style="font-size: 16px; width: 16px; height: 16px; margin-top:2px;"></span>',
                'logueado'               => $logueado,
                // Con sesión: formulario de postulación
                'url_postulacion'        => esc_url( add_query_arg( 'convocatoria_id', $post_id, site_url( '/postulacion/' ) ) ),
                // Sin sesión: login CON MEMORIA de la convocatoria
                'url_login'              => esc_url( add_query_arg( array(
                    'redirect_to'     => 'postulacion',
                    'convocatoria_id' => $post_id,
                ), home_url( '/acceso-proveedores/' ) ) ),
            );
        }
        wp_reset_postdata();
    }

    return sigec_cargar_vista( 'vista-tarjetas', array( 'tarjetas' => $tarjetas ) );
}


/* ==============================================================
 * 2. SHORTCODE: ACCESO DE PROVEEDORES (Login / Registro)
 * Uso: [sigec_acceso_proveedores]
 * El procesamiento de los formularios está en includes/class-sigec-auth.php
 * ============================================================== */
function sigec_renderizar_acceso_proveedores() {
    if ( is_user_logged_in() && ! is_admin() && ! wp_is_json_request() ) {
        wp_safe_redirect( home_url( '/panel-de-proveedores/' ) );
        exit;
    }

    sigec_asegurar_assets();

    // Leer mensajes desde la URL (los agrega SIGEC_Autenticacion)
    $mensaje_registro = '';
    $tipo_mensaje     = '';
    if ( isset( $_GET['exito_reg'] ) ) {
        $mensaje_registro = '¡Registro exitoso! Tu cuenta está en revisión. La institución validará tu NIT para habilitar tu acceso.';
        $tipo_mensaje     = 'exito';
    } elseif ( isset( $_GET['error_reg'] ) && $_GET['error_reg'] == 'existe' ) {
        $mensaje_registro = 'Ese correo ya está registrado. Por favor inicia sesión.';
        $tipo_mensaje     = 'error';
    }

    $mensaje_login = '';
    if ( isset( $_GET['error_login'] ) ) {
        if ( $_GET['error_login'] == 'pendiente' ) {
            $mensaje_login = 'Tu cuenta está registrada, pero aún PENDIENTE de validación por la Rectora.';
        } else {
            $mensaje_login = 'Credenciales incorrectas. Inténtalo de nuevo.';
        }
    }

    return sigec_cargar_vista( 'vista-acceso', array(
        'mensaje_login'    => $mensaje_login,
        'mensaje_registro' => $mensaje_registro,
        'tipo_mensaje'     => $tipo_mensaje,
    ) );
}
add_shortcode( 'sigec_acceso_proveedores', 'sigec_renderizar_acceso_proveedores' );


/* ==============================================================
 * 3. SHORTCODE: PANEL DEL PROVEEDOR (DASHBOARD)
 * Uso: [sigec_panel_proveedor]
 * ============================================================== */
function sigec_renderizar_panel_proveedor() {
    // Si NO está logueado, y no estamos en el editor de bloques (JSON request) o admin
    if ( ! is_user_logged_in() && ! is_admin() && ! wp_is_json_request() ) {
        wp_safe_redirect( home_url( '/acceso-proveedores/' ) );
        exit;
    }

    sigec_asegurar_assets();

    $current_user = wp_get_current_user();

    return sigec_cargar_vista( 'vista-panel', array(
        'current_user' => $current_user,
        'razon_social' => get_user_meta( $current_user->ID, 'sigec_razon_social', true ) ?: $current_user->display_name,
        'nit'          => get_user_meta( $current_user->ID, 'sigec_nit', true ) ?: 'No registrado',
        'telefono'     => get_user_meta( $current_user->ID, 'sigec_telefono', true ) ?: 'No registrado',
    ) );
}
add_shortcode( 'sigec_panel_proveedor', 'sigec_renderizar_panel_proveedor' );


/* ==============================================================
 * 4. SHORTCODE: FORMULARIO DE POSTULACIÓN
 * Uso: [sigec_formulario_postulacion]
 * ============================================================== */
function sigec_renderizar_formulario_postulacion() {
    // Seguridad: Redirigir si no está logueado
    if ( ! is_user_logged_in() && ! is_admin() && ! wp_is_json_request() ) {
        wp_safe_redirect( home_url( '/acceso-proveedores/' ) );
        exit;
    }

    // Obtener el ID de la convocatoria desde la URL
    $convocatoria_id = isset( $_GET['convocatoria_id'] ) ? intval( $_GET['convocatoria_id'] ) : 0;

    if ( ! $convocatoria_id || get_post_type( $convocatoria_id ) !== 'convocatoria' ) {
        return '<p style="padding: 40px; text-align: center;">Error: No se ha seleccionado una convocatoria válida para participar.</p>';
    }

    sigec_asegurar_assets();

    $fecha_cierre = get_post_meta( $convocatoria_id, '_sigec_fecha_cierre', true );

    return sigec_cargar_vista( 'vista-postulacion', array(
        'convocatoria_id'  => $convocatoria_id,
        'titulo'           => get_the_title( $convocatoria_id ),
        'fecha_formateada' => $fecha_cierre ? date_i18n( 'd \d\e F, Y - h:i A', strtotime( $fecha_cierre ) ) : 'No definida',
    ) );
}
add_shortcode( 'sigec_formulario_postulacion', 'sigec_renderizar_formulario_postulacion' );
