<?php
/**
 * SIGEC-WP · Panel de Evaluación de Ofertas y Cierre Contractual (RF 09 y RF 11).
 *
 * Crea el submenú "Evaluación y Cierre" (slug: sigec-evaluacion) bajo "SIGEC - Compras":
 *   1. Vista Principal: Listado de convocatorias para seleccionar y evaluar ofertas.
 *   2. Vista de Detalle: Tabla de propuestas recibidas, validación de soportes en Google Drive
 *      y formulario de Adjudicación / Cierre Contractual.
 *   3. Vista de Cierre Definitivo: Resumen oficial de adjudicación en modo de solo lectura.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SIGEC_Evaluacion_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'registrar_submenu_evaluacion' ) );
        add_action( 'admin_init', array( $this, 'procesar_adjudicacion' ) );
    }

    /**
     * 1. Registra el submenú "Evaluación y Cierre" dentro de "SIGEC - Compras".
     */
    public function registrar_submenu_evaluacion() {
        add_submenu_page(
            'edit.php?post_type=convocatoria',
            'Evaluación y Cierre Contractual',
            'Evaluación y Cierre',
            'manage_options',
            'sigec-evaluacion',
            array( $this, 'renderizar_pagina_evaluacion' )
        );
    }

    /**
     * 2. Procesa el formulario de Adjudicación / Cierre Contractual cuando la Rectora lo envía.
     */
    public function procesar_adjudicacion() {
        if ( ! isset( $_POST['sigec_adjudicar_submit'] ) ) {
            return;
        }

        if ( ! isset( $_POST['sigec_adjudicacion_nonce'] ) || ! wp_verify_nonce( $_POST['sigec_adjudicacion_nonce'], 'sigec_registrar_adjudicacion' ) ) {
            wp_die( 'Error de seguridad: Token de verificación inválido.' );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'No tiene permisos suficientes para realizar esta acción.' );
        }

        $convocatoria_id       = isset( $_POST['id_convocatoria'] ) ? intval( $_POST['id_convocatoria'] ) : 0;
        $propuesta_ganadora_id = isset( $_POST['propuesta_ganadora_id'] ) ? intval( $_POST['propuesta_ganadora_id'] ) : 0;

        if ( ! $convocatoria_id || ! $propuesta_ganadora_id ) {
            return;
        }

        // Verificar que la convocatoria no haya sido adjudicada previamente
        $ya_adjudicada = get_post_meta( $convocatoria_id, '_sigec_convocatoria_adjudicada', true );
        if ( $ya_adjudicada ) {
            return;
        }

        // Obtener todas las propuestas vinculadas a esta convocatoria
        $propuestas = $this->obtener_propuestas_por_convocatoria( $convocatoria_id );

        foreach ( $propuestas as $prop ) {
            if ( intval( $prop->ID ) === $propuesta_ganadora_id ) {
                // a) Marcar la propuesta ganadora como 'adjudicada'
                update_post_meta( $prop->ID, '_sigec_estado_evaluacion', 'adjudicada' );
                update_post_meta( $prop->ID, 'estado_propuesta', 'adjudicada' );
            } else {
                // b) Marcar todas las demás propuestas como 'rechazada'
                update_post_meta( $prop->ID, '_sigec_estado_evaluacion', 'rechazada' );
                update_post_meta( $prop->ID, 'estado_propuesta', 'rechazada' );
            }
        }

        // c) Marcar la convocatoria como adjudicada y guardar datos del cierre oficial
        update_post_meta( $convocatoria_id, '_sigec_convocatoria_adjudicada', '1' );
        update_post_meta( $convocatoria_id, '_sigec_propuesta_ganadora_id', $propuesta_ganadora_id );
        update_post_meta( $convocatoria_id, '_sigec_fecha_adjudicacion', current_time( 'mysql' ) );

        // Redirigir a la vista de detalle con confirmación de cierre
        $url_retorno = add_query_arg( array(
            'post_type'       => 'convocatoria',
            'page'            => 'sigec-evaluacion',
            'id_convocatoria' => $convocatoria_id,
            'adjudicado'      => '1',
        ), admin_url( 'edit.php' ) );

        wp_safe_redirect( $url_retorno );
        exit;
    }

    /**
     * Obtiene todas las propuestas asociadas a una convocatoria (compatible con ambas llaves meta).
     *
     * @param int $convocatoria_id ID de la convocatoria.
     * @return WP_Post[]
     */
    private function obtener_propuestas_por_convocatoria( $convocatoria_id ) {
        return get_posts( array(
            'post_type'   => 'propuesta',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'date',
            'order'       => 'ASC',
            'meta_query'  => array(
                'relation' => 'OR',
                array(
                    'key'     => '_sigec_convocatoria_id',
                    'value'   => $convocatoria_id,
                    'compare' => '=',
                ),
                array(
                    'key'     => 'convocatoria_id',
                    'value'   => $convocatoria_id,
                    'compare' => '=',
                ),
            ),
        ) );
    }

    /**
     * 3. Despachador visual: decide si mostrar la Vista Principal o la Vista de Detalle.
     */
    public function renderizar_pagina_evaluacion() {
        $this->imprimir_estilos_admin();

        $id_convocatoria = isset( $_GET['id_convocatoria'] ) ? intval( $_GET['id_convocatoria'] ) : 0;

        echo '<div class="wrap sigec-eval-wrap">';
        if ( $id_convocatoria && get_post_type( $id_convocatoria ) === 'convocatoria' ) {
            $this->renderizar_vista_detalle( $id_convocatoria );
        } else {
            $this->renderizar_vista_principal();
        }
        echo '</div>';
    }

    /**
     * 4. VISTA PRINCIPAL: Selección de Convocatoria.
     * Lista las convocatorias cerradas (y permite filtrar todas para pruebas rápidas).
     */
    private function renderizar_vista_principal() {
        $hoy          = current_time( 'Y-m-d\TH:i' );
        $filtro_vista = isset( $_GET['mostrar'] ) ? sanitize_text_field( $_GET['mostrar'] ) : 'cerradas';

        $args = array(
            'post_type'      => 'convocatoria',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        // Por defecto filtramos las convocatorias con estado 'Cerrada' (fecha_cierre < hoy) o ya adjudicadas
        if ( $filtro_vista !== 'todas' ) {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key'     => '_sigec_fecha_cierre',
                    'value'   => $hoy,
                    'compare' => '<',
                    'type'    => 'DATETIME',
                ),
                array(
                    'key'     => '_sigec_convocatoria_adjudicada',
                    'value'   => '1',
                    'compare' => '=',
                ),
            );
        }

        $convocatorias = get_posts( $args );

        $url_cerradas = add_query_arg( array( 'post_type' => 'convocatoria', 'page' => 'sigec-evaluacion', 'mostrar' => 'cerradas' ), admin_url( 'edit.php' ) );
        $url_todas    = add_query_arg( array( 'post_type' => 'convocatoria', 'page' => 'sigec-evaluacion', 'mostrar' => 'todas' ), admin_url( 'edit.php' ) );
        ?>
        <div class="sigec-eval-header">
            <div>
                <h1 class="sigec-eval-title">Evaluación de Ofertas y Cierre Contractual</h1>
                <p class="sigec-eval-subtitle">Seleccione una convocatoria cerrada para validar los soportes de los proponentes y adjudicar el contrato.</p>
            </div>
            <div class="sigec-eval-tabs">
                <a href="<?php echo esc_url( $url_cerradas ); ?>" class="sigec-tab-btn <?php echo $filtro_vista !== 'todas' ? 'active' : ''; ?>">
                    Cerradas / Listas para Evaluar
                </a>
                <a href="<?php echo esc_url( $url_todas ); ?>" class="sigec-tab-btn <?php echo $filtro_vista === 'todas' ? 'active' : ''; ?>">
                    Ver Todas las Convocatorias
                </a>
            </div>
        </div>

        <div class="sigec-eval-table-card">
            <table class="sigec-eval-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Título de la Convocatoria</th>
                        <th>Presupuesto Oficial</th>
                        <th>Fecha de Cierre</th>
                        <th>Ofertas Recibidas</th>
                        <th>Estado del Proceso</th>
                        <th style="text-align: right;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $convocatorias ) ) : ?>
                        <?php foreach ( $convocatorias as $conv ) :
                            $presupuesto  = get_post_meta( $conv->ID, '_sigec_presupuesto', true );
                            $fecha_cierre = get_post_meta( $conv->ID, '_sigec_fecha_cierre', true );
                            $adjudicada   = get_post_meta( $conv->ID, '_sigec_convocatoria_adjudicada', true );
                            $propuestas   = $this->obtener_propuestas_por_convocatoria( $conv->ID );
                            $total_prop   = count( $propuestas );
                            $ano_proc     = get_the_date( 'Y', $conv->ID );
                            $cerrada      = ( $fecha_cierre && $fecha_cierre < $hoy );

                            $url_evaluar = add_query_arg( array(
                                'post_type'       => 'convocatoria',
                                'page'            => 'sigec-evaluacion',
                                'id_convocatoria' => $conv->ID,
                            ), admin_url( 'edit.php' ) );
                            ?>
                            <tr>
                                <td style="font-weight: 600; color: #64748b;">
                                    SIGEC-<?php echo esc_html( $ano_proc ); ?>-<?php echo esc_html( str_pad( $conv->ID, 3, '0', STR_PAD_LEFT ) ); ?>
                                </td>
                                <td>
                                    <strong style="color: #0f172a; font-size: 14px;"><?php echo esc_html( $conv->post_title ); ?></strong>
                                </td>
                                <td style="font-weight: 700; color: #16a34a;">
                                    <?php echo $presupuesto ? '$' . esc_html( number_format( (float) preg_replace( '/[^0-9]/', '', (string) $presupuesto ), 0, ',', ',' ) ) . ' COP' : '—'; ?>
                                </td>
                                <td style="color: #475569;">
                                    <?php echo $fecha_cierre ? esc_html( date_i18n( 'd \d\e F, Y - h:i A', strtotime( $fecha_cierre ) ) ) : '—'; ?>
                                </td>
                                <td>
                                    <span class="sigec-count-pill"><?php echo intval( $total_prop ); ?> <?php echo $total_prop === 1 ? 'oferta' : 'ofertas'; ?></span>
                                </td>
                                <td>
                                    <?php if ( $adjudicada ) : ?>
                                        <span class="sigec-badge-eval adjudicada">ADJUDICADA</span>
                                    <?php elseif ( $cerrada ) : ?>
                                        <span class="sigec-badge-eval rechazada">CERRADA</span>
                                    <?php else : ?>
                                        <span class="sigec-badge-eval abierta">ABIERTA</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="<?php echo esc_url( $url_evaluar ); ?>" class="sigec-btn-evaluar <?php echo $adjudicada ? 'secundario' : ''; ?>">
                                        <?php echo $adjudicada ? 'Ver Historial de Cierre' : 'Evaluar Ofertas'; ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px 20px; color: #64748b;">
                                No se encontraron convocatorias con estado <strong>Cerrada</strong> en este momento.
                                <br><br>
                                <a href="<?php echo esc_url( $url_todas ); ?>" class="button">Ver todas las convocatorias (incluyendo abiertas)</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * 5. VISTA DE DETALLE: Evaluación de Ofertas y Panel de Adjudicación / Cierre Definitivo.
     *
     * @param int $convocatoria_id ID de la convocatoria seleccionada.
     */
    private function renderizar_vista_detalle( $convocatoria_id ) {
        $titulo_convocatoria = get_the_title( $convocatoria_id );
        $ya_adjudicada       = (bool) get_post_meta( $convocatoria_id, '_sigec_convocatoria_adjudicada', true );
        $propuestas          = $this->obtener_propuestas_por_convocatoria( $convocatoria_id );

        $url_volver = add_query_arg( array(
            'post_type' => 'convocatoria',
            'page'      => 'sigec-evaluacion',
        ), admin_url( 'edit.php' ) );

        // Datos del ganador si ya está adjudicada
        $ganador_datos = null;
        ?>
        <a href="<?php echo esc_url( $url_volver ); ?>" class="sigec-link-volver">
            &lsaquo; Volver a Convocatorias
        </a>

        <div class="sigec-eval-header" style="margin-bottom: 18px;">
            <div>
                <h1 class="sigec-eval-title">
                    <?php echo $ya_adjudicada ? 'Propuestas Recibidas &mdash; Historial de Cierre' : 'Propuestas Recibidas'; ?>
                </h1>
                <p class="sigec-eval-subtitle">Convocatoria: <?php echo esc_html( $titulo_convocatoria ); ?></p>
            </div>
        </div>

        <?php if ( $ya_adjudicada ) : ?>
            <!-- Alerta verde de Proceso Finalizado -->
            <div class="sigec-banner-exito">
                <span class="dashicons dashicons-yes-alt"></span>
                <span><strong>Proceso Finalizado.</strong> Esta convocatoria ha sido adjudicada exitosamente. El cierre contractual se encuentra en estado de solo lectura.</span>
            </div>
        <?php else : ?>
            <!-- Alerta azul informativa -->
            <div class="sigec-banner-info">
                <span class="dashicons dashicons-info-outline"></span>
                <span>Este panel permite validar los documentos obligatorios de los proponentes y registrar la adjudicación final. Solo los proveedores con documentación completa están aptos para asignación.</span>
            </div>
        <?php endif; ?>

        <!-- Tabla de Propuestas Recibidas -->
        <div class="sigec-eval-table-card">
            <table class="sigec-eval-table">
                <thead>
                    <tr>
                        <th>Razón Social</th>
                        <th>NIT</th>
                        <th>Valor Ofertado</th>
                        <?php if ( $ya_adjudicada ) : ?>
                            <th>Estado</th>
                            <th>Soportes</th>
                        <?php else : ?>
                            <th>Documentación en Nube (Google Drive / OneDrive)</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $propuestas ) ) : ?>
                        <?php foreach ( $propuestas as $prop ) :
                            $autor_id = intval( $prop->post_author );

                            // Razón Social y NIT del autor de la propuesta
                            $razon_social = get_user_meta( $autor_id, 'sigec_razon_social', true );
                            if ( empty( $razon_social ) ) {
                                $user_obj     = get_userdata( $autor_id );
                                $razon_social = $user_obj ? $user_obj->display_name : $prop->post_title;
                            }
                            $nit = get_user_meta( $autor_id, 'sigec_nit', true ) ?: 'No registrado';

                            // Valor Ofertado (compatible con _sigec_valor_ofertado y valor_ofertado)
                            $valor_raw    = get_post_meta( $prop->ID, '_sigec_valor_ofertado', true );
                            if ( $valor_raw === '' ) {
                                $valor_raw = get_post_meta( $prop->ID, 'valor_ofertado', true );
                            }
                            $valor_limpio     = preg_replace( '/[^0-9]/', '', (string) $valor_raw );
                            $valor_formateado = $valor_limpio !== '' ? '$' . number_format( (float) $valor_limpio, 0, ',', ',' ) . ' COP' : '$' . $valor_raw . ' COP';

                            // Links a Google Drive (compatibles con _sigec_link_* y link_*)
                            $link_rut       = get_post_meta( $prop->ID, '_sigec_link_rut', true ) ?: get_post_meta( $prop->ID, 'link_rut', true );
                            $link_cedula    = get_post_meta( $prop->ID, '_sigec_link_cedula', true ) ?: get_post_meta( $prop->ID, 'link_cedula', true );
                            $link_propuesta = get_post_meta( $prop->ID, '_sigec_link_propuesta', true ) ?: get_post_meta( $prop->ID, 'link_propuesta', true );

                            // Estado de evaluación
                            $estado_eval = get_post_meta( $prop->ID, '_sigec_estado_evaluacion', true ) ?: get_post_meta( $prop->ID, 'estado_propuesta', true );
                            $es_ganadora = ( $estado_eval === 'adjudicada' );

                            if ( $es_ganadora ) {
                                $ganador_datos = array(
                                    'razon_social' => $razon_social,
                                    'nit'          => $nit,
                                    'valor'        => $valor_formateado,
                                );
                            }
                            ?>
                            <tr class="<?php echo ( $ya_adjudicada && $es_ganadora ) ? 'sigec-row-ganadora' : ''; ?>">
                                <td>
                                    <strong style="color: #0f172a; font-size: 14px; display: block;"><?php echo esc_html( $razon_social ); ?></strong>
                                    <?php if ( $ya_adjudicada && $es_ganadora ) : ?>
                                        <span style="color: #16a34a; font-size: 12px; font-weight: 600;">Proponente Seleccionado</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: #64748b; font-size: 13px;">
                                    <?php echo esc_html( $nit ); ?>
                                </td>
                                <td>
                                    <span class="<?php echo ( ! $ya_adjudicada || $es_ganadora ) ? 'sigec-valor-verde' : 'sigec-valor-neutro'; ?>">
                                        <?php echo esc_html( $valor_formateado ); ?>
                                    </span>
                                </td>

                                <?php if ( $ya_adjudicada ) : ?>
                                    <td>
                                        <?php if ( $es_ganadora ) : ?>
                                            <span class="sigec-badge-eval adjudicada">ADJUDICADA</span>
                                        <?php else : ?>
                                            <span class="sigec-badge-eval rechazada">RECHAZADA</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>

                                <td>
                                    <div class="sigec-doc-btns">
                                        <?php if ( $link_rut ) : ?>
                                            <a href="<?php echo esc_url( $link_rut ); ?>" target="_blank" rel="noopener noreferrer" class="sigec-btn-doc">
                                                <span class="dashicons dashicons-cloud"></span> Ver RUT
                                            </a>
                                        <?php else : ?>
                                            <span class="sigec-btn-doc-falta">&#9888; Falta RUT</span>
                                        <?php endif; ?>

                                        <?php if ( $link_cedula ) : ?>
                                            <a href="<?php echo esc_url( $link_cedula ); ?>" target="_blank" rel="noopener noreferrer" class="sigec-btn-doc">
                                                <span class="dashicons dashicons-cloud"></span> Ver Cédula
                                            </a>
                                        <?php else : ?>
                                            <span class="sigec-btn-doc-falta">&#9888; Falta Cédula</span>
                                        <?php endif; ?>

                                        <?php if ( $link_propuesta ) : ?>
                                            <a href="<?php echo esc_url( $link_propuesta ); ?>" target="_blank" rel="noopener noreferrer" class="sigec-btn-doc">
                                                <span class="dashicons dashicons-cloud"></span> Ver Propuesta
                                            </a>
                                        <?php else : ?>
                                            <span class="sigec-btn-doc-falta">&#9888; Falta Propuesta</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 35px 20px; color: #64748b;">
                                Aún no se han recibido propuestas para esta convocatoria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ( ! $ya_adjudicada && ! empty( $propuestas ) ) : ?>
            <!-- PANEL DE ADJUDICACIÓN / CIERRE CONTRACTUAL (Formulario) -->
            <div class="sigec-panel-adjudicacion">
                <div class="sigec-panel-adj-title">
                    <span class="dashicons dashicons-lock" style="color: #d97706;"></span>
                    <span>Panel de Adjudicación / Cierre Contractual</span>
                </div>
                <p class="sigec-panel-adj-desc">
                    Al proceder con el registro, se seleccionará al proponente idóneo, se inhabilitará la recepción de nuevas ofertas y se enviará la notificación correspondiente a SIGEC.
                </p>

                <form method="POST" action="" class="sigec-panel-adj-form" onsubmit="return confirm('¿Está segura de adjudicar oficialmente este contrato? Esta acción cerrará el proceso de forma definitiva.');">
                    <?php wp_nonce_field( 'sigec_registrar_adjudicacion', 'sigec_adjudicacion_nonce' ); ?>
                    <input type="hidden" name="id_convocatoria" value="<?php echo esc_attr( $convocatoria_id ); ?>">

                    <select name="propuesta_ganadora_id" class="sigec-select-proponente" required>
                        <option value="">Seleccionar proponente adjudicado...</option>
                        <?php foreach ( $propuestas as $prop ) :
                            $autor_id     = intval( $prop->post_author );
                            $razon_social = get_user_meta( $autor_id, 'sigec_razon_social', true );
                            if ( empty( $razon_social ) ) {
                                $user_obj     = get_userdata( $autor_id );
                                $razon_social = $user_obj ? $user_obj->display_name : $prop->post_title;
                            }
                            $valor_raw    = get_post_meta( $prop->ID, '_sigec_valor_ofertado', true ) ?: get_post_meta( $prop->ID, 'valor_ofertado', true );
                            $valor_limpio = preg_replace( '/[^0-9]/', '', (string) $valor_raw );
                            $valor_fmt    = $valor_limpio !== '' ? '$' . number_format( (float) $valor_limpio, 0, ',', ',' ) . ' COP' : '$' . $valor_raw . ' COP';
                            ?>
                            <option value="<?php echo esc_attr( $prop->ID ); ?>">
                                <?php echo esc_html( $razon_social . ' — ' . $valor_fmt ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" name="sigec_adjudicar_submit" value="1" class="sigec-btn-adjudicar">
                        Registrar Adjudicación / Cierre
                    </button>
                </form>
            </div>

        <?php elseif ( $ya_adjudicada && $ganador_datos ) :
            $fecha_cierre_raw = get_post_meta( $convocatoria_id, '_sigec_fecha_adjudicacion', true ) ?: get_post_meta( $convocatoria_id, '_sigec_fecha_cierre', true );
            $fecha_cierre_fmt = $fecha_cierre_raw ? date_i18n( 'j \d\e F \d\e Y', strtotime( $fecha_cierre_raw ) ) : date_i18n( 'j \d\e F \d\e Y' );
            ?>
            <!-- VISTA DE CIERRE DEFINITIVO: Resumen de Adjudicación Oficial de Contrato -->
            <div class="sigec-resumen-cierre">
                <div class="sigec-resumen-header">
                    <span class="dashicons dashicons-portfolio" style="color: #334155;"></span>
                    <span class="sigec-resumen-title">Resumen de Adjudicación Oficial de Contrato</span>
                    <span class="sigec-badge-cerrado">CERRADO DEFINITIVO</span>
                </div>

                <div class="sigec-resumen-grid">
                    <div class="sigec-resumen-item">
                        <span class="sigec-resumen-label">ADJUDICATARIO GANADOR:</span>
                        <strong class="sigec-resumen-val"><?php echo esc_html( $ganador_datos['razon_social'] ); ?></strong>
                    </div>
                    <div class="sigec-resumen-item">
                        <span class="sigec-resumen-label">NIT ADJUDICADO:</span>
                        <strong class="sigec-resumen-val"><?php echo esc_html( $ganador_datos['nit'] ); ?></strong>
                    </div>
                    <div class="sigec-resumen-item">
                        <span class="sigec-resumen-label">FECHA DE CIERRE OFICIAL:</span>
                        <strong class="sigec-resumen-val"><?php echo esc_html( $fecha_cierre_fmt ); ?></strong>
                    </div>
                    <div class="sigec-resumen-item">
                        <span class="sigec-resumen-label">MONTO DEL CONTRATO ADJUDICADO:</span>
                        <strong class="sigec-resumen-val verde"><?php echo esc_html( $ganador_datos['valor'] ); ?></strong>
                    </div>
                </div>

                <div class="sigec-resumen-footer">
                    <span class="dashicons dashicons-yes-alt" style="color: #16a34a; font-size: 16px; width: 16px; height: 16px;"></span>
                    <span>El acta de adjudicación oficial ha sido firmada digitalmente y reportada automáticamente al Secop II bajo los protocolos del SIGEC Colombia.</span>
                </div>
            </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Estilos CSS del panel de Evaluación y Cierre en el Administrador.
     */
    private function imprimir_estilos_admin() {
        ?>
        <style>
            .sigec-eval-wrap {
                margin: 25px 20px 40px 2px;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
            .sigec-link-volver {
                display: inline-block;
                color: #2563eb;
                text-decoration: none;
                font-size: 13px;
                font-weight: 500;
                margin-bottom: 10px;
            }
            .sigec-link-volver:hover { text-decoration: underline; color: #1d4ed8; }

            .sigec-eval-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                margin-bottom: 22px;
                flex-wrap: wrap;
                gap: 15px;
            }
            .sigec-eval-title {
                font-size: 24px !important;
                font-weight: 700 !important;
                color: #0f172a;
                margin: 0 0 6px 0 !important;
                padding: 0 !important;
                line-height: 1.2;
            }
            .sigec-eval-subtitle {
                margin: 0;
                color: #64748b;
                font-size: 14px;
            }

            /* Pestañas de filtro en Vista Principal */
            .sigec-eval-tabs {
                display: inline-flex;
                background: #e2e8f0;
                padding: 4px;
                border-radius: 6px;
                gap: 4px;
            }
            .sigec-tab-btn {
                padding: 6px 14px;
                border-radius: 4px;
                text-decoration: none;
                font-size: 13px;
                font-weight: 600;
                color: #475569;
                transition: all 0.15s;
            }
            .sigec-tab-btn.active {
                background: #ffffff;
                color: #0f172a;
                box-shadow: 0 1px 2px rgba(0,0,0,0.06);
            }

            /* Banners informativos */
            .sigec-banner-info {
                background: #f0f7ff;
                border: 1px solid #93c5fd;
                border-radius: 6px;
                padding: 13px 16px;
                color: #1e3a8a;
                font-size: 13px;
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 20px;
            }
            .sigec-banner-info .dashicons { color: #2563eb; }

            .sigec-banner-exito {
                background: #dcfce7;
                border: 1px solid #4ade80;
                border-radius: 6px;
                padding: 13px 16px;
                color: #166534;
                font-size: 13px;
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 20px;
            }
            .sigec-banner-exito .dashicons { color: #16a34a; }

            /* Tabla principal */
            .sigec-eval-table-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0,0,0,0.04);
                margin-bottom: 24px;
            }
            .sigec-eval-table {
                width: 100%;
                border-collapse: collapse;
                text-align: left;
            }
            .sigec-eval-table thead tr {
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
            }
            .sigec-eval-table th {
                padding: 13px 18px;
                font-size: 12px;
                font-weight: 700;
                color: #475569;
            }
            .sigec-eval-table td {
                padding: 16px 18px;
                border-bottom: 1px solid #f1f5f9;
                vertical-align: middle;
                font-size: 13px;
            }
            .sigec-eval-table tbody tr:last-child td { border-bottom: none; }
            .sigec-row-ganadora { background-color: #f0fdf4 !important; }

            .sigec-valor-verde {
                color: #16a34a;
                font-weight: 700;
                font-size: 14px;
            }
            .sigec-valor-neutro {
                color: #1e293b;
                font-weight: 700;
                font-size: 14px;
            }

            /* Botones de documentos en Drive */
            .sigec-doc-btns {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }
            .sigec-btn-doc {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                background: #eff6ff;
                color: #1d4ed8;
                border: 1px solid #bfdbfe;
                padding: 5px 12px;
                border-radius: 5px;
                font-size: 12px;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.15s;
            }
            .sigec-btn-doc:hover {
                background: #dbeafe;
                color: #1e40af;
            }
            .sigec-btn-doc .dashicons {
                font-size: 15px;
                width: 15px;
                height: 15px;
            }
            .sigec-btn-doc-falta {
                display: inline-flex;
                align-items: center;
                background: #fef2f2;
                color: #dc2626;
                border: 1px solid #fecaca;
                padding: 5px 12px;
                border-radius: 5px;
                font-size: 12px;
                font-weight: 600;
            }

            /* Badges de estado */
            .sigec-badge-eval {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.3px;
                text-transform: uppercase;
            }
            .sigec-badge-eval.adjudicada { background: #dcfce7; color: #15803d; }
            .sigec-badge-eval.rechazada  { background: #fee2e2; color: #dc2626; }
            .sigec-badge-eval.abierta    { background: #dbeafe; color: #1d4ed8; }

            .sigec-count-pill {
                background: #f1f5f9;
                color: #334155;
                padding: 4px 10px;
                border-radius: 999px;
                font-size: 12px;
                font-weight: 600;
            }

            .sigec-btn-evaluar {
                display: inline-block;
                background: #0a4b9c;
                color: #ffffff !important;
                text-decoration: none;
                padding: 7px 14px;
                border-radius: 5px;
                font-weight: 600;
                font-size: 12px;
                transition: background 0.15s;
            }
            .sigec-btn-evaluar:hover { background: #073b7a; }
            .sigec-btn-evaluar.secundario {
                background: #f1f5f9;
                color: #0f172a !important;
                border: 1px solid #cbd5e1;
            }
            .sigec-btn-evaluar.secundario:hover { background: #e2e8f0; }

            /* Panel de Adjudicación (amarillo suave) */
            .sigec-panel-adjudicacion {
                background: #fefce8;
                border: 1px solid #fde68a;
                border-radius: 8px;
                padding: 22px 24px;
            }
            .sigec-panel-adj-title {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 15px;
                font-weight: 700;
                color: #0f172a;
                margin-bottom: 8px;
            }
            .sigec-panel-adj-desc {
                margin: 0 0 18px 0;
                color: #475569;
                font-size: 13px;
            }
            .sigec-panel-adj-form {
                display: flex;
                align-items: center;
                gap: 12px;
                flex-wrap: wrap;
            }
            .sigec-select-proponente {
                min-width: 280px;
                padding: 7px 12px !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 5px !important;
                background: #ffffff !important;
                font-size: 13px !important;
                color: #1e293b !important;
                height: 38px !important;
            }
            .sigec-btn-adjudicar {
                background: #dc2626;
                color: #ffffff;
                border: none;
                padding: 9px 18px;
                border-radius: 5px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.15s;
                height: 38px;
            }
            .sigec-btn-adjudicar:hover { background: #b91c1c; }

            /* Resumen de Adjudicación Oficial de Contrato */
            .sigec-resumen-cierre {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 22px 24px;
            }
            .sigec-resumen-header {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 18px;
            }
            .sigec-resumen-title {
                font-size: 15px;
                font-weight: 700;
                color: #0f172a;
            }
            .sigec-badge-cerrado {
                background: #e2e8f0;
                color: #475569;
                font-size: 10px;
                font-weight: 700;
                padding: 3px 8px;
                border-radius: 4px;
                letter-spacing: 0.4px;
            }
            .sigec-resumen-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                padding-bottom: 18px;
                border-bottom: 1px solid #e2e8f0;
                margin-bottom: 14px;
            }
            .sigec-resumen-label {
                display: block;
                font-size: 11px;
                font-weight: 700;
                color: #64748b;
                text-transform: uppercase;
                margin-bottom: 4px;
            }
            .sigec-resumen-val {
                font-size: 15px;
                font-weight: 700;
                color: #0f172a;
            }
            .sigec-resumen-val.verde { color: #16a34a; }
            .sigec-resumen-footer {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 12px;
                color: #64748b;
            }
        </style>
        <?php
    }
}

new SIGEC_Evaluacion_Admin();

