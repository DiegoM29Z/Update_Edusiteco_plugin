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
        <a href="<?php echo esc_url( wp_logout_url( home_url( '/panel-de-proveedores/' ) ) ); ?>" class="sigec-btn-logout">
            <span class="dashicons dashicons-external"></span> Cerrar Sesión
        </a>
    </div>

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
        <h3><span class="dashicons dashicons-portfolio" style="color: #0a4b9c; width: 24px; height: 24px; font-size: 24px;"></span> Mis Propuestas Enviadas</h3>
        <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 40px; text-align: center; border-radius: 6px;" class="dark:bg-gray-800 dark:border-gray-700">
            <span class="dashicons dashicons-info" style="color: #94a3b8; font-size: 32px; width: 32px; height: 32px; margin-bottom: 15px; display: inline-block;"></span>
            <p style="color: #64748b; font-size: 16px; margin: 0;">Próximamente aquí verá el historial y estado de todas sus postulaciones.</p>
        </div>
    </div>
</div>

