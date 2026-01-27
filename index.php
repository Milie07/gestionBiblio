<?php

?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/public/css/style.css">
  <title>BiblioApp</title>
</head>
<body>
  <header role="header">
    <nav role="navigation">
      <ul>
        <li>accueil</li>
        <li>catalogue</li>
        <li>connexion</li>
    </ul>
    </nav>
  </header>
  <main role="section principale">
    <h1>Bienvenur sur BiblioApp</h1>
    <section class="search" aria-label="formulaire de recherche de livre">
      <label for="searchInput">Recherche</label>
      <input type="text" name="searchinput" id="search" placeholder="titre, auteur, catégorie">
      <button type="submit" value="Envoyer" name="Envoyer">Envoyer</button>

      <div class="filtre">
        <label for="filter">Titre</label>
        <input type="radio" name="filter" id="filter" value="titre">
        <label for="filter">Auteur</label>
        <input type="radio" name="filter" id="filter" value="auteur">
        <label for="filter">Catégorie</label>
        <input type="radio" name="filter" id="filter" value="catégorie">
        <button type="submit" value="Filtrer" name="Filtrer">Filtrer</button>
      </div>
    </section>
  </main>
  <footer>
    <p>©️2026 - Student Project - Made By Milie </p>
  </footer>
</body>
</html>