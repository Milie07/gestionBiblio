<?php

namespace App\Services;

use App\Classes\Utilisateur;
use DateTimeImmutable;

class LoginService
{
  private \PDO $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  /**
   * Connexion d'un utilisateur
   * @return Utilisateur|null Retourne l'utilisateur si connexion réussie, retourne null sinon.
   */
  public function login(string $email, string $password): ?Utilisateur
  {
    // Récupérer l'utilisateur par l'email
    $userData = Utilisateur::findByEmail($this->pdo, $email);
    if (!$userData) {
      return null; // Utilisateur Inconnu
    }

    // Vérifier le mot de passe
    if (!password_verify($password, $userData['PASSWORD'])) {
      return null; // Mot de passe incorrect
    }

    // Créer la session
    $this->createSession($userData['ID_UTILISATEUR'], $userData['PSEUDO']);

    return new Utilisateur(
      $this->pdo,
      $userData['ID_UTILISATEUR'],
      $userData['PSEUDO'],
      $userData['EMAIL'],
      $userData['PASSWORD'],
      new DateTimeImmutable($userData['DATE_INSCRIPTION'])
    );
  }

  // Déconnexion de l'utilisateur
  public function logout(): void
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
    session_unset();
    session_destroy();
  }

  // Créer une session utilisateur
  private function createSession(int $id, string $pseudo): void
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $_SESSION['id'] = $id;
    $_SESSION['pseudo'] = $pseudo;
    $_SESSION['logged_in'] = true;
  }

  // Vérifier si un utilisateur est connecté
  public static function isLoggedIn(): bool
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
    return isset($_SESSION['id']) && $_SESSION['logged_in'] === true;
  }

  // Récupérer l'Id de l'utilisateur connecté
  public static function getCurrentUserId(): ?int
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
    return $_SESSION['id'] ?? null;
  }

  //  Récupérer le pseudo de l'utilisateur connecté
  public static function getCurrentUserPseudo(): ?string
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
    return $_SESSION['pseudo'] ?? null;
  }

  // Protéger une page (rediriger si non connecté)
  public static function requireLogin(): void
  {
    if (!self::isLoggedIn()) {
      header('Location: login.php');
      exit;
    }
  }

  // Récupérer l'utilisateur connecté
  public function getCurrentUser(): ?Utilisateur
  {
    $userId = self::getCurrentUserId();

    if (!$userId) {
      return null;
    }

    $userData = Utilisateur::findById($this->pdo, $userId);

    if (!$userData) {
      return null;
    }

    return new Utilisateur(
      $this->pdo,
      $userData['ID_UTILISATEUR'],
      $userData['PSEUDO'],
      $userData['EMAIL'],
      $userData['PASSWORD'],
      new DateTimeImmutable($userData['DATE_INSCRIPTION'])
    );

    }
}
