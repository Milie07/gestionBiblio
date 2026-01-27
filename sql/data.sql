USE biblio_app_sql;

INSERT INTO Categorie (libelle_categorie)
VALUES 
('Romance'),
('Aventure'),
('Fantastique'),
('Polar'),
('Bien-Être'),
('Littérature');

INSERT INTO Utilisateur (pseudo, email, mot_de_passe, role)
VALUES
('MilieAdmin', 'milie@biblio.fr', 'hash123', 'admin'),
('user1', 'user1@gmail.fr', 'hash456', 'user'),
('user2', 'user2@gmail.fr', 'hash789', 'user');

INSERT INTO Livre (titre, auteur, id_categorie)
VALUES
('L''Ile au trésor', 'Robert Louis Stevenson', 2),
('Le Tour du monde en 80 jours', 'Jules Verne', 2),
('Vingt mille lieues sous les mers', 'Jules Verne', 2),
('Robinson Crusoé', 'Daniel Dafoe', 2),
('Les Aventures de Tom Sawyer', 'Mark Twain', 2),
('Moby Dick', 'Hermann Melville', 2),
('Voyage au centre de la Terre', 'Jules Verne', 2),
('L''Appel de la forêt', 'Jack London', 2),
('Le Comte de Monte-Cristo', 'Alexandre Dumas', 2),
('Harry Potter à l''école des sorciers', 'J.K. Rowling', 3),
('Le Seigneur des Anneaux', 'J.R.R. Tolkien', 3),
('Le Lion, la Sorcière blanche et l''Armoire magique', 'C.S. Lewis', 3),
('Alice au pays des merveilles', 'Lewis Carroll', 3),
('Percy Jackson : Le Voleur de foudre', 'Rick Riordan', 3),
('Eragon', 'Christopher Paolini', 3),
('Twilight', 'Stephenie Meyer', 3),
('Le Hobbit', 'J.R.R. Tolkien', 3),
('Charlie et la chocolaterie', 'Roald Dahl', 3),
('Orgueil et Préjugés', 'Jane Austen', 1),
('Roméo et Juliette', 'William Shakespeare', 1),
('Les Hauts de Hurlevent', 'Emily Brontë', 1),
('Jane Eyre', 'Emily Brontë', 1),
('Un long dimanche de fiançailles', 'Sébastien Japrisot', 1),
('Le Temps d''un automne', 'Nicholas Sparks', 1),
('Anna Karénine', 'Léon Tolstoï', 1),
('La Dame aux camélias', 'Alexandre Dumas fils', 4),
('Sherlock Holmes : Le Chien des Baskerville', 'Arthur Conan Doyle', 4),
('Le Crime de l''Orient-Express', 'Agatha Christie', 4),
('Dix petits nègres', 'Agatha Christie', 4),
('La Vérité sur l''affaire Harry Quebert', 'Joël Dicker', 4),
('Millénium : Les Hommes qui n''aimaient pas les femmes', 'Stieg Larsson', 4),
('Le Silence des agneaux', 'Thomas Harris', 4),
('Le Parfum', 'Patrick Süskind', 4),
('A Découvert', 'Harlan Coben', 4),
('Les Misérables', 'Victor Hugo', 6),
('L''Étranger ', 'Albert Camus', 6),
('Le Petit Prince', 'Antoine de Saint-Exupéry', 6),
('Madame Bovary', 'Gustave Flaubert', 6),
('1984', 'George Orwell', 6),
('Le Meilleur des monde', 'Aldous Huxley', 6),
('Germinal', 'Émile Zola', 6),
('Candide', 'Voltaire', 6),
('Le Pouvoir du moment présent', 'Eckhart Tolle', 5),
('L''Alchimiste', 'Paulo Coelho', 5),
('Ta deuxième vie commence quand tu comprends que tu n''en as qu''une', 'Raphaëlle Giordano', 5),
('Les Quatre Accords toltèques', 'Don Miguel Ruiz', 5),
('Homme cherche sens à sa vie', 'Viktor Frankl', 5),
('Mange, prie, aime', 'Elizabeth Gilbert', 5),
('La Magie du rangement', 'Marie Kondo', 5),
('La Prophécie des Andes', 'James Redfield', 5);

INSERT INTO Emprunt (id_livre, id_utilisateur)
VALUES
('50', '2'),
('25', '3'),
('15', '2'),
('40', '3');

