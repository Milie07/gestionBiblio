<?php

// Configuration BDD SQL
function getDatabaseConnection() {
  // Utilise les variables d'environnement (Docker) ou valeurs par défaut (MAMP)
  $host = getenv('DB_HOST') ?: '127.0.0.1';
  $port = getenv('DB_PORT') ?: '8889';
  $dbname = getenv('DB_NAME') ?: 'biblio_app_sql';
  $username = getenv('DB_USER') ?: 'root';
  $password = getenv('DB_PASSWORD') ?: 'root';

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
