
<?php 
get_header();
?>

<h2>Chats Anteriores</h2>
<ul>
    <?php
    $chats = pluginia_get_all_chats();
    if (!empty($chats)) {
        foreach ($chats as $chat) {
            echo '<li>' . esc_html($chat->chat_content) . '</li>';
        }
    } else {
        echo '<p>No hay chats anteriores.</p>';
    }
    ?>
</ul>

<?php 
get_footer();
?>
