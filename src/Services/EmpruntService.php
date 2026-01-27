<?php
namespace App\Services;

use App\Classes\Emprunt;
use App\Classes\Utilisateur;
use App\Classe\Livre;
use DateTimeImmutable;

class EmpruntService
{
  private \PDO $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

}