<?php
namespace App\Services;

use App\Classes\Emprunt;
use App\Classes\Utilisateur;
use App\Classes\Livre;
use DateTimeImmutable;

class EmpruntService
{
  private \PDO $pdo;

  public function __construct(\PDO $pdo)
  {
    $this->pdo = $pdo;
  }

  /**
   * Emprunter un livre
   * @return Emprunt|string Retourne l'objet Emprunt si succès, message d'erreur sinon
   */
  public function borrowBook(int $id_livre, int $id_utilisateur): Emprunt|string
  {
    // 1. Vérifier que le livre existe et est disponible
    $livre = Livre::getBookById($this->pdo, $id_livre);
    
    if (!$livre) {
      return "❌ Ce livre n'existe pas";
    }

    if ($livre['DISPONIBILITE'] !== 'Disponible') {
      return "❌ Ce livre est déjà emprunté";
    }

    // 2. Vérifier que l'utilisateur n'a pas déjà emprunté ce livre
    if ($this->userHasBook($id_livre, $id_utilisateur)) {
      return "❌ Vous avez déjà emprunté ce livre";
    }

    // 3. Créer l'emprunt (date de retour = +14 jours)
    $dateEmprunt = new DateTimeImmutable();
    $dateRetourPrevue = $dateEmprunt->modify('+14 days');

    $sql = "INSERT INTO Emprunt (ID_LIVRE, ID_UTILISATEUR, DATE_EMPRUNT, DATE_RETOUR)
            VALUES (?, ?, ?, ?)";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      $id_livre,
      $id_utilisateur,
      $dateEmprunt->format('Y-m-d'),
      $dateRetourPrevue->format('Y-m-d')
    ]);

    // 4. Mettre à jour le statut du livre
    $sqlUpdateLivre = "UPDATE Livre SET DISPONIBILITE = 'Emprunté' WHERE ID_LIVRE = ?";
    $stmtUpdate = $this->pdo->prepare($sqlUpdateLivre);
    $stmtUpdate->execute([$id_livre]);

    // 5. Récupérer et retourner l'emprunt créé
    $idEmprunt = $this->pdo->lastInsertId();
    
    // Créer les objets nécessaires
    $livreObj = new Livre(
      $this->pdo,
      $livre['ID_LIVRE'],
      $livre['TITRE'],
      $livre['AUTEUR'],
      new \App\Classes\Categorie($livre['ID_CATEGORIE'], ''), // Simplifié
      'Emprunté'
    );

    $userData = Utilisateur::findById($this->pdo, $id_utilisateur);
    $userObj = new Utilisateur(
      $this->pdo,
      $userData['ID_UTILISATEUR'],
      $userData['PSEUDO'],
      $userData['EMAIL'],
      $userData['PASSWORD'],
      new DateTimeImmutable($userData['DATE_INSCRIPTION'])
    );

    return new Emprunt(
      $this->pdo,
      $idEmprunt,
      $livreObj,
      $userObj,
      $dateEmprunt,
      $dateRetourPrevue
    );
  }

  /**
   * Retourner un livre
   */
  public function returnBook(int $id_emprunt): bool|string
  {
    // 1. Récupérer l'emprunt
    $sql = "SELECT * FROM Emprunt WHERE ID_EMPRUNT = ?";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$id_emprunt]);
    $emprunt = $stmt->fetch();

    if (!$emprunt) {
      return "❌ Cet emprunt n'existe pas";
    }

    if ($emprunt['DATE_RETOUR']) {
      return "❌ Ce livre a déjà été retourné";
    }

    // 2. Enregistrer la date de retour
    $date_retour = new DateTimeImmutable();
    $sqlRetour = "UPDATE Emprunt 
                  SET DATE_RETOUR = ? 
                  WHERE ID_EMPRUNT = ?";
    $stmtRetour = $this->pdo->prepare($sqlRetour);
    $stmtRetour->execute([
      $date_retour->format('Y-m-d'),
      $id_emprunt
    ]);

    // 3. Remettre le livre disponible
    $sqlUpdateLivre = "UPDATE Livre SET DISPONIBILITE = 'Disponible' WHERE ID_LIVRE = ?";
    $stmtUpdate = $this->pdo->prepare($sqlUpdateLivre);
    $stmtUpdate->execute([$emprunt['ID_LIVRE']]);

    return true;
  }

  /**
   * Vérifier si un utilisateur a déjà emprunté un livre (et ne l'a pas rendu)
   */
  private function userHasBook(int $id_livre, int $id_utilisateur): bool
  {
    $sql = "SELECT COUNT(*) FROM Emprunt 
            WHERE ID_LIVRE = ? 
            AND ID_UTILISATEUR = ? 
            AND DATE_RETOUR IS NULL";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$id_livre, $id_utilisateur]);
    
    return $stmt->fetchColumn() > 0;
  }

  /**
   * Récupérer tous les emprunts en cours d'un utilisateur
   */
  public function getActiveLoans(int $id_utilisateur): array
  {
    $sql = "SELECT e.*, l.TITRE, l.AUTEUR 
            FROM Emprunt e
            JOIN Livre l ON e.ID_LIVRE = l.ID_LIVRE
            WHERE e.ID_UTILISATEUR = ? 
            AND e.DATE_RETOUR IS NULL";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$id_utilisateur]);
    
    return $stmt->fetchAll();
  }

  /**
   * Récupérer l'historique complet d'un utilisateur
   */
  public function getHistory(int $id_utilisateur): array
  {
    $sql = "SELECT e.*, l.TITRE, l.AUTEUR 
            FROM Emprunt e
            JOIN Livre l ON e.ID_LIVRE = l.ID_LIVRE
            WHERE e.ID_UTILISATEUR = ?
            ORDER BY e.DATE_EMPRUNT DESC";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$id_utilisateur]);
    
    return $stmt->fetchAll();
  }
}






