<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить категорию</title>
</head>
<body>
    <?php 
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    echo "<p>ID конкурса: <b>$id</b></p>";
    ?>

    <form action="contest_name.php" method="get">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <label for="contest_name">Тип конкурса:</label><br>
        <input type="text" id="contest_name" name="contest_name" required>
        <br><br>
        <input type="submit" value="Добавить">
    </form>

</body>
</html>
