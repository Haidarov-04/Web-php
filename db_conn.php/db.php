<?php
    $host = "localhost";
    $user = "root";
    $db_name = "demoPHP";
    $pass = "";

    $conn = mysqli_connect($host, $user, $pass, $db_name);
    
    if (mysqli_connect_error()){
        echo "ошибка подключения".mysqli_connect_error() ;
        die;      
    }
?>