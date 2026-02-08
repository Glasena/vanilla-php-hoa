<?php

session_start();
require 'config/database.php';

// Se já tá logado, redireciona
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}