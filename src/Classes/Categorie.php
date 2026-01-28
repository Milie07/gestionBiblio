<?php
namespace App\Classes;

class Categorie 
{
  private int $id;
  private string $libelle_categorie;

  public function __construct(int $id, string $libelle_categorie)
  {
    $this->id = $id;
    $this->libelle_categorie = $libelle_categorie;
  }
  
  public function getId(): int
  {
    return $this->id;
  }
  public function getLabel(): string
  {
    return $this->libelle_categorie;
  }

  public function setLabel($libelle_categorie): void
  {
    $this->libelle_categorie = $libelle_categorie;
  }
}