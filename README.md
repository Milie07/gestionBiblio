# BIBLIO_APP

Petit Projet d'étude pour la création, la connexion et l'utilisation de BDD SQL et NoSQL (architecture hybride).

---

## Fonctionnalités
- Recherche de livres par titre, auteur et catégorie
- Visualisation du catalogue de livres
- Visualisation d'un détail de livre
- Connexion / Déconnexion
- Emprunt d'un livre par un utilisateur
- Retour d'un livre par un utilisateur
- Possibilité de laisser un commentaire sur le livre

## Environnement de développement
- **Serveur Local** : MAMP, XAMPP ou Docker
  - Apache
  - MySQL
  - PHP 8.3
- **IDE** : Visual Studio Code
- **Tests** : PHPUnit 12.5, PHPStan 2.1

## Bases de Données
- **MYSQL** : 8.0.44
  - Port MAMP : 8889
  - Port XAMPP : 3306
-> Gestion des entités Livre, Utilisateur, Emprunt, Catégorie.
- **MongoDB** : 8.2.4
-> Gestion des commentaires sur les livres (collection Commentaires).

## Prérequis
**Option A : Avec MAMP (macOS/Windows)**
- [Télécharger MAMP](https://www.mamp.info/)
- Démarrer MAMP
- MySQL sera sur le port **8889**

**Option B : Avec XAMPP (Windows/macOS/Linux)**
- [Télécharger XAMPP](https://www.apachefriends.org/)
- Démarrer Apache et MySQL depuis le panneau de contrôle
- MySQL sera sur le port **3306**

**Option C : Avec Docker (Recommandé)**
- [Télécharger Docker Desktop](https://www.docker.com/products/docker-desktop/)
- Aucune configuration manuelle requise (MySQL, MongoDB et PHP sont inclus)

**Pour les options A et B :**
- MongoDB installé
  - macOS : `brew install mongodb-community`
  - Windows : [Télécharger MongoDB](https://www.mongodb.com/try/download/community)
- Extension PHP MongoDB
  - `sudo pecl install mongodb`
  - Ajouter `extension=mongodb.so` dans `php.ini`

## Configuration

### Avec MAMP
```php
// config/database.php
$host = 'localhost';
$port = '8889';  // Port MAMP
```

### Avec XAMPP
```php
// config/database.php
$host = 'localhost';
$port = '3306';  // Port XAMPP par défaut
```

### Avec Docker
Les variables d'environnement sont configurées dans `docker-compose.yml` :
- `DB_HOST=mysql`
- `DB_PORT=3306`
- `MONGO_HOST=mongodb`
- `MONGO_PORT=27017`

## Installation

### 1. Cloner le projet
```bash
git clone [url-du-projet]
```

### 2. Installer les dépendances
```bash
composer install
```

### 3. Démarrer les serveurs

**Avec Docker (Recommandé) :**
```bash
docker-compose up -d
```
- Application : `http://localhost:8080`
- MySQL : port `3307`
- MongoDB : port `27018`

Pour arrêter les conteneurs :
```bash
docker-compose down
```

**Avec MAMP :**
- Ouvrir MAMP
- Cliquer sur "Start Servers"
- Accéder à `http://localhost:8888`

**Avec XAMPP :**
- Ouvrir le panneau de contrôle XAMPP
- Démarrer Apache
- Démarrer MySQL
- Accéder à `http://localhost`

### 4. Créer la base de données

**Via phpMyAdmin :**
- MAMP : `http://localhost:8888/phpMyAdmin/`
- XAMPP : `http://localhost/phpmyadmin/`

**Ou via ligne de commande :**
```bash
# Créer la base
mysql -u root -p < sql/schema.sql

# Insérer les données
mysql -u root -p < sql/data.sql
```

### 5. Démarrer MongoDB (MAMP/XAMPP uniquement)
```bash
# macOS
brew services start mongodb-community

# Windows
start MongoDB
```

### 6. Configuration
- Copier `.env.example` vers `.env` et adapter les paramètres selon votre environnement.
- Modifier `$port` selon votre serveur :
   - MAMP : `8889` (par défaut)
   - XAMPP : `3306`
   - Docker : géré automatiquement

## Tests

### PHPUnit
Lancer tous les tests unitaires :
```bash
./vendor/bin/phpunit
```

Lancer une suite de tests spécifique :
```bash
./vendor/bin/phpunit --testsuite Classes
./vendor/bin/phpunit --testsuite Services
```

### PHPStan
Analyse statique du code :
```bash
./vendor/bin/phpstan analyse src
```

