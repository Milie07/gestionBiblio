<?php

namespace Tests\Classes;

use PHPUnit\Framework\TestCase;
use App\Classes\Categorie;

class CategorieTest extends TestCase
{
    public function testConstructeur(): void
    {
        $categorie = new Categorie(1, 'Roman');

        $this->assertSame(1, $categorie->getId());
        $this->assertSame('Roman', $categorie->getLabel());
    }

    public function testGetId(): void
    {
        $categorie = new Categorie(5, 'Science-Fiction');

        $this->assertSame(5, $categorie->getId());
    }

    public function testGetLabel(): void
    {
        $categorie = new Categorie(2, 'Policier');

        $this->assertSame('Policier', $categorie->getLabel());
    }

    public function testSetLabel(): void
    {
        $categorie = new Categorie(1, 'Roman');
        $categorie->setLabel('Fantastique');

        $this->assertSame('Fantastique', $categorie->getLabel());
    }
}
