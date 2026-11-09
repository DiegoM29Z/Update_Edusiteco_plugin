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

        // Columnas y metabox en el administrador para gestionar el estado de la propuesta
        add_filter( 'manage_propuesta_posts_columns', array( $this, 'columnas_admin_propuestas' ) );
        add_action( 'manage_propuesta_posts_custom_column', array( $this, 'mostrar_columnas_admin_propuestas' ), 10, 2 );
        add_action( 'add_meta_boxes', array( $this, 'agregar_metabox_estado_propuesta' ) );
        add_action( 'save_post_propuesta', array( $this, 'guardar_metabox_estado_propuesta' ) );
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

        // 5. Organización automática en carpetas jerárquicas:
        // 5.1. Carpeta de la Convocatoria: "Convocatoria SIGEC-{ID} - {Título}" dentro de la carpeta raíz
        $carpeta_convocatoria = $drive->obtener_o_crear_carpeta_convocatoria( $convocatoria_id );

        if ( empty( $carpeta_convocatoria['success'] ) || empty( $carpeta_convocatoria['folder_id'] ) ) {
            $this->redirigir_con_error( $convocatoria_id, 'error_carpetas_drive' );
        }

        // 5.2. Subcarpeta del Proveedor: "Oferta - {Razon_Social} (NIT: {NIT})" dentro de la convocatoria
        $razon_social = get_user_meta( $user_id, 'sigec_razon_social', true );
        if ( empty( $razon_social ) ) {
            $razon_social = wp_get_current_user()->display_name;
        }

        $nit = get_user_meta( $user_id, 'sigec_nit', true );
        if ( empty( $nit ) ) {
            $nit = 'ID-' . $user_id;
        }

        $nombre_subcarpeta = sprintf( 'Oferta - %s (NIT: %s)', $razon_social, $nit );
        $carpeta_proveedor = $drive->obtener_o_crear_carpeta( $nombre_subcarpeta, $carpeta_convocatoria['folder_id'] );

        if ( empty( $carpeta_proveedor['success'] ) || empty( $carpeta_proveedor['folder_id'] ) ) {
            $this->redirigir_con_error( $convocatoria_id, 'error_carpetas_drive' );
        }

        $destino_id = $carpeta_proveedor['folder_id'];

        // 6. Subir cada uno de los 3 PDFs a la subcarpeta del proveedor con nombres dinámicos
        $nombre_rut       = "RUT_Prov{$user_id}_Conv{$convocatoria_id}.pdf";
        $nombre_cedula    = "Cedula_Prov{$user_id}_Conv{$convocatoria_id}.pdf";
        $nombre_propuesta = "Propuesta_Prov{$user_id}_Conv{$convocatoria_id}.pdf";

        $subida_rut       = $drive->subir_documento_licitacion( $_FILES['doc_rut']['tmp_name'], $nombre_rut, $destino_id );
        $subida_cedula    = $drive->subir_documento_licitacion( $_FILES['doc_cedula']['tmp_name'], $nombre_cedula, $destino_id );
        $subida_propuesta = $drive->subir_documento_licitacion( $_FILES['doc_propuesta']['tmp_name'], $nombre_propuesta, $destino_id );

        if ( empty( $subida_rut['success'] ) || empty( $subida_cedula['success'] ) || empty( $subida_propuesta['success'] ) ) {
            $this->redirigir_con_error( $convocatoria_id, 'error_subida_drive' );
        }

        // 7. Guardar la propuesta en la base de datos de WordPress
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
        update_post_meta( $propuesta_id, 'estado_propuesta', 'en_revision' );
        update_post_meta( $propuesta_id, 'link_rut', esc_url_raw( $subida_rut['link'] ) );
        update_post_meta( $propuesta_id, 'link_cedula', esc_url_raw( $subida_cedula['link'] ) );
        update_post_meta( $propuesta_id, 'link_propuesta', esc_url_raw( $subida_propuesta['link'] ) );
        update_post_meta( $propuesta_id, 'drive_carpeta_id', sanitize_text_field( $destino_id ) );
        if ( ! empty( $carpeta_proveedor['link'] ) ) {
            update_post_meta( $propuesta_id, 'link_carpeta', esc_url_raw( $carpeta_proveedor['link'] ) );
        }

        // 7. Redirigir al panel del proveedor con mensaje de éxito
        $url_exito = add_query_arg( 'exito_postulacion', '1', home_url( '/panel-de-proveedores/' ) );
        wp_safe_redirect( $url_exito );
        exit;
    }

    /**
     * Columnas personalizadas en el listado de Propuestas Recibidas (WP-Admin).
     */
    public function columnas_admin_propuestas( $columns ) {
        return array(
            'cb'             => $columns['cb'],
            'title'          => 'Propuesta',
            'convocatoria'   => 'Convocatoria',
            'valor_ofertado' => 'Valor Ofertado',
            'estado'         => 'Estado',
            'documentos'     => 'Soportes en Drive',
            'date'           => 'Fecha de Envío',
        );
    }

    /**
     * Renderiza el contenido de las columnas en Propuestas Recibidas (WP-Admin).
     */
    public function mostrar_columnas_admin_propuestas( $column, $post_id ) {
        switch ( $column ) {
            case 'convocatoria':
                $conv_id = intval( get_post_meta( $post_id, 'convocatoria_id', true ) );
                echo $conv_id ? '<strong>' . esc_html( get_the_title( $conv_id ) ) . '</strong>' : '—';
                break;

            case 'valor_ofertado':
                $valor = get_post_meta( $post_id, 'valor_ofertado', true );
                $num   = preg_replace( '/[^0-9]/', '', (string) $valor );
                echo $num !== '' ? '<strong>$' . esc_html( number_format( (float) $num, 0, ',', '.' ) ) . ' COP</strong>' : esc_html( $valor );
                break;

            case 'estado':
                $estado = get_post_meta( $post_id, 'estado_propuesta', true ) ?: 'en_revision';
                if ( $estado === 'adjudicada' ) {
                    echo '<span style="background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;padding:4px 10px;border-radius:999px;font-weight:600;font-size:12px;">Adjudicada</span>';
                } elseif ( $estado === 'rechazada' ) {
                    echo '<span style="background:#fee2e2;color:#dc2626;border:1px solid #fecaca;padding:4px 10px;border-radius:999px;font-weight:600;font-size:12px;">Rechazada</span>';
                } else {
                    echo '<span style="background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;padding:4px 10px;border-radius:999px;font-weight:600;font-size:12px;">En revisión</span>';
                }
                break;

            case 'documentos':
                $rut  = get_post_meta( $post_id, 'link_rut', true );
                $ced  = get_post_meta( $post_id, 'link_cedula', true );
                $prop = get_post_meta( $post_id, 'link_propuesta', true );
                $links = array();
                if ( $rut )  $links[] = '<a href="' . esc_url( $rut ) . '" target="_blank">RUT</a>';
                if ( $ced )  $links[] = '<a href="' . esc_url( $ced ) . '" target="_blank">Cédula</a>';
                if ( $prop ) $links[] = '<a href="' . esc_url( $prop ) . '" target="_blank"><strong>Propuesta</strong></a>';
                echo ! empty( $links ) ? implode( ' | ', $links ) : '—';
                break;
        }
    }

    /**
     * Registra la caja para que la Rectora cambie el estado de la propuesta (En revisión / Adjudicada / Rechazada).
     */
    public function agregar_metabox_estado_propuesta() {
        add_meta_box(
            'sigec_estado_propuesta_box',
            'Evaluación y Estado de la Propuesta',
            array( $this, 'renderizar_metabox_estado_propuesta' ),
            'propuesta',
            'normal',
            'high'
        );
    }

    /**
     * Dibuja el metabox con los detalles y el selector de estado de la propuesta.
     */
    public function renderizar_metabox_estado_propuesta( $post ) {
        $estado = get_post_meta( $post->ID, 'estado_propuesta', true ) ?: 'en_revision';
        $valor  = get_post_meta( $post->ID, 'valor_ofertado', true );
        $rut    = get_post_meta( $post->ID, 'link_rut', true );
        $ced    = get_post_meta( $post->ID, 'link_cedula', true );
        $prop   = get_post_meta( $post->ID, 'link_propuesta', true );
        wp_nonce_field( 'sigec_guardar_estado_propuesta', 'sigec_estado_propuesta_nonce' );
        ?>
        <div style="padding: 10px 0;">
            <p style="margin-top:0;"><strong>Valor Ofertado:</strong> $<?php echo esc_html( $valor ); ?> COP</p>
            <p>
                <strong>Documentos del Proveedor:</strong>
                <?php if ( $rut ) : ?><a href="<?php echo esc_url( $rut ); ?>" target="_blank" class="button" style="margin-right:6px;">Ver RUT</a><?php endif; ?>
                <?php if ( $ced ) : ?><a href="<?php echo esc_url( $ced ); ?>" target="_blank" class="button" style="margin-right:6px;">Ver Cédula</a><?php endif; ?>
                <?php if ( $prop ) : ?><a href="<?php echo esc_url( $prop ); ?>" target="_blank" class="button button-primary">Ver Propuesta</a><?php endif; ?>
            </p>
            <hr style="margin: 15px 0;">
            <label for="sigec_estado_propuesta" style="font-weight: 600; display: block; margin-bottom: 8px;">Estado de la Propuesta (visible para el proveedor en su panel):</label>
            <select id="sigec_estado_propuesta" name="sigec_estado_propuesta" style="min-width: 240px; padding: 6px 10px;">
                <option value="en_revision" <?php selected( $estado, 'en_revision' ); ?>>🔵 En revisión</option>
                <option value="adjudicada" <?php selected( $estado, 'adjudicada' ); ?>>🟢 Adjudicada</option>
                <option value="rechazada" <?php selected( $estado, 'rechazada' ); ?>>🔴 Rechazada</option>
            </select>
        </div>
        <?php
    }

    /**
     * Guarda el estado seleccionado por la Rectora en el administrador.
     */
    public function guardar_metabox_estado_propuesta( $post_id ) {
        if ( ! isset( $_POST['sigec_estado_propuesta_nonce'] ) || ! wp_verify_nonce( $_POST['sigec_estado_propuesta_nonce'], 'sigec_guardar_estado_propuesta' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( isset( $_POST['sigec_estado_propuesta'] ) ) {
            $permitidos = array( 'en_revision', 'adjudicada', 'rechazada' );
            $nuevo      = sanitize_text_field( $_POST['sigec_estado_propuesta'] );
            if ( in_array( $nuevo, $permitidos, true ) ) {
                update_post_meta( $post_id, 'estado_propuesta', $nuevo );
            }
        }
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

