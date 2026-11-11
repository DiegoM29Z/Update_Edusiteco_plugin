<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SIGEC_Gestion_Proveedores {

    public function __construct() {
        // Agregar el menú al panel de administración
        add_action( 'admin_menu', array( $this, 'agregar_menu_proveedores' ) );
        // Procesar las acciones de aprobar/rechazar antes de que cargue la página
        add_action( 'admin_init', array( $this, 'procesar_acciones_proveedores' ) );
    }

    public function agregar_menu_proveedores() {
        // Se ubicará debajo del menú principal "SIGEC Convocatorias"
        add_submenu_page(
            'edit.php?post_type=convocatoria', // Parent slug
            'Gestión de Proveedores',         // Título de la página
            'Proveedores',                    // Título del menú
            'manage_options',                 // Capacidad (Solo Rectora/Admin)
            'sigec-proveedores',              // Slug del menú
            array( $this, 'renderizar_pagina_proveedores' )
        );
    }

    public function procesar_acciones_proveedores() {
        // Verificar que estamos en la página correcta y tenemos una acción
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'sigec-proveedores' || ! isset( $_GET['action'] ) || ! isset( $_GET['user_id'] ) ) {
            return;
        }

        // Seguridad
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'accion_proveedor_' . $_GET['user_id'] ) ) {
            wp_die( 'Acción no autorizada.' );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'No tienes permisos para realizar esta acción.' );
        }

        $user_id = intval( $_GET['user_id'] );
        $accion  = $_GET['action'];

        if ( $accion === 'aprobar' ) {
            update_user_meta( $user_id, 'sigec_estado_cuenta', 'aprobado' );
            // Aquí en un futuro puedes cambiarle el rol de 'subscriber' a 'proveedor'
            wp_redirect( add_query_arg( 'mensaje', 'aprobado', remove_query_arg( array('action', 'user_id', '_wpnonce') ) ) );
            exit;
        } elseif ( $accion === 'rechazar' ) {
            update_user_meta( $user_id, 'sigec_estado_cuenta', 'rechazado' );
            wp_redirect( add_query_arg( 'mensaje', 'rechazado', remove_query_arg( array('action', 'user_id', '_wpnonce') ) ) );
            exit;
        }
    }

    public function renderizar_pagina_proveedores() {
        $args = array(
            'meta_key'     => 'sigec_estado_cuenta',
            'meta_compare' => 'EXISTS',
        );
        $user_query = new WP_User_Query( $args );
        $usuarios   = $user_query->get_results();
        $total_prov = is_array( $usuarios ) ? count( $usuarios ) : 0;
        ?>
        <style>
            .sigec-prov-wrap {
                margin: 25px 20px 40px 2px;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
            .sigec-prov-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                margin-bottom: 22px;
                flex-wrap: wrap;
                gap: 15px;
            }
            .sigec-prov-title {
                font-size: 24px !important;
                font-weight: 700 !important;
                color: #0f172a;
                margin: 0 0 6px 0 !important;
                padding: 0 !important;
                line-height: 1.2;
            }
            .sigec-prov-subtitle {
                margin: 0;
                color: #64748b;
                font-size: 14px;
            }
            .sigec-prov-banner {
                border-radius: 6px;
                padding: 13px 16px;
                font-size: 13px;
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 20px;
                font-weight: 500;
            }
            .sigec-prov-banner.exito {
                background: #dcfce7;
                border: 1px solid #4ade80;
                color: #166534;
            }
            .sigec-prov-banner.alerta {
                background: #fef2f2;
                border: 1px solid #fecaca;
                color: #991b1b;
            }
            .sigec-prov-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            }
            .sigec-prov-table {
                width: 100%;
                border-collapse: collapse;
                text-align: left;
            }
            .sigec-prov-table thead tr {
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
            }
            .sigec-prov-table th {
                padding: 13px 18px;
                font-size: 12px;
                font-weight: 700;
                color: #475569;
            }
            .sigec-prov-table td {
                padding: 16px 18px;
                border-bottom: 1px solid #f1f5f9;
                vertical-align: middle;
                font-size: 13px;
                color: #334155;
            }
            .sigec-prov-table tbody tr:last-child td {
                border-bottom: none;
            }
            .sigec-prov-table tbody tr:hover {
                background-color: #f8fafc;
            }
            .sigec-prov-badge {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.3px;
                text-transform: uppercase;
            }
            .sigec-prov-badge.aprobado { background: #dcfce7; color: #15803d; }
            .sigec-prov-badge.pendiente { background: #fef3c7; color: #b45309; }
            .sigec-prov-badge.rechazado { background: #fee2e2; color: #dc2626; }

            .sigec-prov-actions {
                display: flex;
                justify-content: flex-end;
                gap: 8px;
            }
            .sigec-btn-prov {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 6px 13px;
                border-radius: 5px;
                font-size: 12px;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.15s;
            }
            .sigec-btn-prov.aprobar {
                background: #0a4b9c;
                color: #ffffff !important;
                border: 1px solid #0a4b9c;
            }
            .sigec-btn-prov.aprobar:hover {
                background: #073b7a;
            }
            .sigec-btn-prov.rechazar {
                background: #fef2f2;
                color: #dc2626 !important;
                border: 1px solid #fecaca;
            }
            .sigec-btn-prov.rechazar:hover {
                background: #fee2e2;
                color: #b91c1c !important;
            }
            .sigec-count-pill {
                background: #e2e8f0;
                color: #0f172a;
                padding: 5px 12px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 600;
            }
        </style>

        <div class="wrap sigec-prov-wrap">
            <div class="sigec-prov-header">
                <div>
                    <h1 class="sigec-prov-title">Gestión de Proveedores y Contratistas</h1>
                    <p class="sigec-prov-subtitle">Revise la información de las empresas registradas y habilite su acceso al portal de postulación oficial.</p>
                </div>
                <div>
                    <span class="sigec-count-pill"><?php echo intval( $total_prov ); ?> <?php echo $total_prov === 1 ? 'proveedor registrado' : 'proveedores registrados'; ?></span>
                </div>
            </div>

            <?php if ( isset( $_GET['mensaje'] ) ) : ?>
                <?php if ( $_GET['mensaje'] === 'aprobado' ) : ?>
                    <div class="sigec-prov-banner exito">
                        <span class="dashicons dashicons-yes-alt" style="color: #16a34a;"></span>
                        <span><strong>Proveedor aprobado correctamente.</strong> Ya tiene acceso habilitado para iniciar sesión y presentar ofertas.</span>
                    </div>
                <?php elseif ( $_GET['mensaje'] === 'rechazado' ) : ?>
                    <div class="sigec-prov-banner alerta">
                        <span class="dashicons dashicons-warning" style="color: #dc2626;"></span>
                        <span><strong>Acceso restringido.</strong> El estado del proveedor fue actualizado a rechazado.</span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="sigec-prov-card">
                <table class="sigec-prov-table">
                    <thead>
                        <tr>
                            <th>Razón Social / Empresa</th>
                            <th>NIT / Cédula</th>
                            <th>Representante Legal</th>
                            <th>Contacto</th>
                            <th>Estado</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $usuarios ) ) : ?>
                            <?php foreach ( $usuarios as $user ) :
                                $razon_social = get_user_meta( $user->ID, 'sigec_razon_social', true ) ?: $user->display_name;
                                $nit          = get_user_meta( $user->ID, 'sigec_nit', true ) ?: '—';
                                $rep_legal    = get_user_meta( $user->ID, 'sigec_rep_legal', true ) ?: '—';
                                $telefono     = get_user_meta( $user->ID, 'sigec_telefono', true );
                                $estado       = get_user_meta( $user->ID, 'sigec_estado_cuenta', true ) ?: 'pendiente';

                                $clase_badge = 'pendiente';
                                if ( $estado === 'aprobado' ) {
                                    $clase_badge = 'aprobado';
                                } elseif ( $estado === 'rechazado' ) {
                                    $clase_badge = 'rechazado';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <strong style="color: #0f172a; font-size: 14px; display: block; margin-bottom: 2px;"><?php echo esc_html( $razon_social ); ?></strong>
                                        <span style="color: #64748b; font-size: 12px;">Usuario: <?php echo esc_html( $user->user_login ); ?></span>
                                    </td>
                                    <td style="font-weight: 600; color: #475569;">
                                        <?php echo esc_html( $nit ); ?>
                                    </td>
                                    <td style="color: #334155;">
                                        <?php echo esc_html( $rep_legal ); ?>
                                    </td>
                                    <td>
                                        <span style="color: #0f172a; font-weight: 500; display: block;"><?php echo esc_html( $user->user_email ); ?></span>
                                        <?php if ( $telefono ) : ?>
                                            <span style="color: #64748b; font-size: 12px;"><?php echo esc_html( $telefono ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="sigec-prov-badge <?php echo esc_attr( $clase_badge ); ?>">
                                            <?php echo esc_html( $estado ); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="sigec-prov-actions">
                                            <?php if ( $estado === 'pendiente' || $estado === 'rechazado' ) : ?>
                                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=convocatoria&page=sigec-proveedores&action=aprobar&user_id=' . $user->ID ), 'accion_proveedor_' . $user->ID ) ); ?>" class="sigec-btn-prov aprobar">
                                                    Aprobar
                                                </a>
                                            <?php endif; ?>

                                            <?php if ( $estado === 'pendiente' || $estado === 'aprobado' ) : ?>
                                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=convocatoria&page=sigec-proveedores&action=rechazar&user_id=' . $user->ID ), 'accion_proveedor_' . $user->ID ) ); ?>" class="sigec-btn-prov rechazar">
                                                    Rechazar
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px 20px; color: #64748b;">
                                    No hay proveedores registrados aún.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}
new SIGEC_Gestion_Proveedores();