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

    <div class="sigec-dash-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
            <h3 style="margin: 0;"><span class="dashicons dashicons-portfolio" style="color: #0a4b9c; width: 24px; height: 24px; font-size: 24px;"></span> Mis Propuestas Enviadas</h3>
            <a href="<?php echo esc_url( site_url( '/portal-de-contratacion/' ) ); ?>" class="sigec-btn-participar" style="text-decoration: none; padding: 8px 16px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-search" style="font-size: 16px; width: 16px; height: 16px;"></span> Explorar Convocatorias
            </a>
        </div>
        <?php
        $mis_propuestas = get_posts( array(
            'post_type'   => 'propuesta',
            'post_status' => 'publish',
            'author'      => $current_user->ID,
            'numberposts' => -1,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ) );

        if ( ! empty( $mis_propuestas ) ) :
            foreach ( $mis_propuestas as $prop ) :
                $conv_id        = intval( get_post_meta( $prop->ID, 'convocatoria_id', true ) );
                $valor_ofertado = get_post_meta( $prop->ID, 'valor_ofertado', true );
                $link_rut       = get_post_meta( $prop->ID, 'link_rut', true );
                $link_cedula    = get_post_meta( $prop->ID, 'link_cedula', true );
                $link_propuesta = get_post_meta( $prop->ID, 'link_propuesta', true );
                $titulo_conv    = $conv_id ? get_the_title( $conv_id ) : $prop->post_title;
                $ano_conv       = $conv_id ? get_the_date( 'Y', $conv_id ) : get_the_date( 'Y', $prop->ID );
                ?>
                <div class="sigec-tarjeta" style="padding: 20px 0;">
                    <div class="sigec-tarjeta-info">
                        <div class="sigec-meta-superior">
                            <span class="sigec-proceso-id">PROCESO: SIGEC-<?php echo esc_html( $ano_conv ); ?>-<?php echo esc_html( str_pad( $conv_id, 3, '0', STR_PAD_LEFT ) ); ?></span>
                            <span class="sigec-badge abierta">Recibida</span>
                        </div>
                        <h4 style="font-size: 18px; font-weight: 700; margin: 0 0 10px 0; color: inherit;"><?php echo esc_html( $titulo_conv ); ?></h4>
                        <div class="sigec-meta-inferior">
                            <span>Enviada el: <strong><?php echo esc_html( get_the_date( 'd/m/Y h:i A', $prop->ID ) ); ?></strong></span>
                            <span>Valor Ofertado: <span class="sigec-monto">$<?php echo esc_html( $valor_ofertado ); ?> COP</span></span>
                        </div>
                    </div>
                    <div class="sigec-acciones">
                        <?php if ( $link_rut ) : ?>
                            <a href="<?php echo esc_url( $link_rut ); ?>" target="_blank" rel="noopener noreferrer" class="sigec-btn-detalles">Ver RUT</a>
                        <?php endif; ?>
                        <?php if ( $link_cedula ) : ?>
                            <a href="<?php echo esc_url( $link_cedula ); ?>" target="_blank" rel="noopener noreferrer" class="sigec-btn-detalles">Ver Cédula</a>
                        <?php endif; ?>
                        <?php if ( $link_propuesta ) : ?>
                            <a href="<?php echo esc_url( $link_propuesta ); ?>" target="_blank" rel="noopener noreferrer" class="sigec-btn-participar">Ver Propuesta</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            endforeach;
        else :
        ?>
            <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 40px; text-align: center; border-radius: 6px;" class="dark:bg-gray-800 dark:border-gray-700">
                <span class="dashicons dashicons-info" style="color: #94a3b8; font-size: 32px; width: 32px; height: 32px; margin-bottom: 15px; display: inline-block;"></span>
                <p style="color: #64748b; font-size: 16px; margin: 0 0 15px 0;">Aún no ha enviado propuestas económicas.</p>
                <a href="<?php echo esc_url( site_url( '/portal-de-contratacion/' ) ); ?>" class="sigec-btn-portal" style="margin-top: 0;">
                    <span class="dashicons dashicons-portfolio"></span> Explorar Convocatorias Abiertas
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

