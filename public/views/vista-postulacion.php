<?php
/**
 * Vista: Formulario de postulación  [sigec_formulario_postulacion]
 *
 * Variables disponibles:
 * @var int    $convocatoria_id   ID de la convocatoria (validado).
 * @var string $titulo            Título de la convocatoria.
 * @var string $fecha_formateada  Fecha límite formateada.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
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
            <small style="color: #64748b;">Referencia oficial: SIGEC-<?php echo date( 'Y' ); ?>-<?php echo str_pad( $convocatoria_id, 3, '0', STR_PAD_LEFT ); ?></small>
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

