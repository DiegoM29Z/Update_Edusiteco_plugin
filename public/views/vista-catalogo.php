<?php
/**
 * Vista: Catálogo de convocatorias  [sigec_convocatorias_abiertas]
 *
 * Variables disponibles:
 * @var string $html_tarjetas  HTML de las tarjetas (generado por sigec_generar_html_tarjetas()).
 *
 * Estilos: assets/css/sigec-public.css  ·  Buscador AJAX: assets/js/sigec-buscador.js
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
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

    <!-- Controles de Búsqueda -->
    <div class="sigec-controles">
        <input type="text" id="sigec-input-texto" class="sigec-input-busqueda" placeholder="Buscar convocatorias por título o palabra clave..." style="padding-left: 44px !important;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span class="sigec-proceso-id" style="font-size: 15px; font-weight: 600; text-transform: none; color: inherit;">Estado:</span>
            <select id="sigec-select-estado" class="sigec-select-estado">
                <option value="todas">Abiertas y Cerradas</option>
                <option value="abiertas">Solo Abiertas</option>
                <option value="cerradas">Solo Cerradas</option>
            </select>
        </div>
    </div>

    <!-- Contenedor que se actualizará con la búsqueda (AJAX) -->
    <div id="sigec-resultados-contenedor">
        <?php echo $html_tarjetas; ?>
    </div>

    <!-- Alerta de Información -->
    <div class="sigec-alerta">
        <span class="dashicons dashicons-info" style="font-size: 20px; width: 20px; height: 20px; color: #0a4b9c;"></span>
        <span>Recuerde que para presentar propuestas económicas debe estar registrado e iniciar sesión como proveedor autorizado del Estado.</span>
    </div>
</div>

