<?php

require "assets/databaze.php";

$connection = connectionDB();

if(isset($_GET["id"]) and is_numeric($_GET["id"]))
{
    $sql = "SELECT *
            FROM student
            WHERE id = ". $_GET["id"];  


    $result = mysqli_query( $connection, $sql);


    if ($result === false) {
        echo mysqli_error($connection);
    } else {
        $students = mysqli_fetch_assoc($result);
    }

    //var_dump($students);

}



?>


<!DOCTYPE html>
<html lang="cz">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<?php require "assets/header.php"; ?>


    <main>
    <session class ="main-heading">
            <h1>Jeden žák</h1>
    </session>
    <section>
            <?php if ($students === null): ?>
                <p>Žák nenalezen</p>
            <?php else: ?>
                <h2><?= htmlspecialchars($students["first_name"]). " " .htmlspecialchars($students["second_name"]) ?></h2>
                <p>Věk: <?= htmlspecialchars($students["age"]) ?></p>
                <p>Dodatečné informace: <?= htmlspecialchars($students["life"]) ?></p>
                <p>Kolej: <?= htmlspecialchars($students["college"]) ?></p>
            <?php endif ?>    
        </section>

</main>
        
</body>
</html>