<?php
    $server = "localhost";
    $username = "root";
    $pass = "";
    $nmdatabase = "dbiogudang";

    $conn = mysqli_connect($server, $username, $pass, $nmdatabase);

    if(!$conn){
        die("Gagal Masuk");
    }
?>