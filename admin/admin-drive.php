<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Seguridad
}

// 1. Agregar el nuevo menú en el panel lateral de WordPress
add_action( 'admin_menu', 'sigec_agregar_menu_drive' );
function sigec_agregar_menu_drive() {
    add_submenu_page(
        'edit.php?post_type=convocatoria',       // AQUÍ USAMOS TU ID ORIGINAL
        'Conexión Google Drive',                 // Título de la página
        'Drive SIGEC',                           // Nombre en el submenú
        'manage_options',                        // Permiso requerido
        'sigec-drive-settings',                  // Slug de esta página
        'sigec_renderizar_pagina_drive'          // Función
    );
}

// 2. Procesar el inicio y cierre de sesión por detrás
add_action( 'admin_init', 'sigec_procesar_conexion_drive' );
function sigec_procesar_conexion_drive() {
    // A. Atrapar la respuesta de Google cuando nos devuelve el token
    if ( isset( $_GET['code'] ) && !isset($_GET['page']) && current_user_can( 'manage_options' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../includes/class-sigec-drive.php';
        $drive = new SIGEC_Google_Drive();
        
        if ( $drive->procesar_codigo_autorizacion( $_GET['code'] ) ) {
            // Si guardó el token, redirigir a nuestra nueva página limpia
            wp_redirect( admin_url( 'admin.php?page=sigec-drive-settings&estado=conectado' ) );
            exit;
        }
    }
    
    // B. Cuando el usuario hace clic en el botón rojo de "Desconectar"
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'sigec-drive-settings' && isset( $_GET['desconectar'] ) ) {
        delete_option( 'sigec_google_token' ); // Borramos el token de la base de datos
        wp_redirect( admin_url( 'admin.php?page=sigec-drive-settings&estado=desconectado' ) );
        exit;
    }
}

// 3. Diseñar la pantalla HTML que verá el Rector
function sigec_renderizar_pagina_drive() {
    require_once plugin_dir_path( __FILE__ ) . '../includes/class-sigec-drive.php';
    $drive = new SIGEC_Google_Drive();
    
    // Verificamos si hay una llave guardada
    $esta_conectado = get_option( 'sigec_google_token' ) ? true : false;
    ?>
    <div class="wrap">
        <h1 style="margin-bottom: 20px;">Configuración de Almacenamiento - SIGEC</h1>
        
        <?php if ( isset( $_GET['estado'] ) && $_GET['estado'] === 'conectado' ) : ?>
            <div class="notice notice-success is-dismissible"><p><strong>¡Excelente!</strong> La cuenta de Google Drive se ha vinculado correctamente.</p></div>
        <?php endif; ?>

        <?php if ( isset( $_GET['estado'] ) && $_GET['estado'] === 'desconectado' ) : ?>
            <div class="notice notice-warning is-dismissible"><p>La cuenta ha sido desconectada. El sistema no podrá subir archivos hasta que vincules una nueva.</p></div>
        <?php endif; ?>

        <div style="background: #fff; padding: 25px; border: 1px solid #c3c4c7; border-radius: 4px; max-width: 600px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">Estado de la conexión en la nube</h2>
            
            <?php if ( $esta_conectado ) : ?>
                <div style="display: flex; align-items: center; margin: 20px 0;">
                    <span class="dashicons dashicons-yes-alt" style="color: #46b450; font-size: 30px; width: 30px; height: 30px;"></span>
                    <p style="font-size: 16px; margin: 0 0 0 10px;">
                        <strong style="color: #46b450;">Conectado y operativo</strong>.
                    </p>
                </div>
                <p style="color: #666; margin-bottom: 25px;">El sistema tiene permisos activos para gestionar documentos. Las convocatorias y licitaciones se subirán a la nube de forma automática.</p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=sigec-drive-settings&desconectar=1' ) ); ?>" class="button button-secondary" onclick="return confirm('¿Seguro que deseas desconectar la cuenta? Se detendrá la subida de nuevos documentos.');">Desconectar cuenta actual</a>
            <?php else : ?>
                <div style="display: flex; align-items: center; margin: 20px 0;">
                    <span class="dashicons dashicons-warning" style="color: #dc3232; font-size: 30px; width: 30px; height: 30px;"></span>
                    <p style="font-size: 16px; margin: 0 0 0 10px;">
                        <strong style="color: #dc3232;">Desconectado</strong>.
                    </p>
                </div>
                <p style="color: #666; margin-bottom: 25px;">Para garantizar que el servidor del colegio no se sature (Requisito RNF 01), debes autorizar una cuenta de Google donde se almacenarán los archivos.</p>
                <?php $auth_url = $drive->obtener_url_autorizacion(); ?>
              <a href="<?php echo esc_url( $auth_url ); ?>" class="button button-primary button-large" style="background: #1a73e8; border-color: #1a73e8; display: inline-flex; align-items: center; gap: 8px; padding: 0 15px; height: 32px; text-decoration: none;">
    <span class="dashicons dashicons-google" style="font-size: 18px; width: 18px; height: 18px; line-height: 1; margin: 0; display: inline-flex; align-items: center; justify-content: center;"></span>
    <span style="line-height: 1; padding-top: 2px;">Vincular con Google Drive</span>
</a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
