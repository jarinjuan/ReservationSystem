<?php

// XSS 

 require "assets/databaze.php";

 $first_name = null;
 $second_name = null;
 $age = null;
 $life = null;
 $college = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
   
 $first_name = $_POST["first_name"];
 $second_name = $_POST["second_name"];
 $age = $_POST["age"];
 $life = $_POST["life"];
 $college =$_POST["college"];


    if($_POST["first_name"] === "")
    {
        die("Křestní jméno je povinné");
    }

    $connection = connectionDB();


    $sql = "INSERT INTO student (first_name, second_name, age, life, college  ) 

     VALUES (?, ?, ?, ?, ?)";
   
    $statement = mysqli_prepare($connection, $sql);

if($statement === false)
    {
        echo mysqli_error($connection);
    }
    else
    {
        mysqli_stmt_bind_param($statement, "ssiss", $_POST["first_name"], $_POST["second_name"], $_POST["age"], $_POST["life"], $_POST["college"]);
    
        if(mysqli_stmt_execute($statement))
        {
            $id = mysqli_insert_id($connection); //vezem id z databáze
                echo " Úspěšně vložen žák s ID $id";

        }

        else
        {
            echo mysqli_error($connection);
        }
    }
    

              //var_dump($sql);
             // exit;

             //$result = mysqli_query($connection, $sql);

            /* if($result === false)
             {
                echo mysqli_error($connection);
             }
            else   
            {
                $id = mysqli_insert_id($connection); //vezem id z databáze
                echo " Úspěšně vložen žák s ID $id";
            } */

}


?>


<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php require "assets/header.php"; ?>


    <main>
        <section class="add-form">
            <form action="pridat-zaka.php" method="POST">
                <input type="text" name="first_name" placeholder="Křestní jméno" required value =<?php echo htmlspecialchars($first_name)  ?>><br> <!-- required - musí uživatel vyplnit nepustí ho to -->
                <input type="text" name="second_name" placeholder="Příjmení" required value =<?php echo htmlspecialchars($second_name) ?>><br> <!-- hodí to odeslaný do formuláře -->
                <input type="number" name="age" placeholder="Věk" min="10" required value =<?php echo htmlspecialchars($age) ?>><br>
                <textarea name="life" placeholder="Podrobnosti o žákovi"required ><?php echo htmlspecialchars($life) ?></textarea><br>
                <input type="text" name="college" placeholder="Kolej"required value =<?php echo htmlspecialchars($college) ?>><br>
                <input type="submit" value="Přidat">
                </form>
        </section>
    </main>


    <?php require "assets/footer.php"; ?>
</body>
</html>