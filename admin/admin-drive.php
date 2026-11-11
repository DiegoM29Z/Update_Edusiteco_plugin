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

    // Verificamos si el token guardado realmente es válido y conecta con Google
    $esta_conectado = $drive->conectar();
    ?>
    <style>
        .sigec-drive-wrap {
            margin: 25px 20px 40px 2px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        .sigec-drive-header {
            margin-bottom: 22px;
        }
        .sigec-drive-title {
            font-size: 24px !important;
            font-weight: 700 !important;
            color: #0f172a;
            margin: 0 0 6px 0 !important;
            padding: 0 !important;
            line-height: 1.2;
        }
        .sigec-drive-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }
        .sigec-drive-banner {
            border-radius: 6px;
            padding: 13px 16px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .sigec-drive-banner.exito {
            background: #dcfce7;
            border: 1px solid #4ade80;
            color: #166534;
        }
        .sigec-drive-banner.alerta {
            background: #fefce8;
            border: 1px solid #fde68a;
            color: #92400e;
        }
        .sigec-drive-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 26px 28px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            margin-bottom: 20px;
        }
        .sigec-drive-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 18px;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .sigec-drive-card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .sigec-drive-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .sigec-drive-pill.conectado {
            background: #dcfce7;
            color: #15803d;
        }
        .sigec-drive-pill.desconectado {
            background: #fee2e2;
            color: #dc2626;
        }
        .sigec-drive-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px 20px;
            margin: 18px 0 22px 0;
        }
        .sigec-drive-meta-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .sigec-drive-meta-val {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
        }
        .sigec-btn-drive-connect {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #0a4b9c;
            color: #ffffff !important;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.15s;
        }
        .sigec-btn-drive-connect:hover {
            background: #073b7a;
        }
        .sigec-btn-drive-disconnect {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fef2f2;
            color: #dc2626 !important;
            border: 1px solid #fecaca;
            padding: 9px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s;
        }
        .sigec-btn-drive-disconnect:hover {
            background: #fee2e2;
            color: #b91c1c !important;
        }
    </style>

    <div class="wrap sigec-drive-wrap">
        <div class="sigec-drive-header">
            <h1 class="sigec-drive-title">Configuración de Almacenamiento en la Nube — SIGEC</h1>
            <p class="sigec-drive-subtitle">Gestione la vinculación con Google Drive para almacenar automáticamente pliegos y ofertas licitatorias (RNF 01).</p>
        </div>

        <?php if ( isset( $_GET['estado'] ) && $_GET['estado'] === 'conectado' ) : ?>
            <div class="sigec-drive-banner exito">
                <span class="dashicons dashicons-yes-alt" style="color: #16a34a;"></span>
                <span><strong>¡Excelente!</strong> La cuenta de Google Drive se ha vinculado correctamente.</span>
            </div>
        <?php endif; ?>

        <?php if ( isset( $_GET['estado'] ) && $_GET['estado'] === 'desconectado' ) : ?>
            <div class="sigec-drive-banner alerta">
                <span class="dashicons dashicons-warning" style="color: #d97706;"></span>
                <span><strong>Cuenta desconectada.</strong> El sistema no podrá subir nuevos documentos hasta que vincule una cuenta.</span>
            </div>
        <?php endif; ?>

        <div class="sigec-drive-card">
            <div class="sigec-drive-card-top">
                <h2 class="sigec-drive-card-title">
                    <span class="dashicons dashicons-cloud" style="color: #0a4b9c; font-size: 22px; width: 22px; height: 22px;"></span>
                    Estado de la Conexión en la Nube
                </h2>
                <?php if ( $esta_conectado ) : ?>
                    <span class="sigec-drive-pill conectado">
                        <span class="dashicons dashicons-yes-alt" style="font-size: 15px; width: 15px; height: 15px;"></span>
                        Conectado y Operativo
                    </span>
                <?php else : ?>
                    <span class="sigec-drive-pill desconectado">
                        <span class="dashicons dashicons-warning" style="font-size: 15px; width: 15px; height: 15px;"></span>
                        Desconectado
                    </span>
                <?php endif; ?>
            </div>

            <?php if ( $esta_conectado ) : ?>
                <p style="color: #475569; font-size: 14px; margin: 0;">
                    El sistema cuenta con permisos OAuth 2.0 activos para gestionar documentos y asignar permisos de lectura pública. Las convocatorias y propuestas de los proveedores se organizan automáticamente en carpetas jerárquicas.
                </p>

                <div class="sigec-drive-grid">
                    <div>
                        <span class="sigec-drive-meta-label">PROVEEDOR DE NUBE</span>
                        <span class="sigec-drive-meta-val">Google Drive API v3</span>
                    </div>
                    <div>
                        <span class="sigec-drive-meta-label">ESTRUCTURA JERÁRQUICA</span>
                        <span class="sigec-drive-meta-val">Convocatoria SIGEC-{ID} &rarr; Ofertas</span>
                    </div>
                    <div>
                        <span class="sigec-drive-meta-label">PERMISOS DE ARCHIVOS</span>
                        <span class="sigec-drive-meta-val" style="color: #16a34a;">Lectura Pública Automática</span>
                    </div>
                </div>

                <a href="<?php echo esc_url( admin_url( 'admin.php?page=sigec-drive-settings&desconectar=1' ) ); ?>" class="sigec-btn-drive-disconnect" onclick="return confirm('¿Seguro que deseas desconectar la cuenta? Se detendrá la subida de nuevos documentos.');">
                    <span class="dashicons dashicons-dismiss" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    Desconectar cuenta actual
                </a>
            <?php else : ?>
                <p style="color: #475569; font-size: 14px; margin: 0 0 20px 0;">
                    Para garantizar que el servidor de la institución no se sature con archivos PDF pesados (Requisito RNF 01), autorice la cuenta institucional de Google Drive donde se almacenarán los pliegos y ofertas.
                </p>
                <?php $auth_url = $drive->obtener_url_autorizacion(); ?>
                <a href="<?php echo esc_url( $auth_url ); ?>" class="sigec-btn-drive-connect">
                    <span class="dashicons dashicons-google" style="font-size: 18px; width: 18px; height: 18px;"></span>
                    <span>Vincular con Google Drive</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
