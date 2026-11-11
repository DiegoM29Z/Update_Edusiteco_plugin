<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Decirle a WordPress que agregue nuestra caja meta
add_action( 'add_meta_boxes', 'sigec_agregar_metabox_parametros' );
function sigec_agregar_metabox_parametros() {
    add_meta_box(
        'sigec_parametros_documentos',         // ID interno
        'Parámetros y Documentos de SIGEC',    // Título de la caja
        'sigec_renderizar_metabox_parametros', // Función que la dibuja
        'convocatoria',                        // TU post type original
        'normal',                              // Posición (centro)
        'high'                                 // Prioridad (arriba)
    );
}

// 2. Dibujar el formulario visual
function sigec_renderizar_metabox_parametros( $post ) {
    // Recuperar datos si ya estaban guardados
    $presupuesto  = get_post_meta( $post->ID, '_sigec_presupuesto', true );
    $fecha_cierre = get_post_meta( $post->ID, '_sigec_fecha_cierre', true );
    $enlace_drive = get_post_meta( $post->ID, '_sigec_enlace_drive', true );

    wp_nonce_field( 'sigec_guardar_parametros', 'sigec_parametros_nonce' );
    ?>
    <style>
        /* Estilizar pantalla de Añadir / Editar Convocatoria al estilo SIGEC */
        body.post-type-convocatoria .wrap h1.wp-heading-inline {
            font-size: 24px !important;
            font-weight: 700 !important;
            color: #0f172a !important;
        }
        body.post-type-convocatoria #titlediv #title {
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            padding: 10px 14px !important;
            font-size: 18px !important;
            font-weight: 600 !important;
            color: #0f172a !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03) !important;
            height: 46px !important;
        }
        body.post-type-convocatoria #titlediv #title:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
        }
        body.post-type-convocatoria .postbox {
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04) !important;
            overflow: hidden;
        }
        body.post-type-convocatoria .postbox .postbox-header {
            background: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0 !important;
            padding: 4px 6px !important;
        }
        body.post-type-convocatoria .postbox .postbox-header h2 {
            font-size: 14px !important;
            font-weight: 700 !important;
            color: #0f172a !important;
        }
        body.post-type-convocatoria #publishing-action .button-primary {
            background: #0a4b9c !important;
            border-color: #0a4b9c !important;
            border-radius: 5px !important;
            font-weight: 600 !important;
            padding: 4px 18px !important;
            text-shadow: none !important;
        }
        body.post-type-convocatoria #publishing-action .button-primary:hover {
            background: #073b7a !important;
            border-color: #073b7a !important;
        }

        /* Estilos internos del Metabox SIGEC */
        .sigec-meta-wrap {
            padding: 16px 12px 10px 12px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        .sigec-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        .sigec-meta-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #475569;
            margin-bottom: 8px;
        }
        .sigec-meta-input {
            width: 100%;
            padding: 9px 13px !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            color: #0f172a !important;
            background: #ffffff !important;
            box-shadow: none !important;
            height: 40px !important;
        }
        .sigec-meta-input:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
        }
        .sigec-drive-dropzone {
            width: 100%;
            background: #f8fafc;
            border: 1px dashed #93c5fd;
            color: #1d4ed8;
            padding: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.15s;
        }
        .sigec-drive-dropzone:hover {
            background: #eff6ff;
            border-color: #2563eb;
            color: #1e40af;
        }
        .sigec-drive-uploaded {
            background: #f0fdf4;
            border: 1px solid #86efac;
            padding: 14px 18px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .sigec-btn-ver-pliego {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #0a4b9c;
            color: #ffffff !important;
            padding: 7px 14px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
        }
        .sigec-btn-ver-pliego:hover {
            background: #073b7a;
        }
    </style>

    <div class="sigec-meta-wrap">
        <!-- Fila 1: Presupuesto y Fecha -->
        <div class="sigec-meta-grid">
            <div>
                <label for="sigec_presupuesto" class="sigec-meta-label">
                    <span class="dashicons dashicons-money-alt" style="color: #16a34a; font-size: 16px; width: 16px; height: 16px;"></span>
                    Presupuesto Estimado (COP)
                </label>
                <input type="text" id="sigec_presupuesto" name="sigec_presupuesto" value="<?php echo esc_attr( $presupuesto ); ?>" class="sigec-meta-input" placeholder="Ej: 120000000">
            </div>
            <div>
                <label for="sigec_fecha_cierre" class="sigec-meta-label">
                    <span class="dashicons dashicons-calendar-alt" style="color: #2563eb; font-size: 16px; width: 16px; height: 16px;"></span>
                    Fecha y Hora Límite de Entrega
                </label>
                <input type="datetime-local" id="sigec_fecha_cierre" name="sigec_fecha_cierre" value="<?php echo esc_attr( $fecha_cierre ); ?>" class="sigec-meta-input">
            </div>
        </div>

        <!-- Fila 2: Subida a Google Drive -->
        <div>
            <label class="sigec-meta-label">
                <span class="dashicons dashicons-cloud" style="color: #0a4b9c; font-size: 16px; width: 16px; height: 16px;"></span>
                Pliego de Condiciones Oficial (Google Drive)
            </label>

            <?php if ( $enlace_drive ) : ?>
                <div class="sigec-drive-uploaded">
                    <span style="color: #166534; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-yes-alt" style="color: #16a34a;"></span>
                        Pliego de condiciones subido y sincronizado en Google Drive.
                    </span>
                    <a href="<?php echo esc_url( $enlace_drive ); ?>" target="_blank" rel="noopener noreferrer" class="sigec-btn-ver-pliego">
                        <span class="dashicons dashicons-external" style="font-size: 15px; width: 15px; height: 15px;"></span>
                        Ver archivo en Drive
                    </a>
                </div>
            <?php else : ?>
                <input type="file" id="sigec_archivo_drive" name="sigec_archivo_drive" accept=".pdf" style="display: none;">

                <button type="button" class="sigec-drive-dropzone" onclick="document.getElementById('sigec_archivo_drive').click();">
                    <span class="dashicons dashicons-cloud-upload" style="font-size: 22px; width: 22px; height: 22px;"></span>
                    <span>Vincular Pliego de Condiciones (.PDF) hacia Google Drive</span>
                </button>
                <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b;">
                    El documento se almacenará dentro de la carpeta oficial de esta convocatoria en Google Drive con permisos de lectura pública.
                </p>
                <span id="sigec_archivo_nombre" style="display: block; margin-top: 8px; font-weight: 600; color: #16a34a; font-size: 13px;"></span>

                <script>
                    document.getElementById('sigec_archivo_drive').addEventListener('change', function() {
                        var fileName = this.files[0] ? this.files[0].name : '';
                        document.getElementById('sigec_archivo_nombre').innerText = fileName ? '✔️ Archivo "' + fileName + '" seleccionado. Publica o actualiza la convocatoria para subirlo.' : '';
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// 3. Procesar y guardar cuando le den a "Publicar"
add_action( 'save_post', 'sigec_guardar_metabox_parametros' );
function sigec_guardar_metabox_parametros( $post_id ) {
    if ( ! isset( $_POST['sigec_parametros_nonce'] ) || ! wp_verify_nonce( $_POST['sigec_parametros_nonce'], 'sigec_guardar_parametros' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) || get_post_status( $post_id ) === 'auto-draft' ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Guardar Presupuesto y Fecha
    if ( isset( $_POST['sigec_presupuesto'] ) ) update_post_meta( $post_id, '_sigec_presupuesto', sanitize_text_field( $_POST['sigec_presupuesto'] ) );
    if ( isset( $_POST['sigec_fecha_cierre'] ) ) update_post_meta( $post_id, '_sigec_fecha_cierre', sanitize_text_field( $_POST['sigec_fecha_cierre'] ) );

    // Crear o sincronizar la carpeta de la Convocatoria en Google Drive ("Convocatoria SIGEC-{ID} - {Título}")
    require_once plugin_dir_path( __FILE__ ) . '../includes/class-sigec-drive.php';
    $drive        = new SIGEC_Google_Drive();
    $carpeta_conv = $drive->obtener_o_crear_carpeta_convocatoria( $post_id );
    $destino_id   = ! empty( $carpeta_conv['folder_id'] ) ? $carpeta_conv['folder_id'] : null;

    // Subir el Pliego de Condiciones dentro de la carpeta de la Convocatoria
    if ( ! empty( $_FILES['sigec_archivo_drive']['name'] ) && $_FILES['sigec_archivo_drive']['error'] === UPLOAD_ERR_OK ) {
        $titulo_conv = get_the_title( $post_id );
        if ( empty( $titulo_conv ) ) {
            $titulo_conv = 'Convocatoria_' . $post_id;
        }
        $nombre_final = sanitize_file_name( $titulo_conv ) . '_Pliego.pdf';

        $resultado = $drive->subir_documento_licitacion( $_FILES['sigec_archivo_drive']['tmp_name'], $nombre_final, $destino_id );

        if ( ! empty( $resultado['success'] ) ) {
            update_post_meta( $post_id, '_sigec_enlace_drive', esc_url_raw( $resultado['link'] ) );
        }
    }
}

// 4. Permitir archivos binarios en el formulario de WordPress
add_action('post_edit_form_tag', 'sigec_agregar_enctype_formulario');
function sigec_agregar_enctype_formulario() {
    echo ' enctype="multipart/form-data"';
}
