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
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Gestión de Proveedores y Contratistas</h1>
            <p>Revise la información de los usuarios registrados y apruebe su acceso al portal de postulación oficial.</p>

            <?php
            // Mostrar mensajes de éxito
            if ( isset( $_GET['mensaje'] ) ) {
                if ( $_GET['mensaje'] === 'aprobado' ) {
                    echo '<div class="notice notice-success is-dismissible"><p>Proveedor aprobado correctamente. Ya puede iniciar sesión.</p></div>';
                } elseif ( $_GET['mensaje'] === 'rechazado' ) {
                    echo '<div class="notice notice-warning is-dismissible"><p>El acceso del proveedor ha sido denegado.</p></div>';
                }
            }
            ?>

            <table class="wp-list-table widefat fixed striped table-view-list users">
                <thead>
                    <tr>
                        <th style="width: 25%;">Razón Social / Empresa</th>
                        <th style="width: 15%;">NIT / Cédula</th>
                        <th style="width: 20%;">Representante Legal</th>
                        <th style="width: 20%;">Contacto</th>
                        <th style="width: 10%;">Estado</th>
                        <th style="width: 10%;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Consultar todos los usuarios que tienen un estado (solo los que se registraron por nuestro form)
                    $args = array(
                        'meta_key'     => 'sigec_estado_cuenta',
                        'meta_compare' => 'EXISTS'
                    );
                    $user_query = new WP_User_Query( $args );
                    $usuarios = $user_query->get_results();

                    if ( ! empty( $usuarios ) ) {
                        foreach ( $usuarios as $user ) {
                            $razon_social = get_user_meta( $user->ID, 'sigec_razon_social', true );
                            $nit          = get_user_meta( $user->ID, 'sigec_nit', true );
                            $rep_legal    = get_user_meta( $user->ID, 'sigec_rep_legal', true );
                            $telefono     = get_user_meta( $user->ID, 'sigec_telefono', true );
                            $estado       = get_user_meta( $user->ID, 'sigec_estado_cuenta', true );

                            // Colores según el estado
                            $estado_color = '#d97706'; // Amarillo oscuro (Pendiente)
                            if ( $estado === 'aprobado' ) $estado_color = '#15803d'; // Verde
                            if ( $estado === 'rechazado' ) $estado_color = '#b91c1c'; // Rojo
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html( $razon_social ); ?></strong><br>
                                    <span style="color: #64748b; font-size: 12px;">Usuario: <?php echo esc_html( $user->user_login ); ?></span>
                                </td>
                                <td><?php echo esc_html( $nit ); ?></td>
                                <td><?php echo esc_html( $rep_legal ); ?></td>
                                <td>
                                    <?php echo esc_html( $user->user_email ); ?><br>
                                    <?php echo esc_html( $telefono ); ?>
                                </td>
                                <td>
                                    <span style="background-color: <?php echo esc_attr( $estado_color ); ?>; color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase;">
                                        <?php echo esc_html( $estado ); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ( $estado === 'pendiente' || $estado === 'rechazado' ) : ?>
                                        <a href="<?php echo wp_nonce_url( admin_url( 'edit.php?post_type=convocatoria&page=sigec-proveedores&action=aprobar&user_id=' . $user->ID ), 'accion_proveedor_' . $user->ID ); ?>" class="button button-primary button-small">Aprobar</a>
                                    <?php endif; ?>
                                    
                                    <?php if ( $estado === 'pendiente' || $estado === 'aprobado' ) : ?>
                                        <a href="<?php echo wp_nonce_url( admin_url( 'edit.php?post_type=convocatoria&page=sigec-proveedores&action=rechazar&user_id=' . $user->ID ), 'accion_proveedor_' . $user->ID ); ?>" class="button button-small" style="color: #dc3232; border-color: #dc3232;">Rechazar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="6">No hay proveedores registrados aún.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
new SIGEC_Gestion_Proveedores();