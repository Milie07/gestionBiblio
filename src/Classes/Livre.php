<?php

namespace App\Classes;

use App\Classes\Categorie;

class Livre
{
  private int $id_livre;
  private string $titre;
  private string $auteur;
  private Categorie $categorie;
  private string $disponibilite = "Disponible";
  private \PDO $pdo;

  public function __construct($pdo, int $id_livre, string $titre, string $auteur, Categorie $categorie, string $disponibilite = "Disponible")
  {
    $this->id_livre = $id_livre;
    $this->titre = $titre;
    $this->auteur = $auteur;
    $this->categorie = $categorie;
    $this->disponibilite = $disponibilite;
    $this->pdo = $pdo;
  }
  // Getters
  public function getIdLivre(): int
  {
    return $this->id_livre;
  }
  public function getTitre(): string
  {
    return $this->titre;
  }
  public function getAuteur(): string
  {
    return $this->auteur;
  }
  public function getCategorie(): Categorie
  {
    return $this->categorie;
  }
  public function getDisponibilite(): string
  {
    return $this->disponibilite;
  }
  // Setters
  public function setTitre($titre): void
  {
    $this->titre = $titre;
  }
  public function setAuteur($auteur): void
  {
    $this->auteur = $auteur;
  }
  public function setDisponibilite($disponibilite): void
  {
    $this->disponibilite = $disponibilite;
  }

  // Méthode CRUD
  public function createBook(): bool
  {
    $sql = "INSERT INTO Livre (TITRE, AUTEUR, ID_CATEGORIE)
            VALUES (?, ?, ?)";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([
      $this->titre,
      $this->auteur,
      $this->categorie->getId()
    ]);
  }
  // Supprimer un livre
  public function deleteBook(): bool
  {
    $sql = "DELETE FROM Livre WHERE ID_LIVRE = ?";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$this->id_livre]);
  }
  // Mettre à jour les informations du livre
  public function updateBook(): bool
  {
    $sql = "UPDATE Livre
            SET TITRE = ?, AUTEUR = ?, DISPONIBILITE = ?
            WHERE ID_LIVRE = ?";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([
      $this->titre,
      $this->auteur,
      $this->disponibilite,
      $this->id_livre
    ]);
  }
  // Vérifier la disponibilité du livre
  public function isAvailable(): bool
  {
    return $this->disponibilite === 'Disponible';
  }
  // Méthode pour récupérer tous les livres
  public static function getAllBooks($pdo): array
  {
    $stmt = $pdo->query("SELECT * FROM Livre");
    return $stmt->fetchAll();
  }
  // Méthode pour récupérer un livre par ID
  public static function getBookById($pdo, int $id_livre): ?array
  {
    $stmt = $pdo->prepare("SELECT * FROM Livre WHERE ID_LIVRE = ?");
    $stmt->execute([$id_livre]);
    return $stmt->fetch() ?: null;
  }
}
