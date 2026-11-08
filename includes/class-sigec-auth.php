<?php
/**
 * SIGEC-WP · Motor de autenticación de proveedores.
 *
 * Procesa (antes de cargar la página, en template_redirect) los formularios
 * del shortcode [sigec_acceso_proveedores]:
 *   A. Registro de proveedor (la cuenta nace en estado 'pendiente').
 *   B. Inicio de sesión (bloquea cuentas 'pendiente' y aplica la
 *      redirección inteligente hacia /postulacion/?convocatoria_id=X).
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SIGEC_Autenticacion {

    public function __construct() {
        add_action( 'template_redirect', array( $this, 'procesar_formularios_acceso' ) );
        add_action( 'template_redirect', array( $this, 'verificar_control_acceso' ) );
    }

    /**
     * Redirecciones de control de acceso seguras (antes de enviar HTML).
     * Evita el error "headers already sent" que ocurría dentro de los shortcodes.
     */
    public function verificar_control_acceso() {
        if ( is_admin() || wp_is_json_request() ) {
            return;
        }

        global $post;
        if ( ! is_singular() || ! isset( $post->post_content ) ) {
            return;
        }

        $logueado = is_user_logged_in();

        // 1. Si ya tiene sesión e ingresa a /acceso-proveedores/ -> va al panel
        if ( $logueado && has_shortcode( $post->post_content, 'sigec_acceso_proveedores' ) ) {
            wp_safe_redirect( home_url( '/panel-de-proveedores/' ) );
            exit;
        }

        // 2. Si NO tiene sesión e ingresa a /panel-de-proveedores/ -> va al login
        if ( ! $logueado && has_shortcode( $post->post_content, 'sigec_panel_proveedor' ) ) {
            wp_safe_redirect( home_url( '/acceso-proveedores/' ) );
            exit;
        }

        // 3. Si NO tiene sesión e ingresa a /postulacion/ -> va al login con memoria
        if ( ! $logueado && has_shortcode( $post->post_content, 'sigec_formulario_postulacion' ) ) {
            $convocatoria_id = isset( $_GET['convocatoria_id'] ) ? intval( $_GET['convocatoria_id'] ) : 0;
            $url_login = add_query_arg( array(
                'redirect_to'     => 'postulacion',
                'convocatoria_id' => $convocatoria_id,
            ), home_url( '/acceso-proveedores/' ) );
            wp_safe_redirect( $url_login );
            exit;
        }
    }

    /**
     * Punto de entrada: decide qué formulario se envió.
     */
    public function procesar_formularios_acceso() {

        // A. PROCESAR EL REGISTRO
        if ( isset( $_POST['sigec_registro_submit'] ) && wp_verify_nonce( $_POST['sigec_registro_nonce'], 'sigec_registrar_proveedor' ) ) {
            $this->procesar_registro();
        }

        // B. PROCESAR EL LOGIN
        if ( isset( $_POST['sigec_login_submit'] ) && wp_verify_nonce( $_POST['sigec_login_nonce'], 'sigec_iniciar_sesion' ) ) {
            $this->procesar_login();
        }
    }

    /**
     * A. Registro de un nuevo proveedor.
     */
    private function procesar_registro() {
        $email    = sanitize_email( $_POST['reg_email'] );
        $password = isset( $_POST['reg_password'] ) ? $_POST['reg_password'] : '';

        // Validación de longitud mínima de contraseña en el servidor
        if ( strlen( $password ) < 8 ) {
            wp_redirect( add_query_arg( 'error_reg', 'password_corta', remove_query_arg( array( 'exito_reg', 'error_login' ), wp_get_referer() ) ) );
            exit;
        }

        if ( email_exists( $email ) || username_exists( $email ) ) {
            wp_redirect( add_query_arg( 'error_reg', 'existe', remove_query_arg( array( 'exito_reg', 'error_login' ), wp_get_referer() ) ) );
            exit;
        }

        $user_id = wp_create_user( $email, $password, $email );

        if ( ! is_wp_error( $user_id ) ) {
            // SOLUCIÓN VULNERABILIDAD: La cuenta nace bloqueada ('pendiente')
            update_user_meta( $user_id, 'sigec_estado_cuenta', 'pendiente' );

            update_user_meta( $user_id, 'sigec_razon_social', sanitize_text_field( $_POST['reg_razon'] ) );
            update_user_meta( $user_id, 'sigec_nit', sanitize_text_field( $_POST['reg_nit'] ) );
            update_user_meta( $user_id, 'sigec_rep_legal', sanitize_text_field( $_POST['reg_rep'] ) );
            update_user_meta( $user_id, 'sigec_telefono', sanitize_text_field( $_POST['reg_telefono'] ) );

            $user = new WP_User( $user_id );
            $user->set_role( 'subscriber' ); // Más adelante crearemos el rol 'proveedor'

            wp_redirect( add_query_arg( 'exito_reg', '1', remove_query_arg( array( 'error_reg', 'error_login' ), wp_get_referer() ) ) );
            exit;
        }
    }

    /**
     * B. Inicio de sesión de un proveedor.
     */
    private function procesar_login() {
        $user_verify = wp_signon( array(
            'user_login'    => sanitize_user( $_POST['log_email'] ),
            'user_password' => $_POST['log_password'],
            'remember'      => true,
        ), false );

        if ( is_wp_error( $user_verify ) ) {
            wp_redirect( add_query_arg( 'error_login', 'credenciales', remove_query_arg( array( 'exito_reg', 'error_reg' ), wp_get_referer() ) ) );
            exit;
        }

        // VERIFICACIÓN VULNERABILIDAD: Revisar si está aprobado
        $estado = get_user_meta( $user_verify->ID, 'sigec_estado_cuenta', true );

        if ( $estado === 'pendiente' ) {
            wp_logout(); // Expulsamos al usuario no aprobado
            wp_redirect( add_query_arg( 'error_login', 'pendiente', remove_query_arg( array( 'exito_reg', 'error_reg', 'redirect_to', 'convocatoria_id' ), wp_get_referer() ) ) );
            exit;
        }

        // --- REDIRECCIÓN INTELIGENTE ---
        // Si la URL tiene instrucciones de ir a una postulación específica
        if ( isset( $_GET['redirect_to'] ) && $_GET['redirect_to'] === 'postulacion' && isset( $_GET['convocatoria_id'] ) ) {
            $url_destino = add_query_arg( 'convocatoria_id', intval( $_GET['convocatoria_id'] ), home_url( '/postulacion/' ) );
            wp_safe_redirect( $url_destino );
            exit;
        }

        // Si está aprobado y no hay redirección especial, entra a su panel normal
        wp_safe_redirect( home_url( '/panel-de-proveedores/' ) );
        exit;
    }
}

new SIGEC_Autenticacion();

