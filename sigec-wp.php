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




 //Personalizar las columnas en el listado de Convocatorias

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


// 1. Columnas bonitas de la tabla (Estados y colores)
function sigec_mostrar_datos_columnas_convocatoria( $column, $post_id ) {
    switch ( $column ) {
        case 'presupuesto':
            $presupuesto = get_post_meta( $post_id, '_sigec_presupuesto', true );
            $num         = preg_replace( '/[^0-9]/', '', (string) $presupuesto );
            echo $num !== ''
                ? '<span style="color: #16a34a; font-weight: 700; font-size: 13px;">$' . esc_html( number_format( (float) $num, 0, ',', ',' ) ) . ' COP</span>'
                : '<span style="color: #94a3b8;">—</span>';
            break;

        case 'fecha_cierre':
            $fecha = get_post_meta( $post_id, '_sigec_fecha_cierre', true );
            echo $fecha
                ? '<span style="color: #475569; font-weight: 500;">' . esc_html( date_i18n( 'd \d\e F, Y - h:i A', strtotime( $fecha ) ) ) . '</span>'
                : '<span style="color: #94a3b8;">—</span>';
            break;

        case 'estado_convocatoria':
            $estado_wp    = get_post_status( $post_id );
            $fecha_cierre = get_post_meta( $post_id, '_sigec_fecha_cierre', true );
            $adjudicada   = get_post_meta( $post_id, '_sigec_convocatoria_adjudicada', true );
            $hoy          = current_time( 'Y-m-d\TH:i' );

            if ( $estado_wp === 'publish' ) {
                if ( $adjudicada ) {
                    echo '<span class="sigec-admin-badge adjudicada">ADJUDICADA</span>';
                } elseif ( $fecha_cierre && $fecha_cierre < $hoy ) {
                    echo '<span class="sigec-admin-badge cerrada">CERRADA</span>';
                } else {
                    echo '<span class="sigec-admin-badge abierta">ABIERTA</span>';
                }
            } elseif ( $estado_wp === 'draft' ) {
                echo '<span class="sigec-admin-badge borrador">BORRADOR</span>';
            } elseif ( $estado_wp === 'pending' ) {
                echo '<span class="sigec-admin-badge pendiente">PENDIENTE</span>';
            } else {
                echo esc_html( $estado_wp );
            }
            break;

        case 'ofertas':
            $ofertas = get_posts( array(
                'post_type'   => 'propuesta',
                'post_status' => 'publish',
                'fields'      => 'ids',
                'numberposts' => -1,
                'meta_query'  => array(
                    'relation' => 'OR',
                    array( 'key' => '_sigec_convocatoria_id', 'value' => $post_id, 'compare' => '=' ),
                    array( 'key' => 'convocatoria_id', 'value' => $post_id, 'compare' => '=' ),
                ),
            ) );
            $total_ofertas = count( $ofertas );
            $url_eval      = add_query_arg( array(
                'post_type'       => 'convocatoria',
                'page'            => 'sigec-evaluacion',
                'id_convocatoria' => $post_id,
            ), admin_url( 'edit.php' ) );
            echo '<a href="' . esc_url( $url_eval ) . '" class="sigec-admin-ofertas-link">' . intval( $total_ofertas ) . ' ' . ( $total_ofertas === 1 ? 'recibida' : 'recibidas' ) . ' &rarr;</a>';
            break;
    }
}
add_action( 'manage_convocatoria_posts_custom_column', 'sigec_mostrar_datos_columnas_convocatoria', 10, 2 );

// 2. Ocultar opciones de visibilidad y estilizar tabla de Todas las Convocatorias
function sigec_ocultar_opciones_visibilidad() {
    global $post_type;
    if ( 'convocatoria' === $post_type ) {
        echo '<style>
            #visibility-radio-password, #visibility-radio-private,
            label[for="visibility-radio-password"], label[for="visibility-radio-private"],
            #post-visibility-select .inline-edit-password-input { display: none !important; }
            .components-panel__row.editor-post-visibility { display: none !important; }

            /* Diseño moderno SIGEC para el listado de Todas las Convocatorias */
            body.edit-php.post-type-convocatoria .wrap {
                max-width: 1220px;
                margin-top: 22px;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
            body.edit-php.post-type-convocatoria .wrap h1.wp-heading-inline {
                font-size: 24px !important;
                font-weight: 700 !important;
                color: #0f172a !important;
                margin-right: 12px;
            }
            body.edit-php.post-type-convocatoria .wrap .page-title-action {
                background: #0a4b9c !important;
                color: #ffffff !important;
                border: 1px solid #0a4b9c !important;
                border-radius: 5px !important;
                padding: 6px 14px !important;
                font-size: 13px !important;
                font-weight: 600 !important;
                top: -2px;
                transition: background 0.15s;
            }
            body.edit-php.post-type-convocatoria .wrap .page-title-action:hover {
                background: #073b7a !important;
                border-color: #073b7a !important;
            }
            body.edit-php.post-type-convocatoria .wp-list-table {
                background: #ffffff !important;
                border: 1px solid #e2e8f0 !important;
                border-radius: 8px !important;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0,0,0,0.04) !important;
                border-collapse: collapse;
                margin-top: 10px;
            }
            body.edit-php.post-type-convocatoria .wp-list-table thead th,
            body.edit-php.post-type-convocatoria .wp-list-table tfoot th {
                background: #f8fafc !important;
                border-bottom: 1px solid #e2e8f0 !important;
                color: #475569 !important;
                font-weight: 700 !important;
                font-size: 12px !important;
                padding: 13px 16px !important;
            }
            body.edit-php.post-type-convocatoria .wp-list-table thead th a,
            body.edit-php.post-type-convocatoria .wp-list-table tfoot th a {
                color: #475569 !important;
                font-weight: 700 !important;
            }
            body.edit-php.post-type-convocatoria .wp-list-table tbody tr {
                background: #ffffff !important;
            }
            body.edit-php.post-type-convocatoria .wp-list-table tbody tr:hover {
                background: #f8fafc !important;
            }
            body.edit-php.post-type-convocatoria .wp-list-table tbody td,
            body.edit-php.post-type-convocatoria .wp-list-table tbody th.check-column {
                padding: 15px 16px !important;
                border-bottom: 1px solid #f1f5f9 !important;
                vertical-align: middle !important;
                font-size: 13px !important;
                color: #334155 !important;
            }
            body.edit-php.post-type-convocatoria .wp-list-table .row-title {
                color: #0f172a !important;
                font-weight: 700 !important;
                font-size: 14px !important;
            }
            body.edit-php.post-type-convocatoria .wp-list-table .row-title:hover {
                color: #0a4b9c !important;
            }
            body.edit-php.post-type-convocatoria .sigec-admin-badge {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.3px;
                text-transform: uppercase;
            }
            body.edit-php.post-type-convocatoria .sigec-admin-badge.adjudicada { background: #dcfce7; color: #15803d; }
            body.edit-php.post-type-convocatoria .sigec-admin-badge.abierta    { background: #dbeafe; color: #1d4ed8; }
            body.edit-php.post-type-convocatoria .sigec-admin-badge.cerrada    { background: #fee2e2; color: #dc2626; }
            body.edit-php.post-type-convocatoria .sigec-admin-badge.borrador   { background: #f1f5f9; color: #475569; }
            body.edit-php.post-type-convocatoria .sigec-admin-badge.pendiente  { background: #fef3c7; color: #b45309; }

            body.edit-php.post-type-convocatoria .sigec-admin-ofertas-link {
                display: inline-block;
                background: #eff6ff;
                color: #1d4ed8 !important;
                border: 1px solid #bfdbfe;
                padding: 5px 12px;
                border-radius: 999px;
                font-size: 12px;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.15s;
            }
            body.edit-php.post-type-convocatoria .sigec-admin-ofertas-link:hover {
                background: #dbeafe;
                color: #1e40af !important;
            }
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
    check_ajax_referer( 'sigec_buscador_nonce', 'nonce' );

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
    if ( $post && $post->post_type === 'convocatoria' ) {
        $plugin_template = plugin_dir_path( __FILE__ ) . 'public/single-convocatoria.php';
        if ( file_exists( $plugin_template ) ) return $plugin_template;
    }
    return $template;
}
add_filter( 'single_template', 'sigec_cargar_plantilla_individual' );

// 6. Auto-crear las páginas necesarias con sus shortcodes al activar el plugin.

/**
 * Crea automáticamente las 4 páginas del flujo público e inyecta sus shortcodes
 * cuando se activa el plugin en cualquier instalación de WordPress.
 */
function sigec_crear_paginas_por_defecto() {
    $paginas = array(
        'portal-de-contratacion' => array(
            'titulo'    => 'Portal de Contratación',
            'shortcode' => '[sigec_convocatorias_abiertas]',
        ),
        'panel-de-proveedores' => array(
            'titulo'    => 'Mi Panel de Proveedor',
            'shortcode' => '[sigec_panel_proveedor]',
        ),
        'acceso-proveedores' => array(
            'titulo'    => 'Acceso a Proveedores',
            'shortcode' => '[sigec_acceso_proveedores]',
        ),
        'postulacion' => array(
            'titulo'    => 'Envío de Propuesta',
            'shortcode' => '[sigec_formulario_postulacion]',
        ),
    );

    foreach ( $paginas as $slug => $datos ) {
        // 1. Buscar si ya existe una página con ese slug (en cualquier estado)
        $paginas_existentes = get_posts( array(
            'name'           => $slug,
            'post_type'      => 'page',
            'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'trash' ),
            'posts_per_page' => 1,
        ) );

        if ( ! empty( $paginas_existentes ) ) {
            $pagina      = $paginas_existentes[0];
            $actualizar  = array( 'ID' => $pagina->ID );
            $debe_salvar = false;

            // Si estaba en borrador o papelera, la publicamos
            if ( $pagina->post_status !== 'publish' ) {
                $actualizar['post_status'] = 'publish';
                $debe_salvar               = true;
            }

            // Si la página existía vacía sin el shortcode, se lo inyectamos
            if ( strpos( $pagina->post_content, $datos['shortcode'] ) === false ) {
                $actualizar['post_content'] = trim( $pagina->post_content . "\n\n" . $datos['shortcode'] );
                $debe_salvar                = true;
            }

            if ( $debe_salvar ) {
                wp_update_post( $actualizar );
            }
        } else {
            // 2. Si no existe, la crea automáticamente con su slug y shortcode exactos
            wp_insert_post( array(
                'post_title'     => $datos['titulo'],
                'post_name'      => $slug,
                'post_content'   => $datos['shortcode'],
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
            ) );
        }
    }

    // 3. Registrar el CPT y refrescar los enlaces permanentes para evitar errores 404
    sigec_registrar_cpt_convocatorias();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'sigec_crear_paginas_por_defecto' );

// 7. Recursos públicos del frontend (CSS y JS)

/**
 * Lista de shortcodes públicos de SIGEC.
 */
function sigec_shortcodes_publicos() {
    return array(
        'sigec_convocatorias_abiertas',
        'sigec_acceso_proveedores',
        'sigec_panel_proveedor',
        'sigec_formulario_postulacion',
    );
}

/**
 * ¿La página actual contiene alguno de los shortcodes indicados?
 */
function sigec_pagina_tiene_shortcode( $shortcodes ) {
    if ( ! is_singular() ) {
        return false;
    }
    $post = get_post();
    if ( ! $post ) {
        return false;
    }
    foreach ( (array) $shortcodes as $shortcode ) {
        if ( has_shortcode( $post->post_content, $shortcode ) ) {
            return true;
        }
    }
    return false;
}

/**
 * Registra el CSS/JS públicos y los encola solo en las páginas que los usan.
 */
function sigec_encolar_assets_publicos() {
    $url = plugin_dir_url( __FILE__ );
    $dir = plugin_dir_path( __FILE__ );

    // Estilos (filemtime = versión automática para evitar caché vieja al editar)
    wp_register_style(
        'sigec-public',
        $url . 'assets/css/sigec-public.css',
        array( 'dashicons' ),
        filemtime( $dir . 'assets/css/sigec-public.css' )
    );

    // Buscador AJAX del catálogo (cargado en el footer)
    wp_register_script(
        'sigec-buscador',
        $url . 'assets/js/sigec-buscador.js',
        array(),
        filemtime( $dir . 'assets/js/sigec-buscador.js' ),
        true
    );
    wp_localize_script( 'sigec-buscador', 'sigec_ajax', array(
        'url'   => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'sigec_buscador_nonce' ),
    ) );

    if ( sigec_pagina_tiene_shortcode( sigec_shortcodes_publicos() ) ) {
        wp_enqueue_style( 'sigec-public' );
    }
    if ( sigec_pagina_tiene_shortcode( 'sigec_convocatorias_abiertas' ) ) {
        wp_enqueue_script( 'sigec-buscador' );
    }
}
add_action( 'wp_enqueue_scripts', 'sigec_encolar_assets_publicos' );

/**
 * Clase en el <body> de la página del catálogo, para aplicar los ajustes
 * del tema (bordes, enlaces de edición) solo allí.
 */
function sigec_body_class_catalogo( $classes ) {
    if ( sigec_pagina_tiene_shortcode( 'sigec_convocatorias_abiertas' ) ) {
        $classes[] = 'sigec-pagina-catalogo';
    }
    return $classes;
}
add_filter( 'body_class', 'sigec_body_class_catalogo' );

// Motor de autenticación de proveedores (registro y login)
require_once plugin_dir_path( __FILE__ ) . 'includes/class-sigec-auth.php';

// Procesador de postulaciones y subida a Google Drive
require_once plugin_dir_path( __FILE__ ) . 'includes/class-sigec-postulacion.php';

// Shortcodes públicos (despachador de vistas)
require_once plugin_dir_path( __FILE__ ) . 'public/public-shortcodes.php';

// Cargar la interfaz de administración para Google Drive
require_once plugin_dir_path( __FILE__ ) . 'admin/admin-drive.php';

// AGREGAR EL METABOX EN EL POST TYPE CONVOCATORIA
require_once plugin_dir_path( __FILE__ ) . 'admin/admin-metabox.php';

//Comprobar y aceptar al proveedor
require_once plugin_dir_path( __FILE__ ) . 'admin/class-sigec-proveedores.php';

// Panel de Evaluación de Ofertas y Cierre Contractual (RF 09 y RF 11)
require_once plugin_dir_path( __FILE__ ) . 'admin/class-sigec-evaluacion-admin.php';
