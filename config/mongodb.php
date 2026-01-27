<?php

function getMongoDBConnection() {
  try {
      $manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
      return $manager;
  } catch (Exception $e) {
    die("Erreur de connexion MongoDB : " . $e->getMessage());
  }
}
function getMongoNamespace($collectionName) {
  return "bibliotheque_nosql." . $collectionName;
}