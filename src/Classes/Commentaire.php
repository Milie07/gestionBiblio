<?php
namespace App\Classes;

/**
 * Classe Commentaire - Gestion des commentaires avec MongoDB
 * Démontre l'utilisation de NoSQL avec :
 * - CRUD complet (Create, Read, Update, Delete)
 * - Aggregation pipeline ($group, $avg, $sort, $match)
 * - Requêtes avec filtres multiples
 * - Tri et pagination
 */
class Commentaire
{
    private $manager;
    private $namespace = 'biblioapp.commentaires';
    private $database = 'biblioapp';

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    // ==================== CREATE ====================

    /**
     * Ajouter un commentaire
     * Opération: insertOne
     */
    public function add(int $idLivre, int $idUser, string $texte, int $note): bool
    {
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->insert([
            'id_livre' => $idLivre,
            'id_utilisateur' => $idUser,
            'commentaire' => $texte,
            'note' => $note,
            'date' => new \MongoDB\BSON\UTCDateTime(),
            'date_modification' => null
        ]);

        try {
            $this->manager->executeBulkWrite($this->namespace, $bulk);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // ==================== READ ====================

    /**
     * Récupérer les commentaires d'un livre
     * Opération: find avec filtre
     */
    public function getCommentByBook(int $idLivre): array
    {
        $query = new \MongoDB\Driver\Query(
            ['id_livre' => $idLivre],
            ['sort' => ['date' => -1]] // Tri par date décroissante
        );
        $cursor = $this->manager->executeQuery($this->namespace, $query);
        return $cursor->toArray();
    }

    /**
     * Récupérer les commentaires d'un utilisateur
     * Opération: find avec filtre + sort
     */
    public function getCommentsByUser(int $idUser): array
    {
        $query = new \MongoDB\Driver\Query(
            ['id_utilisateur' => $idUser],
            ['sort' => ['date' => -1]]
        );
        $cursor = $this->manager->executeQuery($this->namespace, $query);
        return $cursor->toArray();
    }

    /**
     * Récupérer un commentaire par son ID
     * Opération: findOne
     */
    public function getById(string $commentId): ?object
    {
        $query = new \MongoDB\Driver\Query(
            ['_id' => new \MongoDB\BSON\ObjectId($commentId)],
            ['limit' => 1]
        );
        $cursor = $this->manager->executeQuery($this->namespace, $query);
        $result = $cursor->toArray();
        return $result[0] ?? null;
    }

    /**
     * Récupérer les derniers commentaires (pagination)
     * Opération: find avec sort + limit + skip
     */
    public function getRecents(int $limit = 10, int $offset = 0): array
    {
        $query = new \MongoDB\Driver\Query(
            [],
            [
                'sort' => ['date' => -1],
                'limit' => $limit,
                'skip' => $offset
            ]
        );
        $cursor = $this->manager->executeQuery($this->namespace, $query);
        return $cursor->toArray();
    }

    /**
     * Rechercher des commentaires par mot-clé
     * Opération: find avec regex
     */
    public function search(string $keyword): array
    {
        $query = new \MongoDB\Driver\Query(
            ['commentaire' => ['$regex' => $keyword, '$options' => 'i']],
            ['sort' => ['date' => -1]]
        );
        $cursor = $this->manager->executeQuery($this->namespace, $query);
        return $cursor->toArray();
    }

    // ==================== UPDATE ====================

    /**
     * Modifier un commentaire
     * Opération: updateOne avec $set
     */
    public function update(string $commentId, string $nouveauTexte, int $nouvelleNote): bool
    {
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->update(
            ['_id' => new \MongoDB\BSON\ObjectId($commentId)],
            ['$set' => [
                'commentaire' => $nouveauTexte,
                'note' => $nouvelleNote,
                'date_modification' => new \MongoDB\BSON\UTCDateTime()
            ]]
        );

        try {
            $result = $this->manager->executeBulkWrite($this->namespace, $bulk);
            return $result->getModifiedCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    // ==================== DELETE ====================

    /**
     * Supprimer un commentaire
     * Opération: deleteOne
     */
    public function delete(string $commentId): bool
    {
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->delete(
            ['_id' => new \MongoDB\BSON\ObjectId($commentId)],
            ['limit' => 1]
        );

        try {
            $result = $this->manager->executeBulkWrite($this->namespace, $bulk);
            return $result->getDeletedCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Supprimer tous les commentaires d'un livre
     * Opération: deleteMany
     */
    public function deleteByBook(int $idLivre): int
    {
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->delete(
            ['id_livre' => $idLivre],
            ['limit' => 0] // 0 = pas de limite, supprime tous
        );

        try {
            $result = $this->manager->executeBulkWrite($this->namespace, $bulk);
            return $result->getDeletedCount();
        } catch (\Exception $e) {
            return 0;
        }
    }

    // ==================== AGGREGATION ====================

    /**
     * Note moyenne par livre
     * Opération: Aggregation pipeline avec $group et $avg
     */
    public function getNoteMoyenneParLivre(): array
    {
        $pipeline = [
            [
                '$group' => [
                    '_id' => '$id_livre',
                    'moyenne' => ['$avg' => '$note'],
                    'nb_avis' => ['$sum' => 1],
                    'note_min' => ['$min' => '$note'],
                    'note_max' => ['$max' => '$note']
                ]
            ],
            [
                '$sort' => ['moyenne' => -1]
            ]
        ];

        $command = new \MongoDB\Driver\Command([
            'aggregate' => 'commentaires',
            'pipeline' => $pipeline,
            'cursor' => new \stdClass()
        ]);

        try {
            $cursor = $this->manager->executeCommand($this->database, $command);
            return $cursor->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Note moyenne d'un livre spécifique
     * Opération: Aggregation avec $match + $group
     */
    public function getNoteMoyenne(int $idLivre): ?array
    {
        $pipeline = [
            [
                '$match' => ['id_livre' => $idLivre]
            ],
            [
                '$group' => [
                    '_id' => '$id_livre',
                    'moyenne' => ['$avg' => '$note'],
                    'nb_avis' => ['$sum' => 1]
                ]
            ]
        ];

        $command = new \MongoDB\Driver\Command([
            'aggregate' => 'commentaires',
            'pipeline' => $pipeline,
            'cursor' => new \stdClass()
        ]);

        try {
            $cursor = $this->manager->executeCommand($this->database, $command);
            $result = $cursor->toArray();
            return $result[0] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Statistiques globales des commentaires
     * Opération: Aggregation avec $group sans _id (stats globales)
     */
    public function getStatistiques(): ?array
    {
        $pipeline = [
            [
                '$group' => [
                    '_id' => null,
                    'total_commentaires' => ['$sum' => 1],
                    'note_moyenne_globale' => ['$avg' => '$note'],
                    'note_min' => ['$min' => '$note'],
                    'note_max' => ['$max' => '$note']
                ]
            ]
        ];

        $command = new \MongoDB\Driver\Command([
            'aggregate' => 'commentaires',
            'pipeline' => $pipeline,
            'cursor' => new \stdClass()
        ]);

        try {
            $cursor = $this->manager->executeCommand($this->database, $command);
            $result = $cursor->toArray();
            return $result[0] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Top livres par note moyenne (avec minimum d'avis)
     * Opération: Aggregation avec $group + $match + $sort + $limit
     */
    public function getTopLivresParNote(int $minAvis = 2, int $limit = 5): array
    {
        $pipeline = [
            [
                '$group' => [
                    '_id' => '$id_livre',
                    'moyenne' => ['$avg' => '$note'],
                    'nb_avis' => ['$sum' => 1]
                ]
            ],
            [
                '$match' => ['nb_avis' => ['$gte' => $minAvis]]
            ],
            [
                '$sort' => ['moyenne' => -1]
            ],
            [
                '$limit' => $limit
            ]
        ];

        $command = new \MongoDB\Driver\Command([
            'aggregate' => 'commentaires',
            'pipeline' => $pipeline,
            'cursor' => new \stdClass()
        ]);

        try {
            $cursor = $this->manager->executeCommand($this->database, $command);
            return $cursor->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Activité des commentaires par mois
     * Opération: Aggregation avec $dateToString + $group
     */
    public function getActiviteParMois(): array
    {
        $pipeline = [
            [
                '$group' => [
                    '_id' => [
                        '$dateToString' => [
                            'format' => '%Y-%m',
                            'date' => '$date'
                        ]
                    ],
                    'nb_commentaires' => ['$sum' => 1],
                    'note_moyenne' => ['$avg' => '$note']
                ]
            ],
            [
                '$sort' => ['_id' => -1]
            ],
            [
                '$limit' => 12
            ]
        ];

        $command = new \MongoDB\Driver\Command([
            'aggregate' => 'commentaires',
            'pipeline' => $pipeline,
            'cursor' => new \stdClass()
        ]);

        try {
            $cursor = $this->manager->executeCommand($this->database, $command);
            return $cursor->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Compter le nombre total de commentaires
     * Opération: count
     */
    public function count(): int
    {
        $command = new \MongoDB\Driver\Command([
            'count' => 'commentaires'
        ]);

        try {
            $cursor = $this->manager->executeCommand($this->database, $command);
            $result = $cursor->toArray();
            return $result[0]->n ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}