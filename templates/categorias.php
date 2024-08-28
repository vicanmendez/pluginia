<?php
// Incluir el encabezado del tema
get_header();

// Manejo de acciones de categoría (añadir, editar, eliminar)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pluginia_categories';

    // Añadir nueva categoría
    if (isset($_POST['add_category'])) {
        $category_name = sanitize_text_field($_POST['category_name']);
        if ($category_name) {
            pluginia_create_category($category_name);
            echo '<div class="alert alert-success">Categoría añadida exitosamente.</div>';
        }
    }

    // Editar categoría existente
    if (isset($_POST['edit_category'])) {
        $category_id = intval($_POST['category_id']);
        $new_category_name = sanitize_text_field($_POST['new_category_name']);
        if ($category_id && $new_category_name) {
            $wpdb->update(
                $table_name,
                array('name' => $new_category_name),
                array('id' => $category_id),
                array('%s'),
                array('%d')
            );
            echo '<div class="alert alert-success">Categoría actualizada exitosamente.</div>';
        }
    }

    // Eliminar categoría existente
    if (isset($_POST['delete_category'])) {
        $category_id = intval($_POST['category_id']);
        if ($category_id) {
            $wpdb->delete(
                $table_name,
                array('id' => $category_id),
                array('%d')
            );
            echo '<div class="alert alert-danger">Categoría eliminada exitosamente.</div>';
        }
    }
}

// Obtener todas las categorías para mostrarlas en la tabla
$categories = pluginia_get_all_categories();
?>

<div class="container">
    <h2>Gestión de Categorías de Chat</h2>

    <!-- Formulario para añadir una nueva categoría -->
    <form method="POST" class="mb-3">
        <div class="form-group">
            <label for="category_name">Nombre de Nueva Categoría:</label>
            <input type="text" name="category_name" id="category_name" class="form-control" required>
        </div>
        <button type="submit" name="add_category" class="btn btn-primary">Añadir Categoría</button>
    </form>

    <!-- Tabla para mostrar categorías existentes -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre de la Categoría</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($categories): ?>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo esc_html($category->id); ?></td>
                        <td><?php echo esc_html($category->name); ?></td>
                        <td>
                            <!-- Botón para editar categoría -->
                            <button class="btn btn-warning btn-sm edit-button" data-category-id="<?php echo esc_attr($category->id); ?>">Editar</button>
                            
                            <!-- Botón para eliminar categoría -->
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="category_id" value="<?php echo esc_attr($category->id); ?>">
                                <button type="submit" name="delete_category" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas eliminar esta categoría?');">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <!-- Fila oculta para editar categoría -->
                    <tr id="edit-row-<?php echo esc_attr($category->id); ?>" class="edit-row" style="display: none;">
                        <td colspan="3">
                            <form method="POST" class="form-inline">
                                <div class="form-group">
                                    <label for="new_category_name_<?php echo esc_attr($category->id); ?>">Nuevo Nombre: </label>
                                    <input type="text" name="new_category_name" id="new_category_name_<?php echo esc_attr($category->id); ?>" class="form-control ml-2" value="<?php echo esc_attr($category->name); ?>" required>
                                    <input type="hidden" name="category_id" value="<?php echo esc_attr($category->id); ?>">
                                </div>
                                <button type="submit" name="edit_category" class="btn btn-primary ml-2">Guardar Cambios</button>
                                <button type="button" class="btn btn-secondary ml-2 cancel-edit" data-category-id="<?php echo esc_attr($category->id); ?>">Cancelar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No hay categorías disponibles.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// JavaScript para manejar la visualización de formularios de edición
document.querySelectorAll('.edit-button').forEach(function(button) {
    button.addEventListener('click', function() {
        var categoryId = this.getAttribute('data-category-id');
        var editRow = document.getElementById('edit-row-' + categoryId);
        if (editRow.style.display === 'none') {
            editRow.style.display = 'table-row';
        } else {
            editRow.style.display = 'none';
        }
    });
});

document.querySelectorAll('.cancel-edit').forEach(function(button) {
    button.addEventListener('click', function() {
        var categoryId = this.getAttribute('data-category-id');
        var editRow = document.getElementById('edit-row-' + categoryId);
        editRow.style.display = 'none';
    });
});
</script>

<?php
// Incluir el pie de página del tema
get_footer();
?>
