<?php
session_start();
require "konek.php";

// Save the search term in session
if (isset($_POST['search_term'])) {
    $_SESSION['saved_search'] = $_POST['search_term'];

    // Redirect back to the main page
    header("Location: informasimasukbarang.php");
    exit();
} else {
    // If no search term, clear the saved search
    unset($_SESSION['saved_search']);
    header("Location: informasimasukbarang.php");
    exit();
}