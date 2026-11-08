<?php
/**
 * Vista: Acceso a la plataforma (Login + Registro)  [sigec_acceso_proveedores]
 * Los formularios los procesa SIGEC_Autenticacion (includes/class-sigec-auth.php).
 *
 * Variables disponibles:
 * @var string $mensaje_login     Mensaje de error del login ('' si no hay).
 * @var string $mensaje_registro  Mensaje del registro ('' si no hay).
 * @var string $tipo_mensaje      'exito' | 'error' (clase CSS del mensaje de registro).
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="sigec-auth-wrapper">
    <div class="sigec-auth-header">
        <span>Portal Único de Contratación Educativa</span>
        <h1>Acceso a la Plataforma de Proveedores</h1>
    </div>

    <div class="sigec-auth-box">
        <!-- Columna: Iniciar Sesión -->
        <div class="sigec-auth-col sigec-auth-col-login">
            <h3>Iniciar Sesión</h3>
            <p class="desc">Si ya posee una cuenta registrada en el sistema.</p>

            <?php if ( $mensaje_login ) : ?>
                <div class="sigec-msg error"><?php echo esc_html( $mensaje_login ); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php wp_nonce_field( 'sigec_iniciar_sesion', 'sigec_login_nonce' ); ?>
                <div class="sigec-form-group">
                    <label for="log_email">Correo Electrónico <span>*</span></label>
                    <input type="email" id="log_email" name="log_email" placeholder="ejemplo@proveedor.com" required>
                </div>
                <div class="sigec-form-group">
                    <label for="log_password">Contraseña <span>*</span></label>
                    <input type="password" id="log_password" name="log_password" required>
                </div>
                <button type="submit" name="sigec_login_submit" class="sigec-btn-auth">Iniciar Sesión</button>
                <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="sigec-link-olvido">¿Olvidaste tu contraseña?</a>
            </form>
        </div>

        <!-- Columna: Registro de Proveedor -->
        <div class="sigec-auth-col">
            <h3>Registro de Proveedor</h3>
            <p class="desc">Cree su perfil para postular propuestas oficiales.</p>

            <?php if ( $mensaje_registro ) : ?>
                <div class="sigec-msg <?php echo $tipo_mensaje; ?>"><?php echo esc_html( $mensaje_registro ); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php wp_nonce_field( 'sigec_registrar_proveedor', 'sigec_registro_nonce' ); ?>
                <div class="sigec-form-row">
                    <div>
                        <label for="reg_razon">Razón Social <span>*</span></label>
                        <input type="text" id="reg_razon" name="reg_razon" placeholder="Empresa S.A.S" required>
                    </div>
                    <div>
                        <label for="reg_nit">NIT <span>*</span></label>
                        <input type="text" id="reg_nit" name="reg_nit" placeholder="900.123.456-7" required>
                    </div>
                </div>
                <div class="sigec-form-group">
                    <label for="reg_rep">Representante Legal <span>*</span></label>
                    <input type="text" id="reg_rep" name="reg_rep" placeholder="Nombre completo" required>
                </div>
                <div class="sigec-form-row">
                    <div>
                        <label for="reg_email">Correo Electrónico <span>*</span></label>
                        <input type="email" id="reg_email" name="reg_email" placeholder="contacto@empresa.com" required>
                    </div>
                    <div>
                        <label for="reg_telefono">Teléfono <span>*</span></label>
                        <input type="text" id="reg_telefono" name="reg_telefono" placeholder="+57 300 123 4567" required>
                    </div>
                </div>
                <div class="sigec-form-group">
                    <label for="reg_password">Contraseña de la Cuenta <span>*</span></label>
                    <input type="password" id="reg_password" name="reg_password" placeholder="Mínimo 8 caracteres" minlength="8" required>
                </div>
                <button type="submit" name="sigec_registro_submit" class="sigec-btn-auth">Crear Cuenta de Proveedor</button>
            </form>
        </div>
    </div>
</div>

