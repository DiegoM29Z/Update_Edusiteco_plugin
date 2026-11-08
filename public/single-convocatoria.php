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

        /* Caja de Anexos y Pliegos */
        .sigec-anexos-box {
            margin-top: 35px; 
            padding: 25px; 
            background-color: #f0fdfa; /* Un tono verde agua muy sutil e institucional */
            border-left: 4px solid #0d9488; 
            border-radius: 6px;
        }
        .sigec-anexos-title {
            margin: 0 0 10px 0; 
            color: #0f766e; 
            font-size: 1.3rem; 
            font-weight: 800; 
            display: flex; 
            align-items: center; 
            gap: 10px;
        }
        .sigec-anexos-texto {
            margin: 0 0 20px 0; 
            color: #115e59; 
            font-size: 16px;
        }
        .sigec-btn-descarga {
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            background-color: #0d9488; 
            color: white; 
            padding: 12px 24px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 15px; 
            transition: background-color 0.2s;
        }
        .sigec-btn-descarga:hover { background-color: #0f766e; color: white; }
        
       

        /* -----------------------------------------------------------
           MODO OSCURO
           ----------------------------------------------------------- */
        .dark .sigec-single-container { color: #EDF2F7; }
        .dark .sigec-single-header { border-bottom-color: #374151; }
        /* Anexos en Modo Oscuro */
        .dark .sigec-anexos-box { background-color: rgba(13, 148, 136, 0.1) !important; border-left-color: #14b8a6 !important; }
        .dark .sigec-anexos-title { color: #2dd4bf !important; }
        .dark .sigec-anexos-texto { color: #ccfbf1 !important; }
        
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
        .dark .sigec-form-container { background-color: rgba(255, 255, 255, 0.05) !important; border-color: #374151 !important; }
        .dark .sigec-form-container input[type="text"] { background-color: #1f2937; color: #f8fafc; border-color: #475569; }

    </style>

    
    <div class="sigec-single-container">
        <!-- Botón Volver -->
        <a href="javascript:history.back()" class="sigec-btn-volver">
            <span class="dashicons dashicons-arrow-left-alt" style="margin-top: 2px;"></span> Volver al Portal de Contratación
        </a>

        <!-- Encabezado y Título -->
        <div class="sigec-single-header">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <span style="color: #6b7280; font-weight: 600; font-size: 14px;">PROCESO: SIGEC-<?php echo esc_html( get_the_date( 'Y', $post_id ) ); ?>-<?php echo esc_html( str_pad( $post_id, 3, '0', STR_PAD_LEFT ) ); ?></span>
                <span class="sigec-badge <?php echo esc_attr( $clase_badge ); ?>"><?php echo esc_html( $estado ); ?></span>
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

       <!-- ========================================== -->
        <!-- TERCER BLOQUE: CAJA DE DOCUMENTOS OFICIALES -->
        <!-- ========================================== -->
        <?php
        $enlace_pliego = get_post_meta( $post_id, '_sigec_enlace_drive', true ); 
        
        if ( ! empty( $enlace_pliego ) ) :
        ?>
            <div class="sigec-anexos-box">
                <h4 class="sigec-anexos-title">
                    <span class="dashicons dashicons-pdf" style="font-size: 24px; width: 24px; height: 24px;"></span>
                    Documentos Oficiales
                </h4>
                <p class="sigec-anexos-texto">Descargue el pliego de condiciones y los anexos técnicos para conocer todos los detalles, requisitos legales y formatos requeridos para participar en este proceso.</p>
                
                <a href="<?php echo esc_url( $enlace_pliego ); ?>" target="_blank" rel="noopener noreferrer" class="sigec-btn-descarga">
                    <span class="dashicons dashicons-download" style="margin-top: 2px;"></span> Ver pliego de condiciones
                </a>
            </div>
        <?php endif; ?>
        <!-- ========================================== -->

   
     
    </div>

<?php 
endwhile; 

get_footer(); // Carga el pie de página
?>