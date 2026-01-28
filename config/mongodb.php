<?php

function getMongoDBConnection() {
  // Vérifier si l'extension MongoDB est installée
  if (!class_exists('MongoDB\Driver\Manager')) {
    return null; // Extension non disponible
  }

  // Utilise les variables d'environnement (Docker) ou valeurs par défaut
  $host = getenv('MONGO_HOST') ?: 'localhost';
  $port = getenv('MONGO_PORT') ?: '27017';

  try {
      $manager = new MongoDB\Driver\Manager("mongodb://{$host}:{$port}");
      return $manager;
  } catch (Exception $e) {
    return null; // Connexion échouée
  }
}
function getMongoNamespace($collectionName) {
  return "bibliotheque_nosql." . $collectionName;
}