<?php

/**
 * 
 * Připojení se k databázi
 * 
 * @return object - pro připojení do databáze
 * 
 */

function connectionDB()
{
    $db_host = "127.0.0.1";
    $db_user = "rasovskyjaroslav";
    $db_password = "heslo123456789";
    $db_name = "rezervace";


    $connection = mysqli_connect($db_host, $db_user, $db_password, $db_name);

    if (mysqli_connect_error()){
        echo mysqli_connect_error();
        exit;
    }

    return $connection;
}