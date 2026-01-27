<?php

namespace App\Classes;

use App\Classes\Utilisateur;
use App\Classes\Livre;
use DateTimeImmutable;


class Emprunt
{
  private int $id;
  private Livre $id_livre;
  private Utilisateur $id_utilisateur;
  private DateTimeImmutable $date_emprunt;
  private DateTimeImmutable $date_retour;
  private \PDO $pdo;

  public function __construct($pdo, int $id, Livre $id_livre, Utilisateur $id_utilisateur, DateTimeImmutable $date_emprunt, DateTimeImmutable $date_retour)
  {
    $this->id = $id;
    $this->id_livre = $id_livre;
    $this->id_utilisateur = $id_utilisateur;
    $this->date_emprunt = $date_emprunt;
    $this->date_retour = $date_retour;
    $this->pdo = $pdo;
  }
  // getters
  public function getId(): int
  {
    return $this->id;
  }
  public function getIdLivre(): Livre
  {
    return $this->id_livre;
  }
  public function getIdUtilisateur(): Utilisateur
  {
    return $this->id_utilisateur;
  }
  public function getDateEmprunt(): DateTimeImmutable
  {
    return $this->date_emprunt;
  }
  public function getDateRetour(): DateTimeImmutable
  {
    return $this->date_retour;
  }

  // Emprunter un livre
  public function borrowBook()
  {
    $sql = "INSERT INTO Emprunt (ID_LIVRE, ID_UTILISATEUR)
            VALUES (?, ?)";
    $stmt = $this->pdo->prepare($sql);
    $result = $stmt->execute([
      $this->id_livre->getId(),
      $this->id_utilisateur->getId(),
    ]);

    if ($result) {
      $this->id_livre->setDisponibilite('Emprunté');
      $this->id_livre->updateBook();
    }
    return $result;
  }
  // Retour du Livre 
  public function returnBook(): bool
  {
    $sql = "UPDATE Emprunt
            SET DATE_RETOUR = ?
            WHERE ID_EMPRUNT = ?";
    $stmt = $this->pdo->prepare($sql);

    $date_retour = new DateTimeImmutable();
    $result = $stmt->execute([
      $date_retour->format('Y-m-d'),
      $this->id
    ]);

    if ($result) {
      $this->id_livre->setDisponibilite('Disponible');
      $this->id_livre->updateBook();
    }

    return $result;
  }
  // Emprunts en Cours
  public function loanInProgress(): bool
  {
    $sql = "SELECT COUNT(*) FROM Emprunt
            WHERE ID_LIVRE = ?
            AND ID_UTILISATEUR = ?
            AND DATE_RETOUR IS NULL";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      $this->id_livre->getId(),
      $this->id_utilisateur->getId()
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
