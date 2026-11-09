<?php
/**
 * SIGEC-WP · Procesador de postulaciones y subida de ofertas a Google Drive.
 *
 * Flujo:
 *   1. Registra el Custom Post Type interno `propuesta`.
 *   2. Escucha el formulario de [sigec_formulario_postulacion] en `template_redirect`.
 *   3. Valida sesión, nonce `enviar_oferta_sigec`, datos y los 3 archivos PDF.
 *   4. Conecta con `SIGEC_Google_Drive` y sube RUT, Cédula y Propuesta Técnica.
 *   5. Guarda el registro con `wp_insert_post` (post_type: `propuesta`) y sus metadatos.
 *   6. Redirige con `?exito_postulacion=1` al panel o `?error_postulacion=...` al formulario.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SIGEC_Postulacion {

    public function __construct() {
        add_action( 'init', array( $this, 'registrar_cpt_propuestas' ) );
        add_action( 'template_redirect', array( $this, 'procesar_envio_oferta' ) );
    }

    /**
     * Registra el tipo de contenido `propuesta` para almacenar las ofertas.
     */
    public function registrar_cpt_propuestas() {
        register_post_type( 'propuesta', array(
            'labels' => array(
                'name'          => 'Propuestas Recibidas',
                'singular_name' => 'Propuesta',
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => 'edit.php?post_type=convocatoria',
            'supports'     => array( 'title', 'author' ),
        ) );
    }

    /**
     * Procesa el formulario de postulación cuando el proveedor envía su oferta.
     */
    public function procesar_envio_oferta() {
        // 1. Verificar si se envió el formulario con su nonce válido
        if ( ! isset( $_POST['sigec_oferta_nonce'] ) || ! wp_verify_nonce( $_POST['sigec_oferta_nonce'], 'enviar_oferta_sigec' ) ) {
            return;
        }

        // 2. Verificar que el proveedor tenga sesión activa
        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( home_url( '/acceso-proveedores/' ) );
            exit;
        }

        $user_id         = get_current_user_id();
        $convocatoria_id = isset( $_POST['convocatoria_id'] ) ? intval( $_POST['convocatoria_id'] ) : 0;
        $valor_ofertado  = isset( $_POST['valor_ofertado'] ) ? sanitize_text_field( $_POST['valor_ofertado'] ) : '';

        // Validar convocatoria y valor ofertado
        if ( ! $convocatoria_id || get_post_type( $convocatoria_id ) !== 'convocatoria' || empty( $valor_ofertado ) ) {
            $this->redirigir_con_error( $convocatoria_id, 'datos_incompletos' );
        }

        // Validar que la convocatoria siga abierta
        $fecha_cierre = get_post_meta( $convocatoria_id, '_sigec_fecha_cierre', true );
        $hoy          = current_time( 'Y-m-d\TH:i' );
        if ( $fecha_cierre && $fecha_cierre < $hoy ) {
            $this->redirigir_con_error( $convocatoria_id, 'convocatoria_cerrada' );
        }

        // 3. Validar que los 3 archivos PDF fueron cargados sin errores
        $archivos_requeridos = array( 'doc_rut', 'doc_cedula', 'doc_propuesta' );
        foreach ( $archivos_requeridos as $campo ) {
            if ( empty( $_FILES[ $campo ]['name'] ) || $_FILES[ $campo ]['error'] !== UPLOAD_ERR_OK ) {
                $this->redirigir_con_error( $convocatoria_id, 'archivos_faltantes' );
            }

            // Comprobar extensión y tipo MIME PDF
            $validar_tipo = wp_check_filetype( $_FILES[ $campo ]['name'] );
            if ( strtolower( $validar_tipo['ext'] ) !== 'pdf' ) {
                $this->redirigir_con_error( $convocatoria_id, 'formato_invalido' );
            }
        }

        // 4. Conectar con Google Drive
        require_once plugin_dir_path( __FILE__ ) . 'class-sigec-drive.php';
        $drive = new SIGEC_Google_Drive();

        if ( ! $drive->conectar() ) {
            $this->redirigir_con_error( $convocatoria_id, 'drive_desconectado' );
        }

        // 5. Subir cada uno de los 3 PDFs con nombres dinámicos
        $nombre_rut       = "RUT_Prov{$user_id}_Conv{$convocatoria_id}.pdf";
        $nombre_cedula    = "Cedula_Prov{$user_id}_Conv{$convocatoria_id}.pdf";
        $nombre_propuesta = "Propuesta_Prov{$user_id}_Conv{$convocatoria_id}.pdf";

        $subida_rut       = $drive->subir_documento_licitacion( $_FILES['doc_rut']['tmp_name'], $nombre_rut );
        $subida_cedula    = $drive->subir_documento_licitacion( $_FILES['doc_cedula']['tmp_name'], $nombre_cedula );
        $subida_propuesta = $drive->subir_documento_licitacion( $_FILES['doc_propuesta']['tmp_name'], $nombre_propuesta );

        if ( empty( $subida_rut['success'] ) || empty( $subida_cedula['success'] ) || empty( $subida_propuesta['success'] ) ) {
            $this->redirigir_con_error( $convocatoria_id, 'error_subida_drive' );
        }

        // 6. Guardar la propuesta en la base de datos de WordPress
        $razon_social = get_user_meta( $user_id, 'sigec_razon_social', true ) ?: wp_get_current_user()->display_name;
        $titulo_post  = sprintf( 'Oferta de %s - Convocatoria #%d', $razon_social, $convocatoria_id );

        $propuesta_id = wp_insert_post( array(
            'post_type'   => 'propuesta',
            'post_title'  => $titulo_post,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ) );

        if ( is_wp_error( $propuesta_id ) || ! $propuesta_id ) {
            $this->redirigir_con_error( $convocatoria_id, 'error_bd' );
        }

        // Guardar metadatos de la propuesta
        update_post_meta( $propuesta_id, 'convocatoria_id', $convocatoria_id );
        update_post_meta( $propuesta_id, 'valor_ofertado', $valor_ofertado );
        update_post_meta( $propuesta_id, 'link_rut', esc_url_raw( $subida_rut['link'] ) );
        update_post_meta( $propuesta_id, 'link_cedula', esc_url_raw( $subida_cedula['link'] ) );
        update_post_meta( $propuesta_id, 'link_propuesta', esc_url_raw( $subida_propuesta['link'] ) );

        // 7. Redirigir al panel del proveedor con mensaje de éxito
        $url_exito = add_query_arg( 'exito_postulacion', '1', home_url( '/panel-de-proveedores/' ) );
        wp_safe_redirect( $url_exito );
        exit;
    }

    /**
     * Redirige de vuelta al formulario de postulación con un código de error.
     */
    private function redirigir_con_error( $convocatoria_id, $codigo_error ) {
        $url_error = add_query_arg( array(
            'convocatoria_id'   => $convocatoria_id,
            'error_postulacion' => $codigo_error,
        ), home_url( '/postulacion/' ) );

        wp_safe_redirect( $url_error );
        exit;
    }
}

new SIGEC_Postulacion();

