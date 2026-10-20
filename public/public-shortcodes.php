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

        /* Ocultar el título nativo del tema de WordPress para evitar duplicados */
        .entry-header, 
        .page-header, 
        h1.entry-title, 
        h1.page-title {
            display: none !important;
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
        .dark .sigec-tarjeta { border-bottom-color: #6b7280 !important; }
        .dark .sigec-input-busqueda, .dark .sigec-select-estado { background-color: #1f2937 !important; color: #EDF2F7 !important; border-color: #4b5563 !important; }
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
        <div class="sigec-header-title-wrapper">
            <!-- 1. Texto institucional sutil arriba -->
            <span class="sigec-header-institucional">I.E. Rafael Pombo - Gestión de Adquisiciones</span>
            
            <!-- 2. Título Principal (reemplazando al nativo de WordPress si es necesario) -->
            <h1 class="sigec-catalogo-title">Convocatorias y Contratación</h1>
            
            <!-- 3. Subtítulo descriptivo -->
            <p class="sigec-header-subtitle">Consulte y participe en los procesos de adquisición para instituciones educativas públicas del país.</p>
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
                <a href="<?php echo get_permalink(); ?>#ofertas" class="<?php echo $clase_btn_participar; ?>" <?php echo ($estado === 'Cerrada') ? 'tabindex="-1"' : ''; ?>>
                    <?php echo $texto_btn_participar; ?>
                </a>
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