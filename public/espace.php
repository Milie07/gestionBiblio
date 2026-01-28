<?php
/**
 * Espace utilisateur - BiblioApp
 * Affiche les emprunts et permet de gérer les commentaires
 *
 * Requêtes SQL utilisées :
 * - JOIN pour afficher emprunts avec détails livres
 * - SELECT avec WHERE et ORDER BY
 *
 * Requêtes NoSQL utilisées :
 * - find avec filtre utilisateur
 * - insertOne pour ajouter commentaire
 * - updateOne pour modifier
 * - deleteOne pour supprimer
 */

session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/mongodb.php';

use App\Services\LoginService;
use App\Services\EmpruntService;
use App\Classes\Commentaire;
use App\Classes\Livre;

$pdo = getDatabaseConnection();
$mongoManager = getMongoDBConnection();

$loginService = new LoginService($pdo);
$empruntService = new EmpruntService($pdo);
$commentaire = new Commentaire($mongoManager);

// Vérifier si l'utilisateur est connecté
if (!$loginService->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = $loginService->getCurrentUser();
$userId = $user['ID_UTILISATEUR'];
$message = '';
$messageType = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        // Ajouter un commentaire (NoSQL: insertOne)
        case 'add_comment':
            $idLivre = (int)$_POST['id_livre'];
            $texte = trim($_POST['commentaire']);
            $note = (int)$_POST['note'];

            if ($texte && $note >= 1 && $note <= 5) {
                if ($commentaire->add($idLivre, $userId, $texte, $note)) {
                    $message = "Commentaire ajouté avec succès";
                    $messageType = 'success';
                } else {
                    $message = "Erreur lors de l'ajout du commentaire";
                    $messageType = 'error';
                }
            } else {
                $message = "Veuillez remplir tous les champs";
                $messageType = 'error';
            }
            break;

        // Modifier un commentaire (NoSQL: updateOne)
        case 'edit_comment':
            $commentId = $_POST['comment_id'];
            $texte = trim($_POST['commentaire']);
            $note = (int)$_POST['note'];

            if ($texte && $note >= 1 && $note <= 5) {
                if ($commentaire->update($commentId, $texte, $note)) {
                    $message = "Commentaire modifié";
                    $messageType = 'success';
                } else {
                    $message = "Erreur lors de la modification";
                    $messageType = 'error';
                }
            }
            break;

        // Supprimer un commentaire (NoSQL: deleteOne)
        case 'delete_comment':
            $commentId = $_POST['comment_id'];
            if ($commentaire->delete($commentId)) {
                $message = "Commentaire supprimé";
                $messageType = 'success';
            } else {
                $message = "Erreur lors de la suppression";
                $messageType = 'error';
            }
            break;

        // Emprunter un livre
        case 'borrow':
            $idLivre = (int)$_POST['id_livre'];
            $result = $empruntService->borrowBook($idLivre, $userId);
            if (is_object($result)) {
                $message = "Livre emprunté avec succès";
                $messageType = 'success';
            } else {
                $message = $result;
                $messageType = 'error';
            }
            break;

        // Retourner un livre
        case 'return':
            $idEmprunt = (int)$_POST['id_emprunt'];
            $result = $empruntService->returnBook($idEmprunt);
            if ($result === true) {
                $message = "Livre retourné avec succès";
                $messageType = 'success';
            } else {
                $message = $result;
                $messageType = 'error';
            }
            break;
    }
}

// Récupérer les données
// SQL: JOIN pour emprunts actifs avec détails livres
$empruntsActifs = $empruntService->getActiveLoans($userId);

// SQL: JOIN pour historique avec détails livres + ORDER BY
$historique = $empruntService->getHistory($userId);

// NoSQL: find avec filtre utilisateur
$mesCommentaires = $commentaire->getCommentsByUser($userId);

// SQL: SELECT pour livres disponibles
$livresDisponibles = [];
$sql = "SELECT l.*, c.NOM_CATEGORIE
        FROM Livre l
        LEFT JOIN Categorie c ON l.ID_CATEGORIE = c.ID_CATEGORIE
        WHERE l.DISPONIBILITE = 'Disponible'
        ORDER BY l.TITRE";
$stmt = $pdo->query($sql);
$livresDisponibles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/public/css/style.css">
    <title>BiblioApp - Mon Espace</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        header { background: #27ae60; color: white; padding: 1rem 2rem; }
        nav ul { list-style: none; display: flex; gap: 2rem; align-items: center; }
        nav a { color: white; text-decoration: none; }
        nav a:hover { text-decoration: underline; }
        .user-info { margin-left: auto; font-weight: 600; }
        main { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        h1 { margin-bottom: 0.5rem; }
        .subtitle { color: #7f8c8d; margin-bottom: 2rem; }
        .section { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section h2 { color: #2c3e50; margin-bottom: 0.3rem; font-size: 1.3rem; }
        .query-type { color: #27ae60; font-size: 0.85rem; margin-bottom: 1rem; font-family: monospace; }
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
        button { background: #27ae60; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-size: 0.9rem; }
        button:hover { background: #219a52; }
        button.danger { background: #e74c3c; }
        button.danger:hover { background: #c0392b; }
        button.secondary { background: #95a5a6; }
        .message { padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .message.error { background: #fee; color: #c0392b; border: 1px solid #e74c3c; }
        .message.success { background: #efd; color: #27ae60; border: 1px solid #2ecc71; }
        .empty { color: #95a5a6; font-style: italic; padding: 1rem; text-align: center; }
        .comment-card { background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .comment-card .meta { color: #7f8c8d; font-size: 0.85rem; margin-bottom: 0.5rem; }
        .comment-card .text { margin-bottom: 0.5rem; }
        .comment-card .actions { display: flex; gap: 0.5rem; }
        .etoiles { color: #f39c12; }
        .form-inline { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.3rem; font-weight: 600; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .star-rating { display: flex; gap: 0.3rem; flex-direction: row-reverse; justify-content: flex-end; }
        .star-rating input { display: none; }
        .star-rating label { cursor: pointer; font-size: 1.5rem; color: #ddd; }
        .star-rating label:hover, .star-rating label:hover ~ label, .star-rating input:checked ~ label { color: #f39c12; }
        footer { background: #2c3e50; color: white; text-align: center; padding: 1rem; margin-top: 2rem; }
        .nosql-section { border-left: 4px solid #27ae60; }
        .sql-section { border-left: 4px solid #3498db; }
    </style>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="espace.php">Mon Espace</a></li>
                <li><a href="login.php?action=logout">Déconnexion</a></li>
                <li class="user-info">Bonjour, <?= htmlspecialchars($user['PSEUDO']) ?></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Mon Espace</h1>
        <p class="subtitle">Gérez vos emprunts et commentaires</p>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="grid-2">
            <!-- Emprunts en cours (SQL: JOIN) -->
            <section class="section sql-section">
                <h2>Mes emprunts en cours</h2>
                <div class="query-type">SQL: JOIN Emprunt + Livre + WHERE + IS NULL</div>
                <?php if (count($empruntsActifs) > 0): ?>
                    <table>
                        <thead>
                            <tr><th>Livre</th><th>Emprunté le</th><th>Retour prévu</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empruntsActifs as $emprunt): ?>
                                <?php
                                    $dateEmprunt = new DateTime($emprunt['DATE_EMPRUNT']);
                                    $dateRetour = new DateTime($emprunt['DATE_RETOUR']);
                                    $aujourdhui = new DateTime();
                                    $enRetard = $aujourdhui > $dateRetour;
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($emprunt['TITRE']) ?></strong><br>
                                        <small><?= htmlspecialchars($emprunt['AUTEUR']) ?></small>
                                    </td>
                                    <td><?= $dateEmprunt->format('d/m/Y') ?></td>
                                    <td>
                                        <?= $dateRetour->format('d/m/Y') ?>
                                        <?php if ($enRetard): ?>
                                            <span class="badge badge-danger">En retard</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="return">
                                            <input type="hidden" name="id_emprunt" value="<?= $emprunt['ID_EMPRUNT'] ?>">
                                            <button type="submit">Retourner</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty">Aucun emprunt en cours</p>
                <?php endif; ?>
            </section>

            <!-- Emprunter un livre -->
            <section class="section sql-section">
                <h2>Emprunter un livre</h2>
                <div class="query-type">SQL: SELECT + LEFT JOIN + WHERE disponible</div>
                <?php if (count($livresDisponibles) > 0): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="borrow">
                        <div class="form-group">
                            <label for="id_livre">Choisir un livre</label>
                            <select name="id_livre" id="id_livre" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($livresDisponibles as $livre): ?>
                                    <option value="<?= $livre['ID_LIVRE'] ?>">
                                        <?= htmlspecialchars($livre['TITRE']) ?> - <?= htmlspecialchars($livre['AUTEUR']) ?>
                                        <?= $livre['NOM_CATEGORIE'] ? '(' . htmlspecialchars($livre['NOM_CATEGORIE']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit">Emprunter (14 jours)</button>
                    </form>
                <?php else: ?>
                    <p class="empty">Aucun livre disponible actuellement</p>
                <?php endif; ?>
            </section>
        </div>

        <!-- Historique (SQL: JOIN + ORDER BY) -->
        <section class="section sql-section">
            <h2>Historique de mes emprunts</h2>
            <div class="query-type">SQL: JOIN Emprunt + Livre + ORDER BY DATE DESC</div>
            <?php if (count($historique) > 0): ?>
                <table>
                    <thead>
                        <tr><th>Livre</th><th>Auteur</th><th>Emprunté le</th><th>Retourné le</th><th>Statut</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historique as $emprunt): ?>
                            <tr>
                                <td><?= htmlspecialchars($emprunt['TITRE']) ?></td>
                                <td><?= htmlspecialchars($emprunt['AUTEUR']) ?></td>
                                <td><?= date('d/m/Y', strtotime($emprunt['DATE_EMPRUNT'])) ?></td>
                                <td><?= $emprunt['DATE_RETOUR'] ? date('d/m/Y', strtotime($emprunt['DATE_RETOUR'])) : '-' ?></td>
                                <td>
                                    <?php if ($emprunt['DATE_RETOUR']): ?>
                                        <span class="badge badge-success">Retourné</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">En cours</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty">Aucun historique</p>
            <?php endif; ?>
        </section>

        <h2 style="margin: 2rem 0 1rem; color: #27ae60;">Mes Commentaires (MongoDB)</h2>

        <div class="grid-2">
            <!-- Ajouter un commentaire (NoSQL: insertOne) -->
            <section class="section nosql-section">
                <h2>Ajouter un commentaire</h2>
                <div class="query-type">NoSQL: insertOne avec BulkWrite</div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_comment">

                    <div class="form-group">
                        <label for="livre_comment">Livre</label>
                        <select name="id_livre" id="livre_comment" required>
                            <option value="">-- Choisir un livre --</option>
                            <?php
                            $allBooks = Livre::getAllBooks($pdo);
                            foreach ($allBooks as $livre): ?>
                                <option value="<?= $livre['ID_LIVRE'] ?>">
                                    <?= htmlspecialchars($livre['TITRE']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Note</label>
                        <div class="star-rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="note" id="star<?= $i ?>" value="<?= $i ?>" <?= $i === 4 ? 'checked' : '' ?>>
                                <label for="star<?= $i ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="commentaire">Commentaire</label>
                        <textarea name="commentaire" id="commentaire" required placeholder="Votre avis sur ce livre..."></textarea>
                    </div>

                    <button type="submit">Publier</button>
                </form>
            </section>

            <!-- Mes commentaires (NoSQL: find + update + delete) -->
            <section class="section nosql-section">
                <h2>Mes commentaires</h2>
                <div class="query-type">NoSQL: find + updateOne + deleteOne</div>
                <?php if (count($mesCommentaires) > 0): ?>
                    <?php foreach ($mesCommentaires as $comm): ?>
                        <?php
                            $commId = (string)$comm->_id;
                            $commDate = $comm->date->toDateTime()->format('d/m/Y H:i');
                        ?>
                        <div class="comment-card">
                            <div class="meta">
                                Livre #<?= $comm->id_livre ?> - <?= $commDate ?>
                                <?php if (isset($comm->date_modification)): ?>
                                    <em>(modifié)</em>
                                <?php endif; ?>
                            </div>
                            <div class="text">
                                <span class="etoiles">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?= $i <= $comm->note ? '★' : '☆' ?>
                                    <?php endfor; ?>
                                </span>
                                <?= htmlspecialchars($comm->commentaire) ?>
                            </div>
                            <div class="actions">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_comment">
                                    <input type="hidden" name="comment_id" value="<?= $commId ?>">
                                    <button type="submit" class="danger" onclick="return confirm('Supprimer ce commentaire ?')">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty">Vous n'avez pas encore laissé de commentaire</p>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer>
        <p>2026 - Student Project - Made By Milie</p>
    </footer>
</body>
</html>
