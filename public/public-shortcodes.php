<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Registrar el Shortcode
add_shortcode( 'sigec_convocatorias_abiertas', 'sigec_renderizar_catalogo_publico' );

function sigec_renderizar_catalogo_publico() {
    ob_start();

    // Consulta inicial (carga todas)
    $args = array(
        'post_type'      => 'convocatoria',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_key'       => '_sigec_fecha_cierre',
        'orderby'        => 'meta_value',
        'order'          => 'DESC'
    );
    $query = new WP_Query( $args );
    $hoy = current_time( 'Y-m-d\TH:i' );
    ?>

    <!-- CSS del Catálogo -->
    <style>

     /* Ocultar elementos nativos del tema de WordPress (Títulos, líneas extra y enlaces de edición) */
        .entry-header, 
        .page-header, 
        h1.entry-title, 
        h1.page-title,
        .entry-footer,
        .post-edit-link,
        .edit-link,
        hr.wp-block-separator {
            display: none !important;
        }

        /* Quitar bordes que inyecta el tema principal */
        .site-main > article {
            border-bottom: none !important;
        }
        .site-content, .site-main {
            border: none !important;
            box-shadow: none !important;
        }
        
        .sigec-catalogo {
            font-family: inherit;
            background-color: transparent;
            color: #1A202C; 
            width: 95vw !important; 
            max-width: 95vw !important;
            position: relative;
            left: 50%;
            transform: translateX(-50%);
            padding: 20px 0;
        }

        .sigec-header-title-wrapper {
            margin-bottom: 40px;
            text-align: left; /* Aseguramos alineación a la izquierda */
        }

      /* TIPOGRAFÍA: Título mucho más grande y alineado a la izquierda */
        .sigec-catalogo h1.page-title,
        .sigec-catalogo h1.entry-title,
        .sigec-catalogo-title {
            text-align: left !important;
            font-size: 3.5rem !important; 
            font-weight: 800 !important;
            margin-bottom: 10px !important; /* Menos margen inferior para pegar más el subtítulo */
            line-height: 1.2;
            color: #1A202C; /* Aseguramos el color del texto oscuro en modo claro */
        }
        
        /* Texto pequeñito institucional arriba del título principal */
        .sigec-header-institucional {
            color: #0a4b9c; /* Azul corporativo */
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px; /* Pegado al título grande */
            display: block;
        }
        /* TIPOGRAFÍA: Subtítulo ajustado y más grande */
      .sigec-header-subtitle {
            color: #6b7280;
            font-size: 1.3rem; 
            text-align: left;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        /* Controles de búsqueda */
        .sigec-controles {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 3px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .sigec-input-busqueda, .sigec-select-estado {
            padding: 12px 15px; /* Un poco más de relleno para que se vean más grandes */
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 15px; /* Letra de los controles más grande */
            color: #1A202C;
            background-color: #ffffff;
            outline: none;
            transition: all 0.2s ease;
        }
        
        .sigec-input-busqueda:focus, .sigec-select-estado:focus {
            border-color: #0a4b9c;
            box-shadow: 0 0 0 3px rgba(10, 75, 156, 0.1);
        }
        
        .sigec-input-busqueda {
            width: 100%;
            max-width: 450px; /* Caja de búsqueda un poco más ancha */
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%239ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>');
            background-repeat: no-repeat;
            background-position: 12px center;
            padding-left: 40px;
            box-sizing: border-box;
        }

        .sigec-select-estado {
            min-width: 240px; /* Menú desplegable más ancho */
            cursor: pointer;
            padding-right: 15px; 
            background-image: none !important; 
            appearance: auto !important;       
            -webkit-appearance: auto !important;
            -moz-appearance: auto !important;
        }
        
        /* Contenedor de Resultados (donde inyectaremos vía AJAX) */
        #sigec-resultados-contenedor {
            position: relative;
            min-height: 200px;
            transition: opacity 0.3s ease;
        }
        
        /* Animación de carga (Loading) */
        .sigec-loading {
            opacity: 0.5;
            pointer-events: none;
        }
        
       /* Fila de convocatoria (El resto sigue igual de bonito) */
        .sigec-tarjeta {
            background: transparent;
            border-radius: 0;
            padding: 30px 0;
            margin-bottom: 0;
            border-bottom: 2px solid #d1d5db;
            box-shadow: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            width: 100%;
        }
        
        .sigec-tarjeta-info { flex: 1; min-width: 300px; }
        .sigec-meta-superior { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .sigec-proceso-id { color: #6b7280; font-size: 13px; font-weight: 600; text-transform: uppercase; }
        .sigec-badge { padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 700; }
        .sigec-badge.abierta { background-color: #dcfce7; color: #166534; }
        .sigec-badge.cerrada { background-color: #fee2e2; color: #dc2626; }
        .sigec-titulo-conv { font-size: 22px; font-weight: 700; color: #1A202C; margin: 0 0 16px 0; }
        .sigec-meta-inferior { display: flex; gap: 30px; color: #4b5563; font-size: 15px; align-items: center; flex-wrap: wrap; }
        .sigec-meta-item { display: flex; align-items: center; gap: 8px; }
        .sigec-monto { color: #0a4b9c; font-weight: 700; }
        
        /* Botones */
        .sigec-acciones { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .sigec-btn-detalles { background-color: transparent; color: #0a4b9c; padding: 10px 20px; border: 2px solid #0a4b9c; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .sigec-btn-detalles:hover { background-color: #f0f7ff; color: #073b7a; border-color: #073b7a; }
        .sigec-btn-participar { background-color: #0a4b9c; color: white; padding: 10px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: background-color 0.2s; border: 2px solid #0a4b9c; }
        .sigec-btn-participar:hover { background-color: #073b7a; border-color: #073b7a; color: white; }
        .sigec-btn-disabled { background-color: #e5e7eb !important; border-color: #d1d5db !important; color: #9ca3af !important; pointer-events: none; cursor: not-allowed; }
        .sigec-alerta { background-color: transparent; border: 1px solid #c8d9ed; border-left: 4px solid #0a4b9c; padding: 16px; color: #0a4b9c; font-size: 14px; display: flex; align-items: center; gap: 10px; margin-top: 40px; border-radius: 4px; }

        /* MODO OSCURO */
        .dark .sigec-header-institucional { color: #60a5fa !important; }
        .dark .sigec-catalogo, .dark h1, .dark .page-title, .dark .entry-title, .dark .sigec-catalogo-title, .dark h2.sigec-titulo-conv { color: #EDF2F7 !important; }
        .dark .sigec-header-subtitle, .dark .sigec-meta-inferior, .dark .sigec-proceso-id { color: #cbd5e1 !important; }
        .dark .sigec-controles { border-bottom-color: #4b5563 !important; }
        .dark .sigec-tarjeta { border-bottom-color: #6b7280 !important; }.dark .sigec-input-busqueda, .dark .sigec-select-estado { background-color: #1f2937 !important; color: #EDF2F7 !important; border-color: #4b5563 !important; }
        .dark .sigec-select-estado option { background-color: #1f2937; color: #EDF2F7; }
        .dark .sigec-btn-detalles { color: #60a5fa; border-color: #60a5fa; }
        .dark .sigec-btn-detalles:hover { background-color: rgba(96, 165, 250, 0.1); color: #93c5fd; border-color: #93c5fd; }
        /* -----------------------------------------------------------
           AJUSTES DE COLOR AZUL PARA MODO OSCURO (Textos y Alertas)
           ----------------------------------------------------------- */
        /* El monto del presupuesto en azul brillante */
        .dark .sigec-monto {
            color: #3b82f6 !important; /* Azul más vibrante que combina con tus botones */
        }
        
        /* La caja de alerta informativa de abajo en azul brillante */
        .dark .sigec-alerta {
            color: #3b82f6 !important;
            border-color: #1e3a8a !important; /* Borde oscuro para la caja */
            border-left-color: #3b82f6 !important; /* Borde izquierdo brillante */
            background-color: rgba(59, 130, 246, 0.05) !important; /* Fondo sutil azulado */
        }
        
        /* El ícono de información dentro de la alerta */
        .dark .sigec-alerta .dashicons-info {
            color: #3b82f6 !important;
        }
        
        @media (max-width: 768px) {
            .sigec-tarjeta { flex-direction: column; align-items: flex-start; }
            .sigec-acciones { width: 100%; flex-direction: column; }
            .sigec-btn-detalles, .sigec-btn-participar { width: 100%; }
            .sigec-meta-inferior { flex-direction: column; gap: 10px; align-items: flex-start; }
        }
    </style>

    <div class="sigec-catalogo">
        <div class="sigec-header-title-wrapper" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
            <div>
                <span class="sigec-header-institucional">I.E. Rafael Pombo - Gestión de Adquisiciones</span>
                <h1 class="sigec-catalogo-title">Convocatorias y Contratación</h1>
                <p class="sigec-header-subtitle">Consulte y participe en los procesos de adquisición para instituciones educativas públicas del país.</p>
            </div>
            <div style="margin-top: 10px;">
                <?php if ( is_user_logged_in() ) : ?>
                    <a href="<?php echo esc_url( home_url( '/acceso-proveedores/' ) ); ?>" class="sigec-btn-participar" style="background-color: #ffffff; color: #0a4b9c; border-color: #cbd5e1; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <span class="dashicons dashicons-admin-users" style="margin-top:2px;"></span> Mi Panel
                    </a>
                <?php else : ?>
                    <a href="<?php echo esc_url( home_url( '/acceso-proveedores/' ) ); ?>" class="sigec-btn-participar" style="background-color: #1e293b; border-color: #1e293b;">
                        <span class="dashicons dashicons-lock" style="margin-top:2px;"></span> Iniciar Sesión
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Controles de Búsqueda (El resto de esta sección hacia abajo queda igual) -->
        <div class="sigec-controles">
            <input type="text" id="sigec-input-texto" class="sigec-input-busqueda" placeholder="Buscar convocatorias por título o palabra clave...">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="sigec-proceso-id" style="font-size: 15px; font-weight: 600; text-transform: none; color: inherit;">Estado:</span>
                <!-- ID agregado al select y valores exactos en los option -->
                <select id="sigec-select-estado" class="sigec-select-estado">
                    <option value="todas">Abiertas y Cerradas</option>
                    <option value="abiertas">Solo Abiertas</option>
                    <option value="cerradas">Solo Cerradas</option>
                </select>
            </div>
        </div>

        <!-- Contenedor que se actualizará con la búsqueda -->
        <div id="sigec-resultados-contenedor">
            <?php 
            // Llamamos a una función interna que dibuja las tarjetas. 
            // Así la reutilizamos en la petición de búsqueda.
            echo sigec_generar_html_tarjetas( $query, $hoy ); 
            ?>
        </div>

        <!-- Alerta de Información -->
        <div class="sigec-alerta">
            <span class="dashicons dashicons-info" style="font-size: 20px; width: 20px; height: 20px; color: #0a4b9c;"></span>
            <span>Recuerde que para presentar propuestas económicas debe estar registrado e iniciar sesión como proveedor autorizado del Estado.</span>
        </div>
    </div>

    <!-- SCRIPT DE BÚSQUEDA EN TIEMPO REAL (AJAX) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputTexto = document.getElementById('sigec-input-texto');
            const selectEstado = document.getElementById('sigec-select-estado');
            const contenedorResultados = document.getElementById('sigec-resultados-contenedor');
            
            // Variable para la URL de AJAX nativa de WordPress
            const ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
            let timeoutBusqueda = null;

            function buscarConvocatorias() {
                // Efecto visual de carga
                contenedorResultados.classList.add('sigec-loading');

                const data = new URLSearchParams();
                data.append('action', 'sigec_filtrar_convocatorias');
                data.append('texto', inputTexto.value);
                data.append('estado', selectEstado.value);
                
                // Petición al servidor
                fetch(ajaxurl, {
                    method: 'POST',
                    body: data
                })
                .then(response => response.text())
                .then(html => {
                    contenedorResultados.innerHTML = html;
                    contenedorResultados.classList.remove('sigec-loading');
                })
                .catch(error => {
                    console.error('Error al buscar:', error);
                    contenedorResultados.classList.remove('sigec-loading');
                });
            }

            // Escuchar cuando el usuario cambia el selector (Instantáneo)
            selectEstado.addEventListener('change', buscarConvocatorias);

            // Escuchar cuando el usuario escribe (Con retraso de 400ms para no saturar el servidor)
            inputTexto.addEventListener('input', function() {
                clearTimeout(timeoutBusqueda);
                timeoutBusqueda = setTimeout(buscarConvocatorias, 400);
            });
        });
    </script>
    <?php

    return ob_get_clean();
}

/**
 * 2. Función auxiliar que dibuja las tarjetas HTML (Usada por carga normal y por AJAX)
 */
function sigec_generar_html_tarjetas( $query, $hoy ) {
    ob_start();
    
    if ( $query->have_posts() ) :
        while ( $query->have_posts() ) : $query->the_post(); 
            $post_id = get_the_ID();
            $presupuesto = get_post_meta( $post_id, '_sigec_presupuesto', true );
            $fecha_cierre = get_post_meta( $post_id, '_sigec_fecha_cierre', true );
            
            $presupuesto_formateado = $presupuesto ? '$' . number_format( $presupuesto, 0, ',', '.' ) . ' COP' : 'No definido';
            $fecha_formateada = $fecha_cierre ? date_i18n( 'd \d\e F, Y', strtotime( $fecha_cierre ) ) : 'Sin fecha';

            // Lógica de Abierta/Cerrada
            $estado = ($fecha_cierre && $fecha_cierre >= $hoy) ? 'Abierta' : 'Cerrada';
            $clase_badge = ($estado === 'Abierta') ? 'abierta' : 'cerrada';
            
            $clase_btn_participar = ($estado === 'Cerrada') ? 'sigec-btn-participar sigec-btn-disabled' : 'sigec-btn-participar';
            $texto_btn_participar = ($estado === 'Cerrada') ? 'Cerrada' : 'Participar <span class="dashicons dashicons-arrow-right-alt" style="font-size: 16px; width: 16px; height: 16px; margin-top:2px;"></span>';
        ?>
        
        <div class="sigec-tarjeta">
            <div class="sigec-tarjeta-info">
                <div class="sigec-meta-superior">
                    <span class="sigec-proceso-id">PROCESO: SIGEC-<?php echo date('Y'); ?>-<?php echo str_pad($post_id, 3, "0", STR_PAD_LEFT); ?></span>
                    <span class="sigec-badge <?php echo $clase_badge; ?>"><?php echo $estado; ?></span>
                </div>
                <h2 class="sigec-titulo-conv"><?php the_title(); ?></h2>
                
                <div class="sigec-meta-inferior">
                    <div class="sigec-meta-item">
                        <span class="dashicons dashicons-calendar-alt" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span>Fecha de Cierre: <strong><?php echo $fecha_formateada; ?></strong></span>
                    </div>
                    <div class="sigec-meta-item">
                        <span class="dashicons dashicons-money-alt" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span>Presupuesto Oficial: <span class="sigec-monto"><?php echo $presupuesto_formateado; ?></span></span>
                    </div>
                </div>
            </div>
            
    <!-- Botones Separados -->
            <div class="sigec-acciones">
                <a href="<?php echo get_permalink(); ?>" class="sigec-btn-detalles">Ver Detalles</a>
                
                <?php if ( $estado === 'Abierta' ) : ?>
                    <?php if ( is_user_logged_in() ) : 
                        // Si tiene sesión, lo enviamos al nuevo formulario de postulación
                        $url_postulacion = esc_url( add_query_arg( 'convocatoria_id', $post_id, site_url( '/postulacion/' ) ) );
                    ?>
                        <a href="<?php echo $url_postulacion; ?>" class="<?php echo $clase_btn_participar; ?>">
                            <?php echo $texto_btn_participar; ?>
                        </a>
                    <?php else : 
                       // Si NO tiene sesión, lo enviamos a loguearse CON MEMORIA de la convocatoria
                        $url_login = esc_url( add_query_arg( array(
                            'redirect_to' => 'postulacion',
                            'convocatoria_id' => $post_id
                        ), home_url( '/acceso-proveedores/' ) ) );
                    ?>
                        <a href="<?php echo $url_login; ?>" class="<?php echo $clase_btn_participar; ?>">
                            Iniciar Sesión para Participar
                        </a>
                    <?php endif; ?>
                <?php else : ?>
                    <!-- Si la convocatoria ya está cerrada -->
                    <a href="#" class="<?php echo $clase_btn_participar; ?>" tabindex="-1">
                        <?php echo $texto_btn_participar; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php 
        endwhile; 
        wp_reset_postdata();
    else : 
    ?>
        <p style="padding: 40px 0; text-align: center; font-size: 16px; color: #6b7280;">No se encontraron convocatorias que coincidan con su búsqueda.</p>
    <?php 
    endif;
    
    return ob_get_clean();
}
/**
 * ==============================================================
 * 1. MOTOR DE PROCESAMIENTO (Se ejecuta antes de cargar la página)
 * ==============================================================
 */
add_action( 'template_redirect', 'sigec_procesar_formularios_acceso' );
function sigec_procesar_formularios_acceso() {
    
    // A. PROCESAR EL REGISTRO
    if ( isset( $_POST['sigec_registro_submit'] ) && wp_verify_nonce( $_POST['sigec_registro_nonce'], 'sigec_registrar_proveedor' ) ) {
        $email = sanitize_email( $_POST['reg_email'] );
        
        if ( email_exists( $email ) || username_exists( $email ) ) {
            wp_redirect( add_query_arg( 'error_reg', 'existe', remove_query_arg( array('exito_reg', 'error_login'), wp_get_referer() ) ) );
            exit;
        }
        
        $user_id = wp_create_user( $email, $_POST['reg_password'], $email );
        
        if ( ! is_wp_error( $user_id ) ) {
            // SOLUCIÓN VULNERABILIDAD: La cuenta nace bloqueada ('pendiente')
            update_user_meta( $user_id, 'sigec_estado_cuenta', 'pendiente' );
            
            update_user_meta( $user_id, 'sigec_razon_social', sanitize_text_field( $_POST['reg_razon'] ) );
            update_user_meta( $user_id, 'sigec_nit', sanitize_text_field( $_POST['reg_nit'] ) );
            update_user_meta( $user_id, 'sigec_rep_legal', sanitize_text_field( $_POST['reg_rep'] ) );
            update_user_meta( $user_id, 'sigec_telefono', sanitize_text_field( $_POST['reg_telefono'] ) );
            
            $user = new WP_User( $user_id );
            $user->set_role( 'subscriber' ); // Más adelante crearemos el rol 'proveedor'
            
            wp_redirect( add_query_arg( 'exito_reg', '1', remove_query_arg( array('error_reg', 'error_login'), wp_get_referer() ) ) );
            exit;
        }
    }

    // B. PROCESAR EL LOGIN
    if ( isset( $_POST['sigec_login_submit'] ) && wp_verify_nonce( $_POST['sigec_login_nonce'], 'sigec_iniciar_sesion' ) ) {
        $user_verify = wp_signon( array(
            'user_login'    => sanitize_user( $_POST['log_email'] ),
            'user_password' => $_POST['log_password'],
            'remember'      => true
        ), false );

        if ( is_wp_error( $user_verify ) ) {
            wp_redirect( add_query_arg( 'error_login', 'credenciales', remove_query_arg( array('exito_reg', 'error_reg'), wp_get_referer() ) ) );
            exit;
        } else {
            // VERIFICACIÓN VULNERABILIDAD: Revisar si está aprobado
            $estado = get_user_meta( $user_verify->ID, 'sigec_estado_cuenta', true );
            
            if ( $estado === 'pendiente' ) {
                wp_logout(); // Expulsamos al estudiante/usuario no aprobado
                wp_redirect( add_query_arg( 'error_login', 'pendiente', remove_query_arg( array('exito_reg', 'error_reg', 'redirect_to', 'convocatoria_id'), wp_get_referer() ) ) );
                exit;
            }
            
            // --- NUEVA LÓGICA DE REDIRECCIÓN INTELIGENTE ---
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
}

/**
 * ==============================================================
 * 2. SHORTCODE VISUAL (Carga el diseño y lee los mensajes de la URL)
 * ==============================================================
 */
function sigec_renderizar_acceso_proveedores() {
    if ( is_user_logged_in() && ! is_admin() && ! wp_is_json_request() ) {
        wp_safe_redirect( home_url( '/panel-de-proveedores/' ) );
        exit;
    }

    // Leer mensajes desde la URL (Cambiados por el motor de arriba)
    $mensaje_registro = '';
    $tipo_mensaje = '';
    if ( isset( $_GET['exito_reg'] ) ) {
        $mensaje_registro = '¡Registro exitoso! Tu cuenta está en revisión. La institución validará tu NIT para habilitar tu acceso.';
        $tipo_mensaje = 'exito';
    } elseif ( isset( $_GET['error_reg'] ) && $_GET['error_reg'] == 'existe' ) {
        $mensaje_registro = 'Ese correo ya está registrado. Por favor inicia sesión.';
        $tipo_mensaje = 'error';
    }

    $mensaje_login = '';
    if ( isset( $_GET['error_login'] ) ) {
        if ( $_GET['error_login'] == 'pendiente' ) {
            $mensaje_login = 'Tu cuenta está registrada, pero aún PENDIENTE de validación por la Rectora.';
        } else {
            $mensaje_login = 'Credenciales incorrectas. Inténtalo de nuevo.';
        }
    }

    ob_start();
    ?>
    <style>
        .entry-header, .page-header, h1.entry-title, h1.page-title { display: none !important; }
        .sigec-auth-wrapper { width: 95vw !important; max-width: 95vw !important; position: relative; left: 50%; transform: translateX(-50%); margin: 20px 0; font-family: inherit; background: transparent; padding: 20px 0; color: #1A202C; display: flex; flex-direction: column; align-items: center; }
        .sigec-auth-header { text-align: left; width: 100%; max-width: 1100px; margin-bottom: 30px; }
        .sigec-auth-header h1 { text-align: left !important; font-size: 3.5rem !important; font-weight: 800 !important; margin-bottom: 10px !important; line-height: 1.2; color: #1A202C; }
        .sigec-auth-header span { color: #0a4b9c; font-size: 1rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px; }
        .sigec-auth-box { background: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); width: 100%; max-width: 1100px; display: flex; flex-direction: column; overflow: hidden; border: 1px solid #cbd5e1; }
        
        @media(min-width: 768px) { .sigec-auth-box { flex-direction: row; } }
        .sigec-auth-col { padding: 40px; flex: 1; display: flex; flex-direction: column; }
        .sigec-auth-col-login { border-bottom: 1px solid #cbd5e1; }
        @media(min-width: 768px) { .sigec-auth-col-login { border-bottom: none; border-right: 1px solid #cbd5e1; max-width: 40%; } }
        
        .sigec-auth-col h3 { font-size: 24px; color: #0f172a; margin: 0 0 5px 0; font-weight: 700; }
        .sigec-auth-col p.desc { color: #64748b; font-size: 15px; margin-bottom: 30px; line-height: 1.5; }
        .sigec-form-group { margin-bottom: 20px; }
        .sigec-form-row { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px; }
        .sigec-form-row > div { flex: 1; min-width: 200px; }
        .sigec-auth-wrapper label { display: block; font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 8px; }
        .sigec-auth-wrapper label span { color: #dc2626; }
        .sigec-auth-wrapper input[type="text"], .sigec-auth-wrapper input[type="email"], .sigec-auth-wrapper input[type="password"] { width: 100%; padding: 14px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 15px; color: #1e293b; transition: all 0.2s; box-sizing: border-box; background: transparent; }
        .sigec-auth-wrapper input:focus { border-color: #0a4b9c; outline: none; }
        .sigec-btn-auth { width: 100%; background: #0a4b9c; color: white; border: none; padding: 16px; border-radius: 6px; font-size: 16px; font-weight: 700; cursor: pointer; transition: background 0.2s; margin-top: 10px; }
        .sigec-btn-auth:hover { background: #073b7a; }
        .sigec-link-olvido { display: block; text-align: center; margin-top: 20px; color: #0a4b9c; text-decoration: none; font-size: 15px; font-weight: 600; transition: color 0.2s; }
        .sigec-link-olvido:hover { color: #073b7a; text-decoration: underline; }
        .sigec-msg { padding: 15px; border-radius: 6px; margin-bottom: 25px; font-size: 15px; font-weight: 600; }
        .sigec-msg.error { background: #fee2e2; color: #b91c1c; border-left: 4px solid #ef4444; }
        .sigec-msg.exito { background: #dcfce7; color: #15803d; border-left: 4px solid #22c55e; }
        
        /* MODO OSCURO */
        .dark .sigec-auth-wrapper, .dark .sigec-auth-header h1 { color: #EDF2F7 !important; }
        .dark .sigec-auth-header span { color: #60a5fa; }
        .dark .sigec-auth-box { background: rgba(255,255,255,0.02); border-color: #374151; box-shadow: none; }
        .dark .sigec-auth-col-login { border-color: #374151; }
        .dark .sigec-auth-col h3 { color: #f8fafc; }
        .dark .sigec-auth-col p.desc { color: #94a3b8; }
        .dark .sigec-auth-wrapper label { color: #cbd5e1; }
        .dark .sigec-auth-wrapper input { border-color: #475569; color: #f8fafc; }
        .dark .sigec-auth-wrapper input:focus { border-color: #3b82f6; }
        .dark .sigec-link-olvido { color: #60a5fa; }
        .dark .sigec-link-olvido:hover { color: #93c5fd; }
    </style>

    <div class="sigec-auth-wrapper">1
        <div class="sigec-auth-header">
            <span>Portal Único de Contratación Educativa</span>
            <h1>Acceso a la Plataforma de Proveedores</h1>
        </div>
        
        <div class="sigec-auth-box">
            <div class="sigec-auth-col sigec-auth-col-login">
                <h3>Iniciar Sesión</h3>
                <p class="desc">Si ya posee una cuenta registrada en el sistema.</p>
                
                <?php if ( $mensaje_login ) : ?>
                    <div class="sigec-msg error"><?php echo esc_html( $mensaje_login ); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <?php wp_nonce_field( 'sigec_iniciar_sesion', 'sigec_login_nonce' ); ?>
                    <div class="sigec-form-group">
                        <label for="log_email">Correo Electrónico <span>*</span></label>
                        <input type="email" id="log_email" name="log_email" placeholder="ejemplo@proveedor.com" required>
                    </div>
                    <div class="sigec-form-group">
                        <label for="log_password">Contraseña <span>*</span></label>
                        <input type="password" id="log_password" name="log_password" required>
                    </div>
                    <button type="submit" name="sigec_login_submit" class="sigec-btn-auth">Iniciar Sesión</button>
                    <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="sigec-link-olvido">¿Olvidaste tu contraseña?</a>
                </form>
            </div>
            
            <div class="sigec-auth-col">
                <h3>Registro de Proveedor</h3>
                <p class="desc">Cree su perfil para postular propuestas oficiales.</p>
                
                <?php if ( $mensaje_registro ) : ?>
                    <div class="sigec-msg <?php echo $tipo_mensaje; ?>"><?php echo esc_html( $mensaje_registro ); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <?php wp_nonce_field( 'sigec_registrar_proveedor', 'sigec_registro_nonce' ); ?>
                    <div class="sigec-form-row">
                        <div>
                            <label for="reg_razon">Razón Social <span>*</span></label>
                            <input type="text" id="reg_razon" name="reg_razon" placeholder="Empresa S.A.S" required>
                        </div>
                        <div>
                            <label for="reg_nit">NIT <span>*</span></label>
                            <input type="text" id="reg_nit" name="reg_nit" placeholder="900.123.456-7" required>
                        </div>
                    </div>
                    <div class="sigec-form-group">
                        <label for="reg_rep">Representante Legal <span>*</span></label>
                        <input type="text" id="reg_rep" name="reg_rep" placeholder="Nombre completo" required>
                    </div>
                    <div class="sigec-form-row">
                        <div>
                            <label for="reg_email">Correo Electrónico <span>*</span></label>
                            <input type="email" id="reg_email" name="reg_email" placeholder="contacto@empresa.com" required>
                        </div>
                        <div>
                            <label for="reg_telefono">Teléfono <span>*</span></label>
                            <input type="text" id="reg_telefono" name="reg_telefono" placeholder="+57 300 123 4567" required>
                        </div>
                    </div>
                    <div class="sigec-form-group">
                        <label for="reg_password">Contraseña de la Cuenta <span>*</span></label>
                        <input type="password" id="reg_password" name="reg_password" placeholder="Mínimo 8 caracteres" minlength="8" required>
                    </div>
                    <button type="submit" name="sigec_registro_submit" class="sigec-btn-auth">Crear Cuenta de Proveedor</button>
                </form>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'sigec_acceso_proveedores', 'sigec_renderizar_acceso_proveedores' );

// ---------------------------------
/**
 * ==============================================================
 * SHORTCODE: PANEL DEL PROVEEDOR (DASHBOARD)
 * Uso: [sigec_panel_proveedor]
 * ==============================================================
 */
function sigec_renderizar_panel_proveedor() {
    // Si NO está logueado, y no estamos en el editor de bloques (JSON request) o admin
    if ( ! is_user_logged_in() && ! is_admin() && ! wp_is_json_request() ) {
        // Redirigir usando wp_safe_redirect para mayor seguridad y evitar bucles
        wp_safe_redirect( home_url( '/acceso-proveedores/' ) );
        exit;
    }

    $current_user = wp_get_current_user();
    
    // Obtener datos guardados (si existen)
    $razon_social = get_user_meta( $current_user->ID, 'sigec_razon_social', true ) ?: $current_user->display_name;
    $nit          = get_user_meta( $current_user->ID, 'sigec_nit', true ) ?: 'No registrado';
    $telefono     = get_user_meta( $current_user->ID, 'sigec_telefono', true ) ?: 'No registrado';

    ob_start();
    ?>
    <style>
        /* 1. Ocultar títulos nativos de WordPress */
        .entry-header, .page-header, h1.entry-title, h1.page-title { display: none !important; }

        /* 2. Contenedor Full Width (95vw) alineado con el resto del sistema */
        .sigec-dashboard-wrapper { 
            width: 95vw !important; 
            max-width: 95vw !important;
            position: relative;
            left: 50%;
            transform: translateX(-50%);
            margin: 20px 0; 
            font-family: inherit; 
            background: transparent; 
            padding: 20px 0; 
            color: #1A202C;
            min-height: 70vh;
        }

        /* 3. Cabecera alineada a la izquierda con tipografía institucional */
        .sigec-dashboard-header { 
            margin-bottom: 40px; 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start; /* Alineación superior para que el botón no quede flotando raro */
            flex-wrap: wrap; 
            gap: 20px; 
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
        }
        
        .sigec-dashboard-title-area h1 { text-align: left !important; font-size: 3.5rem !important; font-weight: 800 !important; margin-bottom: 10px !important; line-height: 1.2; color: #1A202C; }
        .sigec-dashboard-title-area p { color: #6b7280; font-size: 1.3rem; margin: 0; line-height: 1.6; }
        
        .sigec-btn-logout { background: #dc2626; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; font-size: 15px; transition: background 0.2s; display: inline-flex; align-items: center; gap: 8px; margin-top: 10px; }
        .sigec-btn-logout:hover { background: #b91c1c; color: white; }

        /* 4. Tarjetas de Información */
        .sigec-dash-card { background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .sigec-dash-card h3 { font-size: 22px; color: #0f172a; margin: 0 0 25px 0; display: flex; align-items: center; gap: 10px; font-weight: 700; }
        
        .sigec-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; }
        .sigec-info-item label { display: block; font-size: 12px; font-weight: 700; color: #0a4b9c; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .sigec-info-item span { font-size: 18px; color: #1e293b; font-weight: 600; display: block; }
        
        /* -----------------------------------------------------------
           MODO OSCURO
           ----------------------------------------------------------- */
        .dark .sigec-dashboard-wrapper, .dark .sigec-dashboard-title-area h1 { color: #EDF2F7 !important; }
        .dark .sigec-dashboard-header { border-bottom-color: #374151; }
        .dark .sigec-dashboard-title-area p { color: #cbd5e1; }
        .dark .sigec-dash-card { background: rgba(255,255,255,0.02); border-color: #374151; box-shadow: none; }
        .dark .sigec-dash-card h3, .dark .sigec-info-item span { color: #f8fafc; }
        .dark .sigec-info-item label { color: #60a5fa; }
    </style>

    <div class="sigec-dashboard-wrapper">
        <div class="sigec-dashboard-header">
            <div class="sigec-dashboard-title-area">
                <h1>Mi Panel de Proveedor</h1>
                <p>Gestione el perfil de su empresa y revise el estado de sus licitaciones.</p>
            </div>
            <a href="<?php echo esc_url( wp_logout_url( home_url( '/panel-de-proveedores/' ) ) ); ?>" class="sigec-btn-logout">
                <span class="dashicons dashicons-external"></span> Cerrar Sesión
            </a>
        </div>

        <div class="sigec-dash-card">
            <h3><span class="dashicons dashicons-building" style="color: #0a4b9c; width: 24px; height: 24px; font-size: 24px;"></span> Datos Básicos de la Empresa</h3>
            <div class="sigec-info-grid">
                <div class="sigec-info-item">
                    <label>Razón Social / Empresa</label>
                    <span><?php echo esc_html( $razon_social ); ?></span>
                </div>
                <div class="sigec-info-item">
                    <label>NIT / Documento</label>
                    <span><?php echo esc_html( $nit ); ?></span>
                </div>
                <div class="sigec-info-item">
                    <label>Correo Electrónico Registrado</label>
                    <span><?php echo esc_html( $current_user->user_email ); ?></span>
                </div>
                <div class="sigec-info-item">
                    <label>Teléfono de Contacto</label>
                    <span><?php echo esc_html( $telefono ); ?></span>
                </div>
            </div>
        </div>
        
        <div class="sigec-dash-card">
            <h3><span class="dashicons dashicons-portfolio" style="color: #0a4b9c; width: 24px; height: 24px; font-size: 24px;"></span> Mis Propuestas Enviadas</h3>
            <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 40px; text-align: center; border-radius: 6px;" class="dark:bg-gray-800 dark:border-gray-700">
                <span class="dashicons dashicons-info" style="color: #94a3b8; font-size: 32px; width: 32px; height: 32px; margin-bottom: 15px; display: inline-block;"></span>
                <p style="color: #64748b; font-size: 16px; margin: 0;">Próximamente aquí verá el historial y estado de todas sus postulaciones.</p>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'sigec_panel_proveedor', 'sigec_renderizar_panel_proveedor' );

/**
 * ==============================================================
 * SHORTCODE: FORMULARIO DE POSTULACIÓN
 * Uso: [sigec_formulario_postulacion]
 * ==============================================================
 */
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

    $titulo = get_the_title( $convocatoria_id );
    $fecha_cierre = get_post_meta( $convocatoria_id, '_sigec_fecha_cierre', true );
    $fecha_formateada = $fecha_cierre ? date_i18n( 'd \d\e F, Y - h:i A', strtotime( $fecha_cierre ) ) : 'No definida';

    ob_start();
    ?>
    <style>
        /* Ocultar el título nativo de la página */
        .entry-header, .page-header, h1.entry-title, h1.page-title { display: none !important; }

        /* Contenedor Full Width igual al catálogo */
        .sigec-form-wrapper { 
            width: 95vw !important; 
            max-width: 95vw !important;
            position: relative;
            left: 50%;
            transform: translateX(-50%);
            margin: 20px 0; 
            font-family: inherit; 
            background: transparent; 
            padding: 20px 0; 
            color: #1A202C;
        }
        
        .sigec-form-wrapper h1 { text-align: left !important; font-size: 3.5rem !important; font-weight: 800 !important; margin-bottom: 10px !important; line-height: 1.2; color: #1A202C; }
        .sigec-form-wrapper p.subtitle { color: #6b7280; font-size: 1.3rem; margin-bottom: 40px; text-align: left; line-height: 1.6; }
        
        .sigec-form-link-volver { color: #4b5563; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 30px; font-size: 15px; transition: color 0.2s; }
        .sigec-form-link-volver:hover { color: #0a4b9c; }

        /* Cajas de Información */
        .sigec-info-box { border: 1px solid #e2e8f0; border-left: 4px solid #0a4b9c; border-radius: 6px; padding: 25px; background: #f8fafc; display: flex; justify-content: space-between; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .sigec-info-box .title { color: #0a4b9c; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block; }
        .sigec-info-box .main-text { color: #1e293b; font-size: 20px; font-weight: 700; margin: 0; }
        .sigec-info-box .date-text { color: #dc2626; font-size: 15px; font-weight: 700; text-align: right; }
        
        /* Caja del Valor Ofertado */
        .sigec-valor-box { border: 2px solid #0a4b9c; border-radius: 6px; padding: 25px; background: #ffffff; margin-bottom: 40px; }
        .sigec-valor-box label { color: #1e293b; font-weight: 700; font-size: 18px; display: block; margin-bottom: 5px; }
        .sigec-valor-box input[type="text"] { width: 100%; border: none; border-bottom: 2px solid #cbd5e1; font-size: 2.5rem; font-weight: 800; color: #0f172a; padding: 10px 0; background: transparent; outline: none; transition: border-color 0.2s; }
        .sigec-valor-box input[type="text"]:focus { border-bottom-color: #0a4b9c; }
        
        /* Grilla de Documentos */
        .sigec-docs-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .sigec-doc-item { border: 2px dashed #cbd5e1; border-radius: 8px; padding: 30px 20px; text-align: center; background: #ffffff; transition: all 0.2s; }
        .sigec-doc-item:hover { border-color: #0a4b9c; background-color: #f0f7ff; }
        .sigec-doc-item .icon { color: #0a4b9c; font-size: 28px; margin-bottom: 15px; width: 28px; height: 28px; }
        .sigec-doc-item strong { display: block; color: #1e293b; font-size: 16px; margin-bottom: 5px; }
        .sigec-doc-item small { color: #64748b; display: block; margin-bottom: 15px; }
        .sigec-doc-item input[type="file"] { max-width: 100%; font-size: 13px; cursor: pointer; }
        
        .sigec-checkbox { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 40px; color: #374151; font-size: 15px; font-weight: 500; }
        .sigec-submit-btn { background: #0a4b9c; color: #ffffff; border: none; padding: 18px; width: 100%; border-radius: 6px; font-size: 18px; font-weight: 700; cursor: pointer; transition: background 0.2s; }
        .sigec-submit-btn:hover { background: #073b7a; }

        /* -----------------------------------------------------------
           MODO OSCURO
           ----------------------------------------------------------- */
        .dark .sigec-form-wrapper, .dark .sigec-form-wrapper h1 { color: #EDF2F7 !important; }
        .dark .sigec-form-wrapper p.subtitle, .dark .sigec-checkbox { color: #cbd5e1; }
        .dark .sigec-form-link-volver { color: #9ca3af; }
        .dark .sigec-form-link-volver:hover { color: #60a5fa; }
        
        .dark .sigec-info-box { background: rgba(255,255,255,0.03); border-color: #374151; border-left-color: #3b82f6; }
        .dark .sigec-info-box .title { color: #60a5fa; }
        .dark .sigec-info-box .main-text { color: #f8fafc; }
        .dark .sigec-info-box .date-text { color: #f87171; }
        
        .dark .sigec-valor-box { background: rgba(255,255,255,0.02); border-color: #3b82f6; }
        .dark .sigec-valor-box label { color: #e2e8f0; }
        .dark .sigec-valor-box input[type="text"] { color: #f8fafc; border-bottom-color: #475569; }
        .dark .sigec-valor-box input[type="text"]:focus { border-bottom-color: #3b82f6; }
        
        .dark .sigec-doc-item { background: rgba(255,255,255,0.02); border-color: #475569; }
        .dark .sigec-doc-item:hover { border-color: #3b82f6; background: rgba(59,130,246,0.05); }
        .dark .sigec-doc-item strong { color: #e2e8f0; }
        .dark .sigec-doc-item small { color: #94a3b8; }
        .dark .sigec-doc-item .icon { color: #60a5fa; }
    </style>

    <div class="sigec-form-wrapper">
       <a href="<?php echo esc_url( site_url( '/portal-de-contratacion/' ) ); ?>" class="sigec-form-link-volver">
            <span class="dashicons dashicons-arrow-left-alt" style="margin-top: 2px;"></span> Volver al Portal de Contratación
        </a>

        <h1>Envío de Propuesta Económica</h1>
        <p class="subtitle">Diligencie la información requerida y cargue los soportes obligatorios para formalizar su participación en este proceso licitatorio.</p>
        
        <div class="sigec-info-box">
            <div>
                <span class="title">Información del Proceso Seleccionado</span>
                <p class="main-text"><?php echo esc_html( $titulo ); ?></p>
                <small style="color: #64748b;">Referencia oficial: SIGEC-<?php echo date('Y'); ?>-<?php echo str_pad($convocatoria_id, 3, "0", STR_PAD_LEFT); ?></small>
            </div>
            <div style="text-align: right;">
                <span style="display: block; color: #64748b; font-size: 12px; margin-bottom: 5px;">Fecha Límite de Envío:</span>
                <span class="date-text"><?php echo $fecha_formateada; ?></span>
            </div>
        </div>

        <form id="form-postulacion-sigec" action="" method="POST" enctype="multipart/form-data">
            <?php wp_nonce_field( 'enviar_oferta_sigec', 'sigec_oferta_nonce' ); ?>
            <input type="hidden" name="convocatoria_id" value="<?php echo esc_attr( $convocatoria_id ); ?>">
            
            <div class="sigec-valor-box">
                <label for="valor_ofertado">Valor Ofertado ($)</label>
                <small style="color: #64748b; display: block; margin-bottom: 10px;">Ingrese el valor total con el cual se postula para este proceso, incluyendo impuestos de ley.</small>
                <div style="display: flex; align-items: baseline; gap: 10px;">
                    <span style="font-size: 2.5rem; font-weight: 800; color: #0a4b9c;" class="dark:text-blue-400">$</span>
                    <input type="text" id="valor_ofertado" name="valor_ofertado" placeholder="Ej: 448.500.000" required>
                    <span style="color: #64748b; font-weight: 600; font-size: 15px;">COP (PESOS COLOMBIANOS)</span>
                </div>
            </div>

            <h3 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 20px; color: inherit;">Carga de Documentos Soportes</h3>
            <div class="sigec-docs-grid">
                <!-- RUT -->
                <div class="sigec-doc-item">
                    <span class="dashicons dashicons-media-document icon"></span>
                    <strong>Registro Único Tributario (RUT)</strong>
                    <small>PDF oficial (.pdf)</small>
                    <input type="file" name="doc_rut" accept=".pdf" required>
                </div>
                <!-- Cédula -->
                <div class="sigec-doc-item">
                    <span class="dashicons dashicons-id icon"></span>
                    <strong>Cédula del Representante Legal</strong>
                    <small>PDF oficial (.pdf)</small>
                    <input type="file" name="doc_cedula" accept=".pdf" required>
                </div>
                <!-- Propuesta Técnica -->
                <div class="sigec-doc-item">
                    <span class="dashicons dashicons-portfolio icon"></span>
                    <strong>Propuesta Técnica (Completa)</strong>
                    <small>PDF oficial (.pdf)</small>
                    <input type="file" name="doc_propuesta" accept=".pdf" required>
                </div>
            </div>

            <div class="sigec-checkbox">
                <input type="checkbox" id="acepto_terminos" name="acepto_terminos" required style="margin-top: 3px; cursor: pointer;">
                <label for="acepto_terminos" style="cursor: pointer;">Certifico bajo juramento que los datos suministrados son reales y acepto los términos de contratación pública vigentes en la República de Colombia.</label>
            </div>

            <button type="submit" class="sigec-submit-btn">Enviar Propuesta Oficial de Contratación</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'sigec_formulario_postulacion', 'sigec_renderizar_formulario_postulacion' );