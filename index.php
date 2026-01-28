<?php
/**
 * Page d'accueil - BiblioApp
 * Affiche le catalogue des livres avec recherche et notes moyennes
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/mongodb.php';

use App\Classes\Livre;
use App\Classes\Commentaire;
use App\Services\StatistiqueService;

// Connexions
$pdo = getDatabaseConnection();
$mongoManager = getMongoDBConnection(); // Peut être null si extension absente

// Services
$statsService = new StatistiqueService($pdo);
$commentaire = $mongoManager ? new Commentaire($mongoManager) : null;

// Récupérer les paramètres de recherche
$recherche = $_GET['q'] ?? '';
$filtre = $_GET['filtre'] ?? '';
$categorie = isset($_GET['categorie']) ? (int)$_GET['categorie'] : null;

// Récupérer les livres (avec recherche si demandée)
if ($recherche || $categorie) {
    $livres = $statsService->rechercherLivres(
        $filtre === 'titre' ? $recherche : null,
        $filtre === 'auteur' ? $recherche : null,
        $categorie,
        null
    );
} else {
    // Requête avec LEFT JOIN pour afficher la catégorie
    $sql = "SELECT l.*, c.NOM_CATEGORIE
            FROM Livre l
            LEFT JOIN Categorie c ON l.ID_CATEGORIE = c.ID_CATEGORIE
            ORDER BY l.TITRE";
    $stmt = $pdo->query($sql);
    $livres = $stmt->fetchAll();
}

// Récupérer les catégories pour le filtre
$sqlCategories = "SELECT * FROM Categorie ORDER BY NOM_CATEGORIE";
$categories = $pdo->query($sqlCategories)->fetchAll();

// Récupérer les notes moyennes par livre (NoSQL Aggregation)
$notesMoyennes = [];
if ($commentaire) {
    $notesData = $commentaire->getNoteMoyenneParLivre();
    foreach ($notesData as $note) {
        $notesMoyennes[$note->_id] = [
            'moyenne' => round($note->moyenne, 1),
            'nb_avis' => $note->nb_avis
        ];
    }
}

// Stats globales (Sous-requêtes SQL)
$stats = $statsService->getStatsGlobales();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/public/css/style.css">
    <title>BiblioApp - Catalogue</title>
    <style>
        
    </style>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="index.php">Catalogue</a></li>
                <li><a href="login.php">Connexion</a></li>
            </ul>
        </nav>
    </header>

    <!-- Barre de statistiques (utilise sous-requêtes SQL) -->
    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-number"><?= $stats['total_livres'] ?? 0 ?></div>
            <div>Livres</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?= $stats['livres_disponibles'] ?? 0 ?></div>
            <div>Disponibles</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?= $stats['livres_empruntes'] ?? 0 ?></div>
            <div>Empruntés</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?= $stats['total_utilisateurs'] ?? 0 ?></div>
            <div>Membres</div>
        </div>
    </div>

    <main>
        <h1>Bienvenue sur BiblioApp</h1>

        <!-- Section recherche (utilise requête dynamique avec LIKE) -->
        <section class="search-section">
            <h2>Rechercher un livre</h2>
            <form method="GET" action="index.php" class="search-form">
                <div class="form-group">
                    <label for="q">Recherche</label>
                    <input type="text" name="q" id="q" placeholder="Titre ou auteur..." value="<?= htmlspecialchars($recherche) ?>">
                </div>

                <div class="form-group">
                    <label>Filtrer par</label>
                    <div class="radio-group">
                        <label><input type="radio" name="filtre" value="titre" <?= $filtre === 'titre' ? 'checked' : '' ?>> Titre</label>
                        <label><input type="radio" name="filtre" value="auteur" <?= $filtre === 'auteur' ? 'checked' : '' ?>> Auteur</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="categorie">Catégorie</label>
                    <select name="categorie" id="categorie">
                        <option value="">Toutes</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['ID_CATEGORIE'] ?>" <?= $categorie == $cat['ID_CATEGORIE'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['NOM_CATEGORIE']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit">Rechercher</button>
                <?php if ($recherche || $categorie): ?>
                    <a href="index.php" style="padding: 0.6rem;">Réinitialiser</a>
                <?php endif; ?>
            </form>
        </section>

        <!-- Catalogue (utilise LEFT JOIN pour catégories + NoSQL pour notes) -->
        <section>
            <h2>Catalogue <?= $recherche ? '- Résultats pour "' . htmlspecialchars($recherche) . '"' : '' ?></h2>
            <p style="margin-bottom: 1rem; color: #7f8c8d;"><?= count($livres) ?> livre(s) trouvé(s)</p>

            <div class="catalogue">
                <?php foreach ($livres as $livre): ?>
                    <?php
                        $idLivre = $livre['ID_LIVRE'];
                        $noteLivre = $notesMoyennes[$idLivre] ?? null;
                    ?>
                    <article class="livre-card">
                        <h3><?= htmlspecialchars($livre['TITRE']) ?></h3>
                        <p class="auteur">par <?= htmlspecialchars($livre['AUTEUR']) ?></p>

                        <?php if (!empty($livre['NOM_CATEGORIE'])): ?>
                            <span class="categorie"><?= htmlspecialchars($livre['NOM_CATEGORIE']) ?></span>
                        <?php endif; ?>

                        <div>
                            <span class="disponibilite <?= $livre['DISPONIBILITE'] === 'Disponible' ? 'disponible' : 'emprunte' ?>">
                                <?= htmlspecialchars($livre['DISPONIBILITE']) ?>
                            </span>
                        </div>

                        <!-- Note moyenne (NoSQL Aggregation) -->
                        <?php if ($noteLivre): ?>
                            <div class="note">
                                <span class="etoiles">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?= $i <= round($noteLivre['moyenne']) ? '★' : '☆' ?>
                                    <?php endfor; ?>
                                </span>
                                <span><?= $noteLivre['moyenne'] ?>/5</span>
                                <span class="nb-avis">(<?= $noteLivre['nb_avis'] ?> avis)</span>
                            </div>
                        <?php else: ?>
                            <div class="note">
                                <span class="nb-avis">Pas encore d'avis</span>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <footer>
        <p>2026 - Student Project - Made By Milie</p>
    </footer>
</body>
</html>
