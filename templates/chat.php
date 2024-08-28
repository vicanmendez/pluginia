<?php
// Incluir el encabezado del tema
get_header();
?>

<div class="container">
    <h2>Chat con Documento PDF</h2>

    <!-- Formulario para subir un archivo PDF -->
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="pdf">Sube tu archivo PDF:</label>
            <input type="file" name="pdf_file" id="pdf" class="form-control-file" required accept=".pdf">
        </div>

        <div class="form-group">
            <label for="category">Selecciona una categoría (opcional):</label>
            <select name="category_id" id="category" class="form-control">
                <option value="">Ninguna</option>
                <?php
                // Obtener las categorías disponibles
                $categories = pluginia_get_all_categories();
                foreach ($categories as $category) {
                    echo "<option value='{$category->id}'>{$category->name}</option>";
                }
                ?>
            </select>
        </div>

        <button type="submit" name="upload_pdf" class="btn btn-primary">Subir PDF</button>
    </form>

    <hr>

    <!-- Verificar si el archivo se ha subido exitosamente -->
    <?php if (isset($_SESSION['document_id'])): ?>
        <!-- Mostrar el chat anterior -->
        <h3>Conversación</h3>
        <div id="chat-history">
            <?php // pluginia_show_previous_chats(); ?>
        </div>

        <!-- Formulario para enviar mensajes al documento -->
        <form method="POST">
            <div class="form-group">
                <label for="message">Escribe tu mensaje:</label>
                <input type="text" name="chat_message" id="message" class="form-control" required>
                <input type="hidden" name="document_id" value="<?php echo esc_attr($_SESSION['document_id']); ?>">
            </div>

            <button type="submit" name="send_message" class="btn btn-primary">Enviar Mensaje</button>
        </form>
    <?php else: ?>
        <p>Por favor, sube un archivo PDF para comenzar el chat.</p>
    <?php endif; ?>

</div>

<?php
// Incluir el pie de página del tema
get_footer();
?>
