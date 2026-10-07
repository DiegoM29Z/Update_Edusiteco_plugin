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
        if ( ! isset( $token['error'] ) ) {
            // Guardamos el token en la base de datos de WordPress
            update_option( 'sigec_google_token', $token );
            return true;
        }
        return false;
    }

    /**
     * Prepara el cliente con el token guardado
     */
    public function conectar() {
        $token = get_option( 'sigec_google_token' );
        if ( empty( $token ) ) {
            return false; // No hay token guardado
        }
        
        $this->client->setAccessToken( $token );
        
        // Si el token expiró, usa el refresh token para pedir uno nuevo
        if ( $this->client->isAccessTokenExpired() ) {
            if ( $this->client->getRefreshToken() ) {
                $nuevo_token = $this->client->fetchAccessTokenWithRefreshToken( $this->client->getRefreshToken() );
                update_option( 'sigec_google_token', $nuevo_token );
            } else {
                return false; // Se necesita iniciar sesión de nuevo
            }
        }
        return true; // Conexión exitosa
    }

    /**
     * Sube el documento a la carpeta
     */
    public function subir_documento_licitacion( $file_path, $file_name ) {
        if ( ! $this->conectar() ) {
            return array( 'success' => false, 'error' => 'Plugin no conectado a Google Drive.' );
        }

        try {
            $service = new Google_Service_Drive($this->client);
            
            $file_metadata = new Google_Service_Drive_DriveFile( array(
                'name'    => $file_name,
                'parents' => array( $this->folder_id )
            ) );

            $content = file_get_contents( $file_path );
            
            $file = $service->files->create( $file_metadata, array(
                'data'       => $content,
                'mimeType'   => 'application/pdf',
                'uploadType' => 'multipart',
                'fields'     => 'id, webViewLink'
            ) );

            return array(
                'success' => true,
                'file_id' => $file->id,
                'link'    => $file->webViewLink
            );

        } catch ( Exception $e ) {
            return array( 'success' => false, 'error' => $e->getMessage() );
        }
    }
}