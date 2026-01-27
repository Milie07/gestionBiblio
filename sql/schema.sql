USE biblio_app_sql;

DROP TABLE IF EXISTS Emprunt;
DROP TABLE IF EXISTS Livre;
DROP TABLE IF EXISTS Categorie;
DROP TABLE IF EXISTS Utilisateur;

CREATE TABLE Categorie (
  id INT PRIMARY KEY AUTO_INCREMENT,
  libelle_categorie VARCHAR(100) NOT NULL
);

CREATE TABLE Utilisateur (
  id INT PRIMARY KEY AUTO_INCREMENT,
  pseudo VARCHAR(100) UNIQUE NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  mot_de_passe CHAR(60) NOT NULL,
  role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Livre (
  id INT PRIMARY KEY AUTO_INCREMENT,
  titre VARCHAR(255) NOT NULL,
  auteur VARCHAR(100) NOT NULL,
  id_categorie INT NOT NULL,
  disponibilite ENUM('Disponible', 'Emprunté') NOT NULL DEFAULT 'Disponible',
  FOREIGN KEY (id_categorie) REFERENCES Categorie(id)
);

CREATE TABLE Emprunt (
  id INT PRIMARY KEY AUTO_INCREMENT,
  id_livre INT NOT NULL,
  id_utilisateur INT NOT NULL,
  date_emprunt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  date_retour TIMESTAMP NULL,
  FOREIGN KEY (id_livre) REFERENCES Livre(id),
  FOREIGN KEY (id_utilisateur) REFERENCES Utilisateur(id)
);
