<?php
namespace App\Services;

/**
 * Service de statistiques pour l'administration
 * Démontre l'utilisation de requêtes SQL avancées :
 * - JOIN (INNER, LEFT)
 * - GROUP BY, HAVING
 * - Fonctions d'agrégation (COUNT, AVG, SUM)
 * - Sous-requêtes
 * - ORDER BY avec LIMIT
 */
class StatistiqueService
{
  private \PDO $pdo;

  public function __construct(\PDO $pdo)
  {
    $this->pdo = $pdo;
  }

  /**
   * Statistiques des emprunts par catégorie
   * Requête: LEFT JOIN + GROUP BY + COUNT + ORDER BY
   */
  public function getEmpruntsByCategorie(): array
  {
    $sql = "SELECT
              c.ID_CATEGORIE,
              c.NOM_CATEGORIE,
              COUNT(e.ID_EMPRUNT) as total_emprunts
            FROM Categorie c
            LEFT JOIN Livre l ON c.ID_CATEGORIE = l.ID_CATEGORIE
            LEFT JOIN Emprunt e ON l.ID_LIVRE = e.ID_LIVRE
            GROUP BY c.ID_CATEGORIE, c.NOM_CATEGORIE
            ORDER BY total_emprunts DESC";

    $stmt = $this->pdo->query($sql);
    return $stmt->fetchAll();
  }

  /**
   * Top 10 des livres les plus empruntés
   * Requête: LEFT JOIN + GROUP BY + HAVING + COUNT + ORDER BY + LIMIT
   */
  public function getTopLivres(int $limit = 10): array
  {
    $sql = "SELECT
              l.ID_LIVRE,
              l.TITRE,
              l.AUTEUR,
              COUNT(e.ID_EMPRUNT) as nb_emprunts
            FROM Livre l
            LEFT JOIN Emprunt e ON l.ID_LIVRE = e.ID_LIVRE
            GROUP BY l.ID_LIVRE, l.TITRE, l.AUTEUR
            HAVING nb_emprunts > 0
            ORDER BY nb_emprunts DESC
            LIMIT ?";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
  }

  /**
   * Utilisateurs avec emprunts en retard (> 14 jours)
   * Requête: JOIN + GROUP BY + COUNT + DATE_SUB + WHERE complexe
   */
  public function getUtilisateursEnRetard(): array
  {
    $sql = "SELECT
              u.ID_UTILISATEUR,
              u.PSEUDO,
              u.EMAIL,
              COUNT(e.ID_EMPRUNT) as emprunts_retard
            FROM Utilisateur u
            JOIN Emprunt e ON u.ID_UTILISATEUR = e.ID_UTILISATEUR
            WHERE e.DATE_RETOUR IS NULL
              AND e.DATE_EMPRUNT < DATE_SUB(CURDATE(), INTERVAL 14 DAY)
            GROUP BY u.ID_UTILISATEUR, u.PSEUDO, u.EMAIL
            ORDER BY emprunts_retard DESC";

    $stmt = $this->pdo->query($sql);
    return $stmt->fetchAll();
  }

  /**
   * Statistiques globales de la bibliothèque
   * Requête: Sous-requêtes multiples + COUNT
   */
  public function getStatsGlobales(): array
  {
    $sql = "SELECT
              (SELECT COUNT(*) FROM Livre) as total_livres,
              (SELECT COUNT(*) FROM Livre WHERE DISPONIBILITE = 'Disponible') as livres_disponibles,
              (SELECT COUNT(*) FROM Livre WHERE DISPONIBILITE = 'Emprunté') as livres_empruntes,
              (SELECT COUNT(*) FROM Utilisateur) as total_utilisateurs,
              (SELECT COUNT(*) FROM Emprunt) as total_emprunts,
              (SELECT COUNT(*) FROM Emprunt WHERE DATE_RETOUR IS NULL) as emprunts_en_cours";

    $stmt = $this->pdo->query($sql);
    return $stmt->fetch();
  }

  /**
   * Activité mensuelle des emprunts (12 derniers mois)
   * Requête: GROUP BY sur fonction DATE + COUNT + ORDER BY
   */
  public function getActiviteMensuelle(): array
  {
    $sql = "SELECT
              DATE_FORMAT(DATE_EMPRUNT, '%Y-%m') as mois,
              COUNT(*) as nb_emprunts
            FROM Emprunt
            WHERE DATE_EMPRUNT >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(DATE_EMPRUNT, '%Y-%m')
            ORDER BY mois DESC";

    $stmt = $this->pdo->query($sql);
    return $stmt->fetchAll();
  }

  /**
   * Livres jamais empruntés
   * Requête: LEFT JOIN + WHERE IS NULL (anti-join pattern)
   */
  public function getLivresJamaisEmpruntes(): array
  {
    $sql = "SELECT
              l.ID_LIVRE,
              l.TITRE,
              l.AUTEUR,
              c.NOM_CATEGORIE
            FROM Livre l
            LEFT JOIN Emprunt e ON l.ID_LIVRE = e.ID_LIVRE
            LEFT JOIN Categorie c ON l.ID_CATEGORIE = c.ID_CATEGORIE
            WHERE e.ID_EMPRUNT IS NULL
            ORDER BY l.TITRE";

    $stmt = $this->pdo->query($sql);
    return $stmt->fetchAll();
  }

  /**
   * Utilisateurs les plus actifs
   * Requête: JOIN + GROUP BY + COUNT + ORDER BY + LIMIT
   */
  public function getTopUtilisateurs(int $limit = 5): array
  {
    $sql = "SELECT
              u.ID_UTILISATEUR,
              u.PSEUDO,
              u.EMAIL,
              COUNT(e.ID_EMPRUNT) as total_emprunts,
              SUM(CASE WHEN e.DATE_RETOUR IS NULL THEN 1 ELSE 0 END) as emprunts_en_cours
            FROM Utilisateur u
            JOIN Emprunt e ON u.ID_UTILISATEUR = e.ID_UTILISATEUR
            GROUP BY u.ID_UTILISATEUR, u.PSEUDO, u.EMAIL
            ORDER BY total_emprunts DESC
            LIMIT ?";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
  }

  /**
   * Durée moyenne des emprunts par catégorie
   * Requête: Multiple JOINs + AVG + DATEDIFF + GROUP BY
   */
  public function getDureeMoyenneParCategorie(): array
  {
    $sql = "SELECT
              c.NOM_CATEGORIE,
              ROUND(AVG(DATEDIFF(COALESCE(e.DATE_RETOUR, CURDATE()), e.DATE_EMPRUNT)), 1) as duree_moyenne_jours,
              COUNT(e.ID_EMPRUNT) as nb_emprunts
            FROM Categorie c
            JOIN Livre l ON c.ID_CATEGORIE = l.ID_CATEGORIE
            JOIN Emprunt e ON l.ID_LIVRE = e.ID_LIVRE
            GROUP BY c.ID_CATEGORIE, c.NOM_CATEGORIE
            HAVING nb_emprunts >= 1
            ORDER BY duree_moyenne_jours DESC";

    $stmt = $this->pdo->query($sql);
    return $stmt->fetchAll();
  }

  /**
   * Recherche avancée de livres avec filtres multiples
   * Requête: JOIN + WHERE dynamique + LIKE + ORDER BY
   */
  public function rechercherLivres(?string $titre = null, ?string $auteur = null, ?int $categorie = null, ?string $disponibilite = null): array
  {
    $sql = "SELECT
              l.ID_LIVRE,
              l.TITRE,
              l.AUTEUR,
              l.DISPONIBILITE,
              c.NOM_CATEGORIE
            FROM Livre l
            LEFT JOIN Categorie c ON l.ID_CATEGORIE = c.ID_CATEGORIE
            WHERE 1=1";

    $params = [];

    if ($titre) {
      $sql .= " AND l.TITRE LIKE ?";
      $params[] = "%$titre%";
    }
    if ($auteur) {
      $sql .= " AND l.AUTEUR LIKE ?";
      $params[] = "%$auteur%";
    }
    if ($categorie) {
      $sql .= " AND l.ID_CATEGORIE = ?";
      $params[] = $categorie;
    }
    if ($disponibilite) {
      $sql .= " AND l.DISPONIBILITE = ?";
      $params[] = $disponibilite;
    }

    $sql .= " ORDER BY l.TITRE";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
  }
}
