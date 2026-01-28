<?php

namespace App\Classes;

use App\Classes\Utilisateur;
use App\Classes\Livre;
use DateTimeImmutable;


class Emprunt
{
  private int $id_emprunt;
  private Livre $livre;
  private Utilisateur $utilisateur;
  private DateTimeImmutable $date_emprunt;
  private DateTimeImmutable $date_retour;
  private \PDO $pdo;

  public function __construct($pdo, int $id_emprunt, Livre $livre, Utilisateur $utilisateur, DateTimeImmutable $date_emprunt, DateTimeImmutable $date_retour)
  {
    $this->id_emprunt = $id_emprunt;
    $this->livre = $livre;
    $this->utilisateur = $utilisateur;
    $this->date_emprunt = $date_emprunt;
    $this->date_retour = $date_retour;
    $this->pdo = $pdo;
  }
  // getters
  public function getIdEmprunt(): int
  {
    return $this->id_emprunt;
  }
  public function getLivre(): Livre
  {
    return $this->livre;
  }
  public function getUtilisateur(): Utilisateur
  {
    return $this->utilisateur;
  }
  public function getDateEmprunt(): DateTimeImmutable
  {
    return $this->date_emprunt;
  }
  public function getDateRetour(): DateTimeImmutable
  {
    return $this->date_retour;
  }
  // Méthode CRUD
  // Créer un emprunt dans la BDD
  public function create(): bool
  {
    $sql = "INSERT INTO Emprunt (ID_LIVRE, ID_UTILISATEUR)
            VALUES (?, ?)";
    $stmt = $this->pdo->prepare($sql);
    $result = $stmt->execute([
      $this->livre->getIdLivre(),
      $this->utilisateur->getUserId(),
    ]);

    if ($result) {
      $this->livre->setDisponibilite('Emprunté');
      $this->livre->updateBook();
    }
    return $result;
  }
  // Retour du Livre 
  public function registerReturn(): bool
  {
    $sql = "UPDATE Emprunt
            SET DATE_RETOUR = ?
            WHERE ID_EMPRUNT = ?";
    $stmt = $this->pdo->prepare($sql);

    $date_retour = new DateTimeImmutable();
    $result = $stmt->execute([
      $date_retour->format('Y-m-d'),
      $this->id_emprunt
    ]);

    if ($result) {
      $this->livre->setDisponibilite('Disponible');
      $this->livre->updateBook();
    }

    return $result;
  }
  // Emprunt en Cours pour un utilisateur
  public function isActive(): bool
  {
    $sql = "SELECT COUNT(*) FROM Emprunt
            WHERE ID_LIVRE = ?
            AND ID_UTILISATEUR = ?
            AND DATE_RETOUR IS NULL";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      $this->livre->getIdLivre(),
      $this->utilisateur->getUserId()
    ]);
    return $stmt->fetchColumn() > 0;
  }

  // Récupère tous les emprunts en cours
  public static function getActiveLoans($pdo): array
  {
    $sql = "SELECT * FROM Emprunt WHERE DATE_RETOUR IS NULL";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
  }

  // Récupère l'historique des emprunts d'un utilisateur
  public static function getUserHistory($pdo, int $userId): array
  {
    $sql = "SELECT * FROM Emprunt WHERE ID_UTILISATEUR = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
  }
}
