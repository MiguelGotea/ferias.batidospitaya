<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['loggedin'])) {
    header('Location: /login.php');
    exit;
}