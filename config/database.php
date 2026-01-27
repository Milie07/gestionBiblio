<?php

// Configuration BDD SQL
function getDatabaseConnection() {
  // Paramètre de connexion MAMP
  $host = 'localhost';
  $port = '8889';
  $dbname = 'biblio_app_sql';
  $username = 'root';
  $password = 'root';

  try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;

  } catch (PDOException $e) {
    die("Erreur de Connexion MySQL : " . $e->getMessage());
  }
} 
