<?php

namespace App\Classes;

use App\Classes\Categorie;

class Livre
{
  private int $id;
  private string $titre;
  private string $auteur;
  private Categorie $id_categorie;
  private string $disponibilite = "Disponible";
  private \PDO $pdo;

  public function __construct($pdo, int $id, string $titre, string $auteur, Categorie $id_categorie, string $disponibilite = "Disponible")
  {
    $this->id = $id;
    $this->titre = $titre;
    $this->auteur = $auteur;
    $this->id_categorie = $id_categorie;
    $this->disponibilite = $disponibilite;
    $this->pdo = $pdo;
  }
  // Getters
  public function getId(): int
  {
    return $this->id;
  }
  public function getTitre(): string
  {
    return $this->titre;
  }
  public function getAuteur(): string
  {
    return $this->auteur;
  }
  public function getIdCategorie(): Categorie
  {
    return $this->id_categorie;
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
  public function registerBook(): bool
  {
    $sql = "INSERT INTO Livre (TITRE, AUTEUR, ID_CATEGORIE)
            VALUES (?, ?, ?)";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([
      $this->titre,
      $this->auteur,
      $this->id_categorie->getId()
    ]);
  }
  public function deleteBook(): bool
  {
    $sql = "DELETE FROM Livre WHERE ID_LIVRE = ?";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$this->id]);
  }
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
      $this->id
    ]);
  }
  public function isAvailable(): bool
  {
    return $this->disponibilite === 'Disponible';
  }
  // Méthode pour récupérer tous les livres
  public static function getAllLivres($pdo): array
  {
    $stmt = $pdo->query("SELECT * FROM Livre");
    return $stmt->fetchAll();
  }
  // Méthode pour récupérer un livre par ID
  public static function getLivreById($pdo, int $id): ?array
  {
    $stmt = $pdo->prepare("SELECT * FROM Livre WHERE ID_LIVRE = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
  }
}
