<?php
/**
 * Vista: Panel del proveedor (Dashboard)  [sigec_panel_proveedor]
 *
 * Variables disponibles:
 * @var WP_User $current_user  Usuario actual.
 * @var string  $razon_social  Razón social (o display_name si no existe).
 * @var string  $nit           NIT ('No registrado' si no existe).
 * @var string  $telefono      Teléfono ('No registrado' si no existe).
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="sigec-dashboard-wrapper">
    <div class="sigec-dashboard-header">
        <div class="sigec-dashboard-title-area">
            <h1>Mi Panel de Proveedor</h1>
            <p>Gestione el perfil de su empresa y revise el estado de sus licitaciones.</p>
        </div>
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <a href="<?php echo esc_url( site_url( '/portal-de-contratacion/' ) ); ?>" class="sigec-btn-portal">
                <span class="dashicons dashicons-arrow-left-alt" style="margin-top: 1px;"></span> Ver Convocatorias Disponibles
            </a>
            <a href="<?php echo esc_url( wp_logout_url( home_url( '/panel-de-proveedores/' ) ) ); ?>" class="sigec-btn-logout">
                <span class="dashicons dashicons-external"></span> Cerrar Sesión
            </a>
        </div>
    </div>

    <?php if ( isset( $_GET['exito_postulacion'] ) && $_GET['exito_postulacion'] == '1' ) : ?>
        <div class="sigec-msg exito" style="display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-yes-alt" style="font-size: 22px; width: 22px; height: 22px;"></span>
            <span>¡Propuesta enviada exitosamente! Sus documentos han sido almacenados en la nube y su postulación quedó registrada oficialmente.</span>
        </div>
    <?php endif; ?>

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

    <h3 class="sigec-section-title"><span class="dashicons dashicons-portfolio" style="color: #0a4b9c; width: 24px; height: 24px; font-size: 24px;"></span> Mis Propuestas Enviadas</h3>
    <?php
    $mis_propuestas = get_posts( array(
        'post_type'   => 'propuesta',
        'post_status' => 'publish',
        'author'      => $current_user->ID,
        'numberposts' => -1,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ) );

    if ( ! empty( $mis_propuestas ) ) : ?>
        <div class="sigec-propuestas-tabla-wrap">
                <table class="sigec-propuestas-tabla">
                    <thead>
                        <tr>
                            <th>NOMBRE DE LA CONVOCATORIA</th>
                            <th>FECHA DE ENVÍO</th>
                            <th>VALOR OFERTADO</th>
                            <th>ESTADO</th>
                            <th style="text-align: right;">DOCUMENTOS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $mis_propuestas as $prop ) :
                            $conv_id          = intval( get_post_meta( $prop->ID, '_sigec_convocatoria_id', true ) ?: get_post_meta( $prop->ID, 'convocatoria_id', true ) );
                            $valor_ofertado   = get_post_meta( $prop->ID, '_sigec_valor_ofertado', true );
                            if ( $valor_ofertado === '' ) {
                                $valor_ofertado = get_post_meta( $prop->ID, 'valor_ofertado', true );
                            }
                            $valor_limpio     = preg_replace( '/[^0-9]/', '', (string) $valor_ofertado );
                            $valor_formateado = $valor_limpio !== '' ? number_format( (float) $valor_limpio, 0, ',', '.' ) : $valor_ofertado;
                            $estado_prop      = get_post_meta( $prop->ID, '_sigec_estado_evaluacion', true ) ?: get_post_meta( $prop->ID, 'estado_propuesta', true ) ?: 'en_revision';
                            $link_rut         = get_post_meta( $prop->ID, '_sigec_link_rut', true ) ?: get_post_meta( $prop->ID, 'link_rut', true );
                            $link_cedula      = get_post_meta( $prop->ID, '_sigec_link_cedula', true ) ?: get_post_meta( $prop->ID, 'link_cedula', true );
                            $link_propuesta   = get_post_meta( $prop->ID, '_sigec_link_propuesta', true ) ?: get_post_meta( $prop->ID, 'link_propuesta', true );
                            $titulo_conv      = $conv_id ? get_the_title( $conv_id ) : $prop->post_title;
                            $ano_conv         = $conv_id ? get_the_date( 'Y', $conv_id ) : get_the_date( 'Y', $prop->ID );

                            if ( $estado_prop === 'adjudicada' ) {
                                $texto_estado = 'Adjudicada';
                                $clase_estado = 'sigec-estado-pill adjudicada';
                            } elseif ( $estado_prop === 'rechazada' ) {
                                $texto_estado = 'Rechazada';
                                $clase_estado = 'sigec-estado-pill rechazada';
                            } else {
                                $texto_estado = 'En revisión';
                                $clase_estado = 'sigec-estado-pill en-revision';
                            }
                            ?>
                            <tr>
                                <td>
                                    <span class="sigec-proceso-id" style="display: block; font-size: 11px; margin-bottom: 4px;">PROCESO: SIGEC-<?php echo esc_html( $ano_conv ); ?>-<?php echo esc_html( str_pad( $conv_id, 3, '0', STR_PAD_LEFT ) ); ?></span>
                                    <strong class="sigec-tabla-titulo"><?php echo esc_html( $titulo_conv ); ?></strong>
                                </td>
                                <td class="sigec-tabla-fecha">
                                    <?php echo esc_html( date_i18n( 'd M Y', strtotime( $prop->post_date ) ) ); ?>
                                    <small style="display: block; font-size: 12px; opacity: 0.75;"><?php echo esc_html( get_the_date( 'h:i A', $prop->ID ) ); ?></small>
                                </td>
                                <td class="sigec-tabla-valor">
                                    $<?php echo esc_html( $valor_formateado ); ?> COP
                                </td>
                                <td>
                                    <span class="<?php echo esc_attr( $clase_estado ); ?>"><?php echo esc_html( $texto_estado ); ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="sigec-acciones" style="justify-content: flex-end;">
                                        <?php if ( $link_rut ) : ?>
                                            <a href="<?php echo esc_url( $link_rut ); ?>" target="_blank" rel="noopener noreferrer" class="sigec-btn-detalles" style="padding: 7px 14px; font-size: 13px;">Ver RUT</a>
                                        <?php endif; ?>
                                        <?php if ( $link_cedula ) : ?>
                                            <a href="<?php echo esc_url( $link_cedula ); ?>" target="_blank" rel="noopener noreferrer" class="sigec-btn-detalles" style="padding: 7px 14px; font-size: 13px;">Ver Cédula</a>
                                        <?php endif; ?>
                                        <?php if ( $link_propuesta ) : ?>
                                            <a href="<?php echo esc_url( $link_propuesta ); ?>" target="_blank" rel="noopener noreferrer" class="sigec-btn-participar" style="padding: 7px 14px; font-size: 13px;">Ver Propuesta</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
        </div>
    <?php else : ?>
        <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 40px; text-align: center; border-radius: 6px;" class="dark:bg-gray-800 dark:border-gray-700">
            <span class="dashicons dashicons-info" style="color: #94a3b8; font-size: 32px; width: 32px; height: 32px; margin-bottom: 15px; display: inline-block;"></span>
            <p style="color: #64748b; font-size: 16px; margin: 0 0 15px 0;">Aún no ha enviado propuestas económicas.</p>
            <a href="<?php echo esc_url( site_url( '/portal-de-contratacion/' ) ); ?>" class="sigec-btn-portal" style="margin-top: 0;">
                <span class="dashicons dashicons-portfolio"></span> Explorar Convocatorias Abiertas
            </a>
        </div>
    <?php endif; ?>
</div>

