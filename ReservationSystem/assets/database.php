<?php
function connectionDB()
{
    $db_host = "127.0.0.1";
    $db_user = "rasovskyjaroslav";
    $db_password = "heslo123456789";
    $db_name = "rezervace";

    $connection = mysqli_connect($db_host, $db_user, $db_password, $db_name);

    if (mysqli_connect_error()) {
        die("Připojení do DB selhalo: " . mysqli_connect_error());
    }

    return $connection;
}
?>
