<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . '../vendor/autoload.php';

class SIGEC_Google_Drive {

    private $client;
    private $folder_id;

    public function __construct() {
        // ID de tu carpeta de prueba en Drive
        $this->folder_id = '1E_mddsObei1kVzIOTlgkTjQyRJGxyGTx'; 

        $this->client = new Google_Client();
        $this->client->setApplicationName('SIGEC WordPress Plugin');
        
        // Carga el nuevo archivo OAuth
        $credentials_path = plugin_dir_path( __FILE__ ) . 'client_secret.json';
        $this->client->setAuthConfig($credentials_path);
        
        // Pide permiso para manejar archivos en Drive
        $this->client->addScope(Google_Service_Drive::DRIVE_FILE);
        
        // Indica que la aplicación se usará para subir cosas fuera de línea
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
        
        // Redirección de vuelta a WordPress después de que el usuario acepta
        $this->client->setRedirectUri('http://localhost/wordpress/wp-admin/');
    }

    /**
     * Inicia el proceso de conexión si no hay un token
     */
    public function obtener_url_autorizacion() {
        return $this->client->createAuthUrl();
    }

    /**
     * Guarda el token cuando Google nos redirige de vuelta
     */
    public function procesar_codigo_autorizacion( $code ) {
        $token = $this->client->fetchAccessTokenWithAuthCode( $code );
        if ( is_array( $token ) && ! isset( $token['error'] ) && ! empty( $token['access_token'] ) ) {
            // Guardamos el token en la base de datos de WordPress
            update_option( 'sigec_google_token', $token );
            return true;
        }
        return false;
    }

    /**
     * Prepara el cliente con el token guardado de forma segura
     */
    public function conectar() {
        $token = get_option( 'sigec_google_token' );
        if ( empty( $token ) || ! is_array( $token ) || empty( $token['access_token'] ) ) {
            delete_option( 'sigec_google_token' );
            return false; // No hay token válido guardado
        }

        try {
            $this->client->setAccessToken( $token );

            // Si el token expiró, usa el refresh token para pedir uno nuevo
            if ( $this->client->isAccessTokenExpired() ) {
                $refresh_token = $this->client->getRefreshToken();
                if ( $refresh_token ) {
                    $nuevo_token = $this->client->fetchAccessTokenWithRefreshToken( $refresh_token );
                    if ( ! is_array( $nuevo_token ) || isset( $nuevo_token['error'] ) || empty( $nuevo_token['access_token'] ) ) {
                        delete_option( 'sigec_google_token' );
                        return false;
                    }
                    // Unimos con el token anterior para no perder el refresh_token original
                    $token_actualizado = array_merge( $token, $nuevo_token );
                    update_option( 'sigec_google_token', $token_actualizado );
                } else {
                    delete_option( 'sigec_google_token' );
                    return false; // Se necesita iniciar sesión de nuevo
                }
            }
            return true; // Conexión exitosa
        } catch ( Exception $e ) {
            delete_option( 'sigec_google_token' );
            return false;
        }
    }

    /**
     * Busca una subcarpeta por nombre dentro de la carpeta indicada (o la raíz)
     * y, si no existe, la crea en Google Drive.
     *
     * @param string      $folder_name Nombre de la carpeta.
     * @param string|null $parent_id   ID de la carpeta padre (null = carpeta raíz).
     * @return array { success: bool, folder_id?: string, link?: string, error?: string }
     */
    public function obtener_o_crear_carpeta( $folder_name, $parent_id = null ) {
        if ( ! $this->conectar() ) {
            return array( 'success' => false, 'error' => 'Plugin no conectado a Google Drive.' );
        }

        $parent = $parent_id ? $parent_id : $this->folder_id;

        try {
            $service = new Google_Service_Drive( $this->client );

            // 1. Buscar si ya existe una carpeta con ese nombre dentro del padre
            $nombre_escapado = str_replace( array( "\\", "'" ), array( "\\\\", "\\'" ), $folder_name );
            $query = sprintf(
                "mimeType = 'application/vnd.google-apps.folder' and name = '%s' and '%s' in parents and trashed = false",
                $nombre_escapado,
                $parent
            );

            $resultados = $service->files->listFiles( array(
                'q'        => $query,
                'fields'   => 'files(id, name, webViewLink)',
                'pageSize' => 1,
            ) );

            $archivos = $resultados->getFiles();
            if ( ! empty( $archivos ) ) {
                return array(
                    'success'   => true,
                    'folder_id' => $archivos[0]->getId(),
                    'link'      => $archivos[0]->getWebViewLink(),
                );
            }

            // 2. Si no existe, crearla
            $folder_metadata = new Google_Service_Drive_DriveFile( array(
                'name'     => $folder_name,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents'  => array( $parent ),
            ) );

            $carpeta = $service->files->create( $folder_metadata, array(
                'fields' => 'id, webViewLink',
            ) );

            // Asignar permiso público de lectura a la carpeta
            $this->asignar_permiso_lectura_publica( $service, $carpeta->id );

            return array(
                'success'   => true,
                'folder_id' => $carpeta->id,
                'link'      => $carpeta->webViewLink,
            );

        } catch ( Exception $e ) {
            return array( 'success' => false, 'error' => $e->getMessage() );
        }
    }

    /**
     * Sube el documento PDF a la carpeta indicada (o a la carpeta raíz por defecto).
     * Inmediatamente después de crearlo, asigna permisos de lectura pública (anyone/reader)
     * para que los enlaces webViewLink puedan abrirse sin requerir inicio de sesión en Google.
     *
     * @param string      $file_path Ruta temporal del archivo en el servidor.
     * @param string      $file_name Nombre final del archivo en Drive.
     * @param string|null $parent_id ID de la carpeta destino (opcional).
     * @return array { success: bool, file_id?: string, link?: string, error?: string }
     */
    public function subir_documento_licitacion( $file_path, $file_name, $parent_id = null ) {
        if ( ! $this->conectar() ) {
            return array( 'success' => false, 'error' => 'Plugin no conectado a Google Drive.' );
        }

        $destino = $parent_id ? $parent_id : $this->folder_id;

        try {
            $service = new Google_Service_Drive($this->client);
            
            $file_metadata = new Google_Service_Drive_DriveFile( array(
                'name'    => $file_name,
                'parents' => array( $destino )
            ) );

            $content = file_get_contents( $file_path );
            
            $file = $service->files->create( $file_metadata, array(
                'data'       => $content,
                'mimeType'   => 'application/pdf',
                'uploadType' => 'multipart',
                'fields'     => 'id, webViewLink'
            ) );

            // Asignar permiso de lectura pública para que el enlace abra directamente sin login
            $this->asignar_permiso_lectura_publica( $service, $file->id );

            return array(
                'success' => true,
                'file_id' => $file->id,
                'link'    => $file->webViewLink
            );

        } catch ( Exception $e ) {
            return array( 'success' => false, 'error' => $e->getMessage() );
        }
    }

    /**
     * Asigna permisos de lectura pública ('anyone', 'reader') a un archivo o carpeta en Drive.
     * Esto asegura que los botones 'Ver RUT', 'Ver Cédula' y 'Ver Propuesta' funcionen
     * para cualquier usuario sin solicitar inicio de sesión en Google.
     *
     * @param Google_Service_Drive $service Instancia del servicio de Google Drive.
     * @param string               $file_id ID del archivo o carpeta.
     * @return bool
     */
    private function asignar_permiso_lectura_publica( $service, $file_id ) {
        try {
            $permiso = new Google_Service_Drive_Permission();
            $permiso->setType( 'anyone' );
            $permiso->setRole( 'reader' );
            $service->permissions->create( $file_id, $permiso );
            return true;
        } catch ( Exception $e ) {
            error_log( 'SIGEC Drive - Error al asignar permiso público al elemento ' . $file_id . ': ' . $e->getMessage() );
            return false;
        }
    }
}