<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить категорию</title>
    <link rel="stylesheet" href="../admin/auth_style.css">
    <link rel="stylesheet" href="../admin/topbar.css">
</head>
<body>
    <?php include '../admin/topbar.php'; ?>
    <div class="container">
        <h1>Добавить категорию</h1>
        <?php 
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        echo "<p>ID конкурса: <b>$id</b></p>";
        ?>

        <form action="contest_name.php" method="get">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <div class="form-group">
                <label for="contest_name">Название категории:</label><br>
                <input type="text" id="contest_name" name="contest_name" required>
            </div>
            <br>
            <div class="form-group">
                <input type="submit" value="Добавить">
            </div>
        </form>
    </div>
</body>
</html>
