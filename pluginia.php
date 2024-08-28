<?php
/**
 * Plugin Name: Pluginia
 * Plugin URI: http://tusitio.com/pluginia
 * Description: Un plugin que muestra contenido sólo para usuarios autenticados y permite chatear con documentos PDF utilizando ChatPDF.
 * Version: 1.3
 * Author: Tu Nombre
 * Author URI: http://tusitio.com
 */

if (!defined('ABSPATH')) {
    exit;
}

// Iniciar sesión para manejar datos de sesión
function pluginia_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'pluginia_start_session', 1); // Usa prioridad 1 para asegurar que se inicie antes de otros procesos


function pluginia_display_main_menu() {
    wp_nav_menu(array(
        'theme_location' => 'primary', // Asegúrate de que 'primary' es el nombre del menú registrado en tu tema
        'container' => 'nav',
        'container_class' => 'main-menu',
    ));
}
add_action('wp_footer', 'pluginia_display_main_menu');



// Incluir los estilos CSS
function pluginia_enqueue_styles() {
    wp_enqueue_style('pluginia-styles', plugins_url('assets/css/pluginia_styles.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'pluginia_enqueue_styles');

// Crear el shortcode [pluginia_content]
function pluginia_content_shortcode() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'pluginia_api_key', true);

        if ($api_key) {
            return pluginia_menu();
        } else {
            return '<h2>No has configurado tu API Key</h2> <br> Puedes definirla en <a href="' . get_edit_profile_url($user_id) . '"> Editar usuario</a>';
        }
    } else {
        return '<h2>Debes estar autenticado para ver este contenido.</h2>';
    }
}
add_shortcode('pluginia_content', 'pluginia_content_shortcode');

// Redireccionar usuarios no autenticados
function pluginia_redirect_if_not_logged_in() {
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        } else {
            // Para depuración, solo muestra este mensaje si el usuario está autenticado
        }
}
add_action('template_redirect', 'pluginia_redirect_if_not_logged_in');


// Añadir campo de API Key en el perfil de usuario
function pluginia_add_api_key_field($user) {
    ?>
    <h3>Configuración del Pluginia</h3>
    <table class="form-table">
        <tr>
            <th><label for="pluginia_api_key">API Key de ChatPDF</label></th>
            <td>
                <input type="text" name="pluginia_api_key" id="pluginia_api_key" value="<?php echo esc_attr(get_user_meta($user->ID, 'pluginia_api_key', true)); ?>" class="regular-text" /><br />
                <span class="description">Introduce tu API Key de ChatPDF aquí.</span>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'pluginia_add_api_key_field');
add_action('edit_user_profile', 'pluginia_add_api_key_field');

// Guardar API Key cuando se actualiza el perfil de usuario
function pluginia_save_api_key_field($user_id) {
    if (current_user_can('edit_user', $user_id)) {
        update_user_meta($user_id, 'pluginia_api_key', sanitize_text_field($_POST['pluginia_api_key']));
    }
}
add_action('personal_options_update', 'pluginia_save_api_key_field');
add_action('edit_user_profile_update', 'pluginia_save_api_key_field');




// Registrar las rutas de las páginas
function pluginia_register_routes() {
    add_rewrite_rule('^pluginia/?$', 'index.php?pluginia_page=chat', 'top');
    add_rewrite_rule('^pluginia/chat/?$', 'index.php?pluginia_page=chat', 'top');
    add_rewrite_rule('^pluginia/categorias/?$', 'index.php?pluginia_page=categorias', 'top');
    add_rewrite_rule('^pluginia/chats_anteriores/?$', 'index.php?pluginia_page=chats_anteriores', 'top');
}
add_action('init', 'pluginia_register_routes');

// Añadir query vars
function pluginia_query_vars($vars) {
    $vars[] = 'pluginia_page';
    return $vars;
}
add_filter('query_vars', 'pluginia_query_vars');

// Cargar la plantilla adecuada
function pluginia_template_include($template) {
    $pluginia_page = get_query_var('pluginia_page');
    if ($pluginia_page) {
        $new_template = plugin_dir_path(__FILE__) . 'templates/' . $pluginia_page . '.php';
        if (file_exists($new_template)) {
            return $new_template;
        }
    }
    return $template;
}
add_filter('template_include', 'pluginia_template_include');

// Crear el menú responsivo
function pluginia_menu() {
    echo '<nav>';
    echo '<ul>';
    echo '<li><a href="' . home_url('/pluginia/chat') . '">Chat</a></li>';
    echo '<li><a href="' . home_url('/pluginia/categorias') . '">Categorías</a></li>';
    echo '<li><a href="' . home_url('/pluginia/chats_anteriores') . '">Chats anteriores</a></li>';
    echo '</ul>';
    echo '</nav>';
}
add_action('wp_footer', 'pluginia_menu');

// Función para manejar la subida de PDF y envío a la API
function pluginia_handle_pdf_upload() {
    if (isset($_POST['upload_pdf']) && is_user_logged_in()) {
        if (!empty($_FILES['pdf_file']['name']) && $_FILES['pdf_file']['type'] === 'application/pdf') {
            $user_id = get_current_user_id();
            $api_key = get_user_meta($user_id, 'pluginia_api_key', true);
            $file_tmp = $_FILES['pdf_file']['tmp_name'];
            $file_name = $_FILES['pdf_file']['name'];

            $response = pluginia_upload_pdf_to_chatpdf($file_tmp, $file_name, $api_key);

            if ($response && $response['success']) {
                $_SESSION['document_id'] = $response['document_id']; // Guardar el ID del documento
                echo '<div class="alert alert-success">Archivo PDF subido exitosamente.</div>';
            } else {
                echo '<div class="alert alert-danger">Error al subir el archivo PDF.</div>';
            }
        } else {
            echo '<div class="alert alert-danger">Por favor, selecciona un archivo PDF válido.</div>';
        }
    }

    if (isset($_POST['send_message']) && is_user_logged_in()) {
        $user_id = get_current_user_id();
        $message = sanitize_text_field($_POST['chat_message']);
        
        if (isset($_SESSION['document_id'])) {
            $document_id = sanitize_text_field($_SESSION['document_id']);
        } else {
            $document_id = null;
        }
    
        if ($message && $document_id) {
            $api_key = get_user_meta($user_id, 'pluginia_api_key', true);
            $response = pluginia_send_message_to_chatpdf($document_id, $message, $api_key);
    
            if ($response['success']) {
                // Guardar el chat en la base de datos
                pluginia_save_chat($user_id, $document_id, $message);
                echo '<div class="alert alert-success">Mensaje enviado exitosamente: ' . esc_html($response['content']) . '</div>';
            } else {
                echo '<div class="alert alert-danger">Error al enviar el mensaje: ' . esc_html($response['error']) . '</div>';
            }
        } else {
            echo '<div class="alert alert-warning">No se ha seleccionado ningún documento para chatear.</div>';
        }
    }
    
}
add_action('wp', 'pluginia_handle_pdf_upload');

// Función para guardar un chat en la base de datos
function pluginia_save_chat($user_id, $document_id, $message) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pluginia_chats';
    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'document_id' => $document_id,
            'message' => $message,
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%s')
    );
}


// Función para subir PDF a la API de ChatPDF
// Función para subir PDF a la API de ChatPDF
function pluginia_upload_pdf_to_chatpdf($file_tmp, $file_name, $api_key) {
    $url = 'https://api.chatpdf.com/v1/sources/add-file';

    // Configurar la solicitud cURL
    $ch = curl_init();

    // Crear el FormData análogo en PHP
    $cfile = new CURLFile($file_tmp, 'application/pdf', $file_name);

    $postfields = array(
        'file' => $cfile
    );

    // Configurar los encabezados de la solicitud, incluyendo la API Key
    $headers = array(
        'x-api-key: ' . $api_key,
        'Content-Type: multipart/form-data'
    );

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Ejecutar la solicitud cURL y obtener la respuesta
    $response = curl_exec($ch);

    // Verificar errores en la solicitud cURL
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return array('success' => false, 'error' => $error_msg);
    }

    curl_close($ch);

    // Decodificar la respuesta JSON de la API
    $response_data = json_decode($response, true);

    // Manejar la respuesta de la API
    if (isset($response_data['sourceId'])) {
        return array('success' => true, 'document_id' => $response_data['sourceId']);
    } else {
        return array('success' => false, 'error' => 'Error en la respuesta de la API');
    }
}

// Función para enviar un mensaje al documento PDF
// Función para enviar un mensaje al documento PDF
function pluginia_send_message_to_chatpdf($document_id, $message, $api_key) {
    $url = 'https://api.chatpdf.com/v1/chats/message';

    // Datos a enviar en el cuerpo de la solicitud
    $data = array(
        'sourceId' => $document_id,
        'messages' => array(
            array(
                'role' => 'user',
                'content' => $message
            )
        )
    );

    // Convertir los datos a formato JSON
    $json_data = json_encode($data);

    // Configurar la solicitud cURL
    $ch = curl_init();

    // Configurar los encabezados de la solicitud, incluyendo la API Key
    $headers = array(
        'x-api-key: ' . $api_key,
        'Content-Type: application/json'
    );

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Ejecutar la solicitud cURL y obtener la respuesta
    $response = curl_exec($ch);

    // Verificar errores en la solicitud cURL
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return array('success' => false, 'error' => $error_msg);
    }

    curl_close($ch);

    // Decodificar la respuesta JSON de la API
    $response_data = json_decode($response, true);

    // Manejar la respuesta de la API
    if (isset($response_data['content'])) {
        return array('success' => true, 'content' => $response_data['content']);
    } else {
        return array('success' => false, 'error' => 'Error en la respuesta de la API');
    }
}



// Funciones CRUD para chats y categorías
function pluginia_create_category($name) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pluginia_categories';
    $wpdb->insert($table_name, array('name' => $name));
}

function pluginia_get_all_categories() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pluginia_categories';
    return $wpdb->get_results("SELECT * FROM $table_name");
}

function pluginia_create_chat($document_id, $chat_content, $category_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pluginia_chats';
    $wpdb->insert($table_name, array(
        'document_id' => $document_id,
        'chat_content' => $chat_content,
        'category_id' => $category_id
    ));
}

function pluginia_get_all_chats() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pluginia_chats';
    return $wpdb->get_results("SELECT * FROM $table_name");
}

// Crear la tabla al activar el plugin
function pluginia_create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$wpdb->prefix}pluginia_chats (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        document_id varchar(100) NOT NULL,
        chat_content text NOT NULL,
        category_id mediumint(9),
        PRIMARY KEY  (id)
    ) $charset_collate;

    CREATE TABLE {$wpdb->prefix}pluginia_categories (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'pluginia_create_tables');

// Crear la página principal /pluginia
function pluginia_create_page() {
    $existing_page = get_page_by_path('pluginia');
    
    if (!$existing_page) {
        $page = array(
            'post_title'    => 'Pluginia',
            'post_content'  => '[pluginia_content]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => 'pluginia'
        );
        
        // Crear la página si no existe
        wp_insert_post($page);
    } else {
        echo "<p>La página pluginia ya existe. Slug: pluginia</p>";
    }

register_activation_hook(__FILE__, 'pluginia_create_page');}
// Borrar la página y las tablas al desactivar el plugin
function pluginia_remove_page_and_tables() {
    $page = get_page_by_path('pluginia');
    if ($page) {
        wp_delete_post($page->ID, true);
    }

    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pluginia_chats");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pluginia_categories");
}
register_deactivation_hook(__FILE__, 'pluginia_remove_page_and_tables');