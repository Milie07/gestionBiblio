<?php
namespace App\Classes;

class Commentaire
{
    private $manager;
    private $namespace = 'biblioapp.commentaires';

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    // Ajouter un commentaire
    public function ajouter(int $idLivre, int $idUser, string $texte, int $note): bool
    {
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->insert([
            'id_livre' => $idLivre,
            'id_utilisateur' => $idUser,
            'commentaire' => $texte,
            'note' => $note,
            'date' => new \MongoDB\BSON\UTCDateTime()
        ]);
        
        try {
            $this->manager->executeBulkWrite($this->namespace, $bulk);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // Récupérer les commentaires d'un livre
    public function getByLivre(int $idLivre): array
    {
        $query = new \MongoDB\Driver\Query(['id_livre' => $idLivre]);
        $cursor = $this->manager->executeQuery($this->namespace, $query);
        return $cursor->toArray();
    }
}