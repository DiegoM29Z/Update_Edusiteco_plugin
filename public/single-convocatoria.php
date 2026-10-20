<?php
// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header(); // Carga el encabezado nativo

while ( have_posts() ) : the_post();
    $post_id = get_the_ID();
    $presupuesto = get_post_meta( $post_id, '_sigec_presupuesto', true );
    $fecha_cierre = get_post_meta( $post_id, '_sigec_fecha_cierre', true );
    
    $presupuesto_formateado = $presupuesto ? '$' . number_format( $presupuesto, 0, ',', '.' ) . ' COP' : 'No definido';
    $fecha_formateada = $fecha_cierre ? date_i18n( 'd \d\e F, Y - h:i A', strtotime( $fecha_cierre ) ) : 'Sin fecha límite';
    
    $hoy = current_time( 'Y-m-d\TH:i' );
    $estado = ($fecha_cierre && $fecha_cierre >= $hoy) ? 'Abierta' : 'Cerrada';
    $clase_badge = ($estado === 'Abierta') ? 'abierta' : 'cerrada';
    ?>
 
   <style>
       .sigec-single-container {
            /* Igualamos al catálogo: ancho casi total y forzamos el centrado */
            width: 95vw !important; 
            max-width: 95vw !important;
            position: relative;
            left: 50%;
            transform: translateX(-50%);
            
            padding: 40px 0; /* Espaciado superior e inferior */
            font-family: inherit;
            color: #1A202C;
        }
        .sigec-single-header {
            border-bottom: 3px solid #e5e7eb;
            padding-bottom: 30px;
            margin-bottom: 35px;
        }
        
        .sigec-btn-volver {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #4b5563;
            text-decoration: none;
            margin-bottom: 30px;
            font-weight: 600;
            transition: color 0.2s;
            font-size: 15px;
        }
        .sigec-btn-volver:hover { color: #0a4b9c; }

        .sigec-single-meta {
            display: flex;
            gap: 30px;
            margin-top: 30px; 
            background-color: #f8fafc;
            padding: 20px 25px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            flex-wrap: wrap;
        }
        .sigec-single-meta-item { display: flex; align-items: center; gap: 10px; font-size: 16px; }
        .sigec-badge { padding: 6px 14px; border-radius: 9999px; font-size: 14px; font-weight: 700; }
        .sigec-badge.abierta { background-color: #dcfce7; color: #166534; }
        .sigec-badge.cerrada { background-color: #fee2e2; color: #dc2626; }
        .sigec-monto { color: #0a4b9c; font-weight: 700; font-size: 18px; }
        
        /* Estilos para el área de lectura (Requisitos) */
        .sigec-single-content {
            font-size: 18px; /* AUMENTADO: de 17px a 18px */
            line-height: 1.8;
            color: #374151;
        }
        
        /* Subtítulos (Descripción y Ofertas) más grandes */
        .sigec-single-content h3, 
        #ofertas h3 {
            font-size: 1.8rem; /* AUMENTADO para que destaquen más */
            margin-bottom: 25px;
            font-weight: 800; /* Más negrita */
            color: inherit;
        }
        
        /* Reglas automáticas para lo que escriba la rectora en WordPress */
        .sigec-single-content p { margin-bottom: 16px; }
        .sigec-single-content ul, .sigec-single-content ol { margin-bottom: 16px; padding-left: 24px; }
        .sigec-single-content li { margin-bottom: 8px; }
        .sigec-single-content strong { color: inherit; }

        .sigec-ofertas-texto {
            color: #4b5563; 
            font-size: 16px;
        }

        /* -----------------------------------------------------------
           MODO OSCURO
           ----------------------------------------------------------- */
        .dark .sigec-single-container { color: #EDF2F7; }
        .dark .sigec-single-header { border-bottom-color: #374151; }
        
        /* Caja de metas más contrastante en modo oscuro */
        .dark .sigec-single-meta { 
            background-color: rgba(255, 255, 255, 0.04); 
            border-color: #374151; 
        }
        .dark .sigec-single-meta-item .dashicons { color: #9ca3af !important; }
        
        /* Aseguramos que los subtítulos sean blancos en modo oscuro */
        .dark .sigec-single-content h3, 
        .dark #ofertas h3 {
            color: #ffffff !important; 
        }
        
        /* Textos claros para lectura cómoda */
        .dark .sigec-single-content,
        .dark .sigec-single-content p,
        .dark .sigec-single-content li,
        .dark .sigec-ofertas-texto { 
            color: #e2e8f0 !important; 
        }
        
        .dark .sigec-monto { color: #3b82f6; }
        .dark .sigec-btn-volver { color: #9ca3af; }
        .dark .sigec-btn-volver:hover { color: #60a5fa; }
    </style>

    
    <div class="sigec-single-container">
        <!-- Botón Volver -->
        <a href="javascript:history.back()" class="sigec-btn-volver">
            <span class="dashicons dashicons-arrow-left-alt" style="margin-top: 2px;"></span> Volver al Portal de Contratación
        </a>

        <!-- Encabezado y Título -->
        <div class="sigec-single-header">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <span style="color: #6b7280; font-weight: 600; font-size: 14px;">PROCESO: SIGEC-<?php echo date('Y'); ?>-<?php echo str_pad($post_id, 3, "0", STR_PAD_LEFT); ?></span>
                <span class="sigec-badge <?php echo $clase_badge; ?>"><?php echo $estado; ?></span>
            </div>
            
            <h1 style="font-size: 2.8rem; font-weight: 800; margin: 0; color: inherit; line-height: 1.2;"><?php the_title(); ?></h1>
            
            <div class="sigec-single-meta">
                <div class="sigec-single-meta-item">
                    <span class="dashicons dashicons-calendar-alt" style="font-size: 20px; width: 20px; height: 20px; color: #6b7280;"></span>
                    <span><strong>Cierre de Recepción:</strong> <?php echo $fecha_formateada; ?></span>
                </div>
                <div class="sigec-single-meta-item">
                    <span class="dashicons dashicons-money-alt" style="font-size: 20px; width: 20px; height: 20px; color: #6b7280;"></span>
                    <span><strong>Presupuesto Oficial:</strong> <span class="sigec-monto"><?php echo $presupuesto_formateado; ?></span></span>
                </div>
            </div>
        </div>

        <!-- Contenido y Requisitos -->
        <div class="sigec-single-content">
            <h3 style="font-size: 1.6rem; margin-bottom: 25px; color: inherit; font-weight: 700;">Descripción y Requisitos Técnicos</h3>
            <?php 
            // the_content() imprime lo que hay en el editor
            the_content(); 
            ?>
        </div>

        <!-- Zona reservada para el Hito 2 -->
        <div id="ofertas" style="margin-top: 70px; padding-top: 40px; border-top: 2px dashed #cbd5e1;">
            <h3 style="font-size: 1.8rem; margin-bottom: 15px; color: inherit; font-weight: 700;">Recepción de Ofertas</h3>
            <p class="sigec-ofertas-texto">El formulario seguro de recepción de propuestas se habilitará en la siguiente fase de desarrollo.</p>
        </div>
    </div>

<?php 
endwhile; 

get_footer(); // Carga el pie de página
?>