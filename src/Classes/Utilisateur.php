<?php

namespace App\Classes;

use DateTimeImmutable;

class Utilisateur
{
  private int $id;
  private string $pseudo;
  private string $email;
  private string $password;
  private DateTimeImmutable $date_inscription;
  private $pdo;

  public function __construct($pdo, int $id, string $pseudo, string $email, string $password, DateTimeImmutable $date_inscription)
  {
    $this->id = $id;
    $this->pseudo = $pseudo;
    $this->email = $email;
    $this->password = $password;
    $this->date_inscription = $date_inscription;
    $this->pdo = $pdo;
  }
  // Getters
  public function getId(): int
  {
    return $this->id;
  }
  public function getPseudo(): string
  {
    return $this->pseudo;
  }
  public function getEmail(): string
  {
    return $this->email;
  }
  public function getPassword(): string
  {
    return $this->password;
  }
  public function getDateInscription(): DateTimeImmutable
  {
    return $this->date_inscription;
  }
  // Setters
  public function setPseudo($pseudo): void
  {
    $this->pseudo = $pseudo;
  }
  public function setEmail($email): void
  {
    $this->email = $email;
  }
  public function setPassword($password): void
  {
    $this->password = $password;
  }

  // Méthode CRUD
  public function registerUser(): bool
  {
    $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO Utilisateur (PSEUDO, EMAIL, PASSWORD, DATE_INSCRIPTION)
            VALUES (?, ?, ?, ?)";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([
      $this->pseudo,
      $this->email,
      $hashedPassword,
      $this->date_inscription->format('Y-m-d H:i:s')
    ]);
  }

  public function verifyPassword(string $passwordToCheck): bool
  {
    return password_verify($passwordToCheck, $this->password);
  }

  public function deleteUser(): bool
  {
    $sql = "DELETE FROM Utilisateur WHERE ID_UTILISATEUR = ?";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$this->id]);
  }

  public function updateUser(): bool
  {
    $sql = "UPDATE Utilisateur
            SET PSEUDO = ?, EMAIL = ?, PASSWORD = ?
            WHERE ID_UTILISATEUR = ?";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([
      $this->pseudo,
      $this->email,
      $this->password,
      $this->id
    ]);
  }

  // Méthode statique pour récupérer un utilisateur par email
  public static function findByEmail($pdo, string $email): ?array
  {
    $sql = "SELECT * FROM Utilisateur WHERE EMAIL = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
  }

  // Méthode statique pour récupérer un utilisateur par ID
  public static function findById($pdo, int $id): ?array
  {
    $sql = "SELECT * FROM Utilisateur WHERE ID_UTILISATEUR = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
  }

}
