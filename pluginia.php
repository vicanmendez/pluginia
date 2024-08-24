<?php
/**
 * Plugin Name: Pluginia
 * Plugin URI: http://tusitio.com/pluginia
 * Description: Un plugin que muestra contenido sólo para usuarios autenticados.
 * Version: 1.2
 * Author: Tu Nombre
 * Author URI: http://tusitio.com
 */

if (!defined('ABSPATH')) {
    exit;
}

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
            return pluginia_render_menu() . pluginia_render_content();
            ;
        } else {
            return '<h2>No has configurado tu API Key </h2> <br> Puedes definirla en <a href=\http://localhost/wordpressai/wp-admin/profile.php\> Editar usuario';
        }
    } else {
        return '<h2>Debes estar autenticado para ver este contenido.</h2>';
    }
}
add_shortcode('pluginia_content', 'pluginia_content_shortcode');

// Redireccionar usuarios no autenticados
function pluginia_redirect_if_not_logged_in() {
    if (is_page('pluginia') && !is_user_logged_in()) {
        wp_redirect(wp_login_url(get_permalink()));
        exit;
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

// Crear menú responsivo
function pluginia_render_menu() {
    ob_start(); ?>
    <div class="pluginia-menu">
        <a href="?page=chat">Chat</a>
        <a href="?page=chats-anteriores">Chats Anteriores</a>
        <a href="?page=categorias">Categorías</a>
    </div>
    <?php
    return ob_get_clean();
}

// Renderizar el contenido según la página seleccionada
function pluginia_render_content() {
    $page = isset($_GET['page']) ? $_GET['page'] : 'chat';

    switch ($page) {
        case 'chat':
            return pluginia_load_template('chat.php');
        case 'chats-anteriores':
            return pluginia_load_template('chats-anteriores.php');
        case 'categorias':
            return pluginia_load_template('categorias.php');
        default:
            return pluginia_load_template('chat.php');
    }
}

// Cargar las plantillas de las páginas
function pluginia_load_template($template_name) {
    $template_path = plugin_dir_path(__FILE__) . 'templates/' . $template_name;
    if (file_exists($template_path)) {
        ob_start();
        include $template_path;
        return ob_get_clean();
    }
    return '<h2>Plantilla no encontrada.</h2>';
}

// Crear la página principal /pluginia
function pluginia_create_page() {
    $page = array(
        'post_title' => 'Pluginia',
        'post_content' => '[pluginia_content]',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_name' => 'pluginia'
    );

    $existing_page = get_page_by_path('pluginia');
    if (!$existing_page) {
        wp_insert_post($page);
    }
}
register_activation_hook(__FILE__, 'pluginia_create_page');

// Borrar la página al desactivar el plugin
function pluginia_remove_page() {
    $page = get_page_by_path('pluginia');
    if ($page) {
        wp_delete_post($page->ID, true);
    }
}
register_deactivation_hook(__FILE__, 'pluginia_remove_page');

