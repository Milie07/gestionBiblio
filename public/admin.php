<?php
/**
 * Page d'administration - BiblioApp
 * Affiche les statistiques avancées de la bibliothèque
 *
 * Requêtes SQL avancées utilisées :
 * - LEFT JOIN + GROUP BY + COUNT (emprunts par catégorie)
 * - GROUP BY + HAVING + ORDER BY + LIMIT (top livres)
 * - JOIN + DATE_SUB + GROUP BY (utilisateurs en retard)
 * - Sous-requêtes multiples (stats globales)
 * - AVG + DATEDIFF + COALESCE (durée moyenne)
 * - Anti-join pattern (livres jamais empruntés)
 * - DATE_FORMAT + GROUP BY (activité mensuelle)
 *
 * Requêtes NoSQL utilisées :
 * - Aggregation $group + $avg (notes moyennes)
 * - Aggregation $dateToString (activité par mois)
 * - count() (total commentaires)
 */

session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/mongodb.php';

use App\Services\StatistiqueService;
use App\Services\LoginService;
use App\Classes\Commentaire;

$pdo = getDatabaseConnection();
$mongoManager = getMongoDBConnection();

$loginService = new LoginService($pdo);
$statsService = new StatistiqueService($pdo);
$commentaire = new Commentaire($mongoManager);

// Vérification de l'accès admin (pour la démo, on permet l'accès)
$isAdmin = true;
if ($loginService->isLoggedIn()) {
    $user = $loginService->getCurrentUser();
    $isAdmin = isset($user['ROLE']) && $user['ROLE'] === 'admin';
}

// Récupérer toutes les statistiques

// 1. Stats globales (sous-requêtes)
$statsGlobales = $statsService->getStatsGlobales();

// 2. Emprunts par catégorie (LEFT JOIN + GROUP BY)
$empruntsByCategorie = $statsService->getEmpruntsByCategorie();

// 3. Top 10 livres les plus empruntés (GROUP BY + HAVING + LIMIT)
$topLivres = $statsService->getTopLivres(10);

// 4. Utilisateurs en retard (JOIN + DATE_SUB)
$utilisateursRetard = $statsService->getUtilisateursEnRetard();

// 5. Livres jamais empruntés (LEFT JOIN + IS NULL)
$livresJamaisEmpruntes = $statsService->getLivresJamaisEmpruntes();

// 6. Top utilisateurs (JOIN + GROUP BY + SUM CASE)
$topUtilisateurs = $statsService->getTopUtilisateurs(5);

// 7. Durée moyenne par catégorie (AVG + DATEDIFF + HAVING)
$dureeMoyenne = $statsService->getDureeMoyenneParCategorie();

// 8. Activité mensuelle SQL (DATE_FORMAT + GROUP BY)
$activiteMensuelle = $statsService->getActiviteMensuelle();

// 9. Stats NoSQL - Notes moyennes par livre (Aggregation $group + $avg)
$notesParLivre = $commentaire->getNoteMoyenneParLivre();

// 10. Stats NoSQL globales (Aggregation $group sans _id)
$statsCommentaires = $commentaire->getStatistiques();

// 11. Activité commentaires par mois (Aggregation $dateToString)
$activiteCommentaires = $commentaire->getActiviteParMois();

// 12. Top livres par note (Aggregation $group + $match + $sort)
$topParNote = $commentaire->getTopLivresParNote(1, 5);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/public/css/style.css">
    <title>BiblioApp - Administration</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        header { background: #8e44ad; color: white; padding: 1rem 2rem; }
        nav ul { list-style: none; display: flex; gap: 2rem; }
        nav a { color: white; text-decoration: none; }
        nav a:hover { text-decoration: underline; }
        main { max-width: 1400px; margin: 2rem auto; padding: 0 1rem; }
        h1 { margin-bottom: 0.5rem; }
        .subtitle { color: #7f8c8d; margin-bottom: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-card .number { font-size: 2rem; font-weight: bold; color: #8e44ad; }
        .stat-card .label { color: #7f8c8d; margin-top: 0.3rem; }
        .section { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section h2 { color: #2c3e50; margin-bottom: 0.3rem; font-size: 1.3rem; }
        .section .query-type { color: #8e44ad; font-size: 0.85rem; margin-bottom: 1rem; font-family: monospace; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.8rem; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; }
        .badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.85rem; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .nosql-section { border-left: 4px solid #27ae60; }
        .sql-section { border-left: 4px solid #3498db; }
        .etoiles { color: #f39c12; }
        footer { background: #2c3e50; color: white; text-align: center; padding: 1rem; margin-top: 2rem; }
        .empty { color: #95a5a6; font-style: italic; padding: 1rem; text-align: center; }
    </style>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="admin.php">Administration</a></li>
                <?php if ($loginService->isLoggedIn()): ?>
                    <li><a href="login.php?action=logout">Déconnexion</a></li>
                <?php else: ?>
                    <li><a href="login.php">Connexion</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Tableau de bord - Administration</h1>
        <p class="subtitle">Vue d'ensemble des statistiques de la bibliothèque</p>

        <!-- Stats globales (SQL: Sous-requêtes) -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?= $statsGlobales['total_livres'] ?? 0 ?></div>
                <div class="label">Total Livres</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $statsGlobales['livres_disponibles'] ?? 0 ?></div>
                <div class="label">Disponibles</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $statsGlobales['livres_empruntes'] ?? 0 ?></div>
                <div class="label">Empruntés</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $statsGlobales['total_utilisateurs'] ?? 0 ?></div>
                <div class="label">Utilisateurs</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $statsGlobales['total_emprunts'] ?? 0 ?></div>
                <div class="label">Total Emprunts</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $statsGlobales['emprunts_en_cours'] ?? 0 ?></div>
                <div class="label">En cours</div>
            </div>
        </div>

        <div class="grid-2">
            <!-- Top livres (SQL: GROUP BY + HAVING + ORDER BY + LIMIT) -->
            <section class="section sql-section">
                <h2>Top 10 Livres les plus empruntés</h2>
                <div class="query-type">SQL: LEFT JOIN + GROUP BY + HAVING + ORDER BY + LIMIT</div>
                <?php if (count($topLivres) > 0): ?>
                    <table>
                        <thead>
                            <tr><th>#</th><th>Titre</th><th>Auteur</th><th>Emprunts</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topLivres as $i => $livre): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($livre['TITRE']) ?></td>
                                    <td><?= htmlspecialchars($livre['AUTEUR']) ?></td>
                                    <td><span class="badge badge-info"><?= $livre['nb_emprunts'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty">Aucun emprunt enregistré</p>
                <?php endif; ?>
            </section>

            <!-- Emprunts par catégorie (SQL: LEFT JOIN + GROUP BY + COUNT) -->
            <section class="section sql-section">
                <h2>Emprunts par catégorie</h2>
                <div class="query-type">SQL: LEFT JOIN + GROUP BY + COUNT + ORDER BY</div>
                <?php if (count($empruntsByCategorie) > 0): ?>
                    <table>
                        <thead>
                            <tr><th>Catégorie</th><th>Emprunts</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empruntsByCategorie as $cat): ?>
                                <tr>
                                    <td><?= htmlspecialchars($cat['NOM_CATEGORIE'] ?? 'Sans catégorie') ?></td>
                                    <td><span class="badge badge-info"><?= $cat['total_emprunts'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty">Aucune catégorie</p>
                <?php endif; ?>
            </section>

            <!-- Utilisateurs en retard (SQL: JOIN + DATE_SUB + GROUP BY) -->
            <section class="section sql-section">
                <h2>Utilisateurs avec emprunts en retard</h2>
                <div class="query-type">SQL: JOIN + WHERE + DATE_SUB + GROUP BY</div>
                <?php if (count($utilisateursRetard) > 0): ?>
                    <table>
                        <thead>
                            <tr><th>Pseudo</th><th>Email</th><th>Retards</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilisateursRetard as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['PSEUDO']) ?></td>
                                    <td><?= htmlspecialchars($user['EMAIL']) ?></td>
                                    <td><span class="badge badge-danger"><?= $user['emprunts_retard'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty">Aucun retard - Bravo !</p>
                <?php endif; ?>
            </section>

            <!-- Top utilisateurs (SQL: JOIN + GROUP BY + SUM CASE) -->
            <section class="section sql-section">
                <h2>Utilisateurs les plus actifs</h2>
                <div class="query-type">SQL: JOIN + GROUP BY + COUNT + SUM(CASE) + LIMIT</div>
                <?php if (count($topUtilisateurs) > 0): ?>
                    <table>
                        <thead>
                            <tr><th>Pseudo</th><th>Total</th><th>En cours</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topUtilisateurs as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['PSEUDO']) ?></td>
                                    <td><span class="badge badge-info"><?= $user['total_emprunts'] ?></span></td>
                                    <td><span class="badge badge-warning"><?= $user['emprunts_en_cours'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty">Aucun utilisateur actif</p>
                <?php endif; ?>
            </section>

            <!-- Durée moyenne par catégorie (SQL: AVG + DATEDIFF + HAVING) -->
            <section class="section sql-section">
                <h2>Durée moyenne des emprunts</h2>
                <div class="query-type">SQL: JOIN + AVG + DATEDIFF + COALESCE + GROUP BY + HAVING</div>
                <?php if (count($dureeMoyenne) > 0): ?>
                    <table>
                        <thead>
                            <tr><th>Catégorie</th><th>Durée moy.</th><th>Emprunts</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dureeMoyenne as $cat): ?>
                                <tr>
                                    <td><?= htmlspecialchars($cat['NOM_CATEGORIE']) ?></td>
                                    <td><?= $cat['duree_moyenne_jours'] ?> jours</td>
                                    <td><?= $cat['nb_emprunts'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty">Pas assez de données</p>
                <?php endif; ?>
            </section>

            <!-- Livres jamais empruntés (SQL: LEFT JOIN + IS NULL) -->
            <section class="section sql-section">
                <h2>Livres jamais empruntés</h2>
                <div class="query-type">SQL: LEFT JOIN + WHERE IS NULL (anti-join pattern)</div>
                <?php if (count($livresJamaisEmpruntes) > 0): ?>
                    <table>
                        <thead>
                            <tr><th>Titre</th><th>Auteur</th><th>Catégorie</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($livresJamaisEmpruntes, 0, 5) as $livre): ?>
                                <tr>
                                    <td><?= htmlspecialchars($livre['TITRE']) ?></td>
                                    <td><?= htmlspecialchars($livre['AUTEUR']) ?></td>
                                    <td><?= htmlspecialchars($livre['NOM_CATEGORIE'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($livresJamaisEmpruntes) > 5): ?>
                        <p style="color:#7f8c8d;margin-top:0.5rem;">... et <?= count($livresJamaisEmpruntes) - 5 ?> autres</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="empty">Tous les livres ont été empruntés au moins une fois</p>
                <?php endif; ?>
            </section>
        </div>

        <h2 style="margin: 2rem 0 1rem; color: #27ae60;">Statistiques NoSQL (MongoDB)</h2>

        <div class="grid-2">
            <!-- Stats commentaires (NoSQL: Aggregation $group sans _id) -->
            <section class="section nosql-section">
                <h2>Statistiques des commentaires</h2>
                <div class="query-type">NoSQL: Aggregation $group (stats globales)</div>
                <?php if ($statsCommentaires): ?>
                    <table>
                        <tr><td>Total commentaires</td><td><strong><?= $statsCommentaires->total_commentaires ?? 0 ?></strong></td></tr>
                        <tr><td>Note moyenne globale</td><td><strong><?= round($statsCommentaires->note_moyenne_globale ?? 0, 1) ?>/5</strong></td></tr>
                        <tr><td>Note minimale</td><td><?= $statsCommentaires->note_min ?? '-' ?>/5</td></tr>
                        <tr><td>Note maximale</td><td><?= $statsCommentaires->note_max ?? '-' ?>/5</td></tr>
                    </table>
                <?php else: ?>
                    <p class="empty">Aucun commentaire</p>
                <?php endif; ?>
            </section>

            <!-- Top livres par note (NoSQL: Aggregation $group + $match + $sort + $limit) -->
            <section class="section nosql-section">
                <h2>Top livres par note</h2>
                <div class="query-type">NoSQL: Aggregation $group + $match + $sort + $limit</div>
                <?php if (count($topParNote) > 0): ?>
                    <table>
                        <thead>
                            <tr><th>ID Livre</th><th>Note moyenne</th><th>Nb avis</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topParNote as $livre): ?>
                                <tr>
                                    <td>#<?= $livre->_id ?></td>
                                    <td>
                                        <span class="etoiles">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?= $i <= round($livre->moyenne) ? '★' : '☆' ?>
                                            <?php endfor; ?>
                                        </span>
                                        <?= round($livre->moyenne, 1) ?>/5
                                    </td>
                                    <td><?= $livre->nb_avis ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty">Pas assez d'avis (min: 2 requis)</p>
                <?php endif; ?>
            </section>

            <!-- Activité SQL mensuelle (DATE_FORMAT + GROUP BY) -->
            <section class="section sql-section">
                <h2>Activité mensuelle (Emprunts)</h2>
                <div class="query-type">SQL: DATE_FORMAT + GROUP BY + ORDER BY</div>
                <?php if (count($activiteMensuelle) > 0): ?>
                    <table>
                        <thead>
                            <tr><th>Mois</th><th>Emprunts</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activiteMensuelle as $mois): ?>
                                <tr>
                                    <td><?= $mois['mois'] ?></td>
                                    <td><span class="badge badge-info"><?= $mois['nb_emprunts'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty">Aucune activité</p>
                <?php endif; ?>
            </section>

            <!-- Activité NoSQL mensuelle ($dateToString + $group) -->
            <section class="section nosql-section">
                <h2>Activité mensuelle (Commentaires)</h2>
                <div class="query-type">NoSQL: Aggregation $dateToString + $group + $sort</div>
                <?php if (count($activiteCommentaires) > 0): ?>
                    <table>
                        <thead>
                            <tr><th>Mois</th><th>Commentaires</th><th>Note moy.</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activiteCommentaires as $mois): ?>
                                <tr>
                                    <td><?= $mois->_id ?></td>
                                    <td><?= $mois->nb_commentaires ?></td>
                                    <td><?= round($mois->note_moyenne, 1) ?>/5</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty">Aucun commentaire</p>
                <?php endif; ?>
            </section>
        </div>

        <!-- Récapitulatif des requêtes -->
        <section class="section" style="background: #2c3e50; color: white;">
            <h2 style="color: white;">Récapitulatif des requêtes utilisées</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1rem;">
                <div>
                    <h3 style="color: #3498db; margin-bottom: 0.5rem;">SQL (MySQL)</h3>
                    <ul style="list-style: none;">
                        <li>LEFT JOIN + GROUP BY + COUNT</li>
                        <li>GROUP BY + HAVING + ORDER BY + LIMIT</li>
                        <li>JOIN + DATE_SUB + WHERE complexe</li>
                        <li>Sous-requêtes multiples</li>
                        <li>AVG + DATEDIFF + COALESCE</li>
                        <li>Anti-join (LEFT JOIN + IS NULL)</li>
                        <li>SUM(CASE WHEN...)</li>
                        <li>DATE_FORMAT + GROUP BY</li>
                        <li>Requêtes dynamiques avec LIKE</li>
                    </ul>
                </div>
                <div>
                    <h3 style="color: #27ae60; margin-bottom: 0.5rem;">NoSQL (MongoDB)</h3>
                    <ul style="list-style: none;">
                        <li>insertOne / insertMany</li>
                        <li>find avec sort, limit, skip</li>
                        <li>find avec $regex</li>
                        <li>updateOne avec $set</li>
                        <li>deleteOne / deleteMany</li>
                        <li>Aggregation $group + $avg</li>
                        <li>Aggregation $match + $sort</li>
                        <li>Aggregation $dateToString</li>
                        <li>count()</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>2026 - Student Project - Made By Milie</p>
    </footer>
</body>
</html>
