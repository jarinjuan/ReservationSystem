<?php

require "assets/databaze.php";

$connection = connectionDB(); // Tady se zavolá funkce a vytvoří se připojení!


    

    $sql = "SELECT * FROM student";

    $result = mysqli_query($connection, $sql);
    //var_dump($result);
    //echo "<br>";
    //echo "<br>";
    
    if ($result === false) {
        echo mysqli_error($connection);
    } else {
        $students = mysqli_fetch_all($result, MYSQLI_ASSOC);    
    }


    // var_dump($students);

    
?>

<!DOCTYPE html>
<html lang="cz">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">


    <style>
        html {
            background: black;
            color: white;
            font-family: "Roboto Mono", monospace;
        }

        ul {
            color: white;
        }



    </style>

</head>
<body>


<?php require "assets/header.php"; ?>
<session class ="main-heading">
           <h1>Seznam žáků školy</h1>
 </session>
    <main>
        <session class = "students-list"> </session>

        
    <?php if(empty($students)): ?>
        <p>Žádní žáci nebyli nalezeni</p>
    <?php else: ?>
        <ul>
        <?php foreach($students as $one_student): ?>
                <li>
                    <?php echo $one_student["first_name"]. " " .$one_student["second_name"] ?>
                </li>
                <a href="jeden-zak.php?id=<?= $one_student['id'] ?>">Více informací</a>
            <?php endforeach ?>
        </ul>
    <?php endif ?>

    <a href="index.php">Zpět na hlavní stránku</a>
</main>

<?php require "assets/footer.php"; ?>
        
</body>

</html>
