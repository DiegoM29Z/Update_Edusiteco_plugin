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
    $presupuesto = get_post_meta( $post->ID, '_sigec_presupuesto', true );
    $fecha_cierre = get_post_meta( $post->ID, '_sigec_fecha_cierre', true );
    $enlace_drive = get_post_meta( $post->ID, '_sigec_enlace_drive', true );
    
    wp_nonce_field( 'sigec_guardar_parametros', 'sigec_parametros_nonce' );
    ?>
    <div style="padding: 15px 10px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;">
        
        <!-- Fila 1: Presupuesto y Fecha -->
        <div style="display: flex; gap: 30px; margin-bottom: 25px;">
            <div style="flex: 1;">
                <label for="sigec_presupuesto" style="font-weight: 600; display: block; margin-bottom: 10px; font-size: 14px; color: #1d2327;">Presupuesto Estimado (COP)</label>
                <input type="text" id="sigec_presupuesto" name="sigec_presupuesto" value="<?php echo esc_attr( $presupuesto ); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; box-shadow: none; font-size: 14px; color: #3c434a;" placeholder="Ej: 120,000,000">
            </div>
            <div style="flex: 1;">
                <label for="sigec_fecha_cierre" style="font-weight: 600; display: block; margin-bottom: 10px; font-size: 14px; color: #1d2327;">Fecha y Hora Límite de Entrega</label>
                <input type="datetime-local" id="sigec_fecha_cierre" name="sigec_fecha_cierre" value="<?php echo esc_attr( $fecha_cierre ); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; box-shadow: none; font-size: 14px; color: #3c434a;">
            </div>
        </div>

        <!-- Fila 2: Subida a Google Drive -->
        <div style="padding-top: 10px;">
            <label style="font-weight: 600; display: block; margin-bottom: 15px; font-size: 14px; color: #1d2327;">Subir Documento Base (Google Drive)</label>
            
            <?php if ( $enlace_drive ) : ?>
                <div style="background: #f0f6fc; border: 1px solid #c8d9ed; padding: 15px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #0a4b9c; font-weight: 600; font-size: 14px;"><span class="dashicons dashicons-yes-alt" style="color: #46b450; margin-right: 5px;"></span> Documento subido exitosamente.</span>
                    <a href="<?php echo esc_url( $enlace_drive ); ?>" target="_blank" class="button button-primary">Ver archivo en Drive</a>
                </div>
            <?php else : ?>
                <input type="file" id="sigec_archivo_drive" name="sigec_archivo_drive" accept=".pdf" style="display: none;">
                
                <button type="button" class="button" style="width: 100%; background: #f6f7f7; border: 1px solid #c3c4c7; color: #2271b1; padding: 12px; height: auto; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; border-radius: 4px; cursor: pointer; font-size: 14px; transition: background-color 0.2s;" onclick="document.getElementById('sigec_archivo_drive').click();" onmouseover="this.style.backgroundColor='#f0f0f1'" onmouseout="this.style.backgroundColor='#f6f7f7'">
                    <span class="dashicons dashicons-cloud-upload" style="color: #2271b1; font-size: 20px; width: 20px; height: 20px;"></span> Vincular Pliego de Condiciones desde Google Drive
                </button>
                <p class="description" style="margin-top: 10px; font-size: 13px; color: #646970;">El documento vinculado se sincronizará automáticamente para la visualización de los oferentes.</p>
                <span id="sigec_archivo_nombre" style="display: block; margin-top: 8px; font-weight: 600; color: #008a20; font-size: 13px;"></span>
                
                <script>
                    document.getElementById('sigec_archivo_drive').addEventListener('change', function() {
                        var fileName = this.files[0] ? this.files[0].name : '';
                        document.getElementById('sigec_archivo_nombre').innerText = fileName ? '✔️ Archivo "' + fileName + '" seleccionado. Guarda la convocatoria para subirlo.' : '';
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
