<?php
/**
 * Vista: Listado de tarjetas de convocatorias.
 * Se usa en la carga inicial del catálogo y en la respuesta AJAX del buscador.
 *
 * Variables disponibles:
 * @var array $tarjetas  Lista de convocatorias preparada por sigec_generar_html_tarjetas().
 *                       Cada elemento: post_id, titulo, permalink, estado, clase_badge,
 *                       fecha_formateada, presupuesto_formateado, clase_btn_participar,
 *                       texto_btn_participar, logueado, url_postulacion, url_login.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<?php if ( ! empty( $tarjetas ) ) : ?>
    <?php foreach ( $tarjetas as $t ) : ?>

    <div class="sigec-tarjeta">
        <div class="sigec-tarjeta-info">
            <div class="sigec-meta-superior">
                <span class="sigec-proceso-id">PROCESO: SIGEC-<?php echo esc_html( $t['ano_proceso'] ); ?>-<?php echo esc_html( str_pad( $t['post_id'], 3, '0', STR_PAD_LEFT ) ); ?></span>
                <span class="sigec-badge <?php echo esc_attr( $t['clase_badge'] ); ?>"><?php echo esc_html( $t['estado'] ); ?></span>
            </div>
            <h2 class="sigec-titulo-conv"><?php echo esc_html( $t['titulo'] ); ?></h2>

            <div class="sigec-meta-inferior">
                <div class="sigec-meta-item">
                    <span class="dashicons dashicons-calendar-alt" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    <span>Fecha de Cierre: <strong><?php echo esc_html( $t['fecha_formateada'] ); ?></strong></span>
                </div>
                <div class="sigec-meta-item">
                    <span class="dashicons dashicons-money-alt" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    <span>Presupuesto Oficial: <span class="sigec-monto"><?php echo esc_html( $t['presupuesto_formateado'] ); ?></span></span>
                </div>
            </div>
        </div>

        <!-- Botones Separados -->
        <div class="sigec-acciones">
            <a href="<?php echo esc_url( $t['permalink'] ); ?>" class="sigec-btn-detalles">Ver Detalles</a>

            <?php if ( $t['estado'] === 'Abierta' ) : ?>
                <?php if ( $t['logueado'] ) : ?>
                    <!-- Con sesión: va directo al formulario de postulación -->
                    <a href="<?php echo esc_url( $t['url_postulacion'] ); ?>" class="<?php echo esc_attr( $t['clase_btn_participar'] ); ?>">
                        <?php echo $t['texto_btn_participar']; ?>
                    </a>
                <?php else : ?>
                    <!-- Sin sesión: va al login CON MEMORIA de la convocatoria -->
                    <a href="<?php echo esc_url( $t['url_login'] ); ?>" class="<?php echo esc_attr( $t['clase_btn_participar'] ); ?>">
                        Iniciar Sesión para Participar
                    </a>
                <?php endif; ?>
            <?php else : ?>
                <!-- Convocatoria cerrada -->
                <a href="#" class="<?php echo esc_attr( $t['clase_btn_participar'] ); ?>" tabindex="-1">
                    <?php echo $t['texto_btn_participar']; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php endforeach; ?>
<?php else : ?>
    <p style="padding: 40px 0; text-align: center; font-size: 16px; color: #6b7280;">No se encontraron convocatorias que coincidan con su búsqueda.</p>
<?php endif; ?>

