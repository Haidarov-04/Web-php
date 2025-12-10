<?php
    $host = "localhost";
    $user = "root";
    $db_name = "demoPHP";
    $pass = "";

    $conn = mysqli_connect($host, $user, $pass, $db_name);
    
    if (mysqli_connect_error()){
        echo "error connect".mysqli_connect_error() ;
        die;      
    }
?>