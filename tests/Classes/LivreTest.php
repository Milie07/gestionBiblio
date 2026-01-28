<?php

namespace Tests\Classes;

use PHPUnit\Framework\TestCase;
use App\Classes\Livre;
use App\Classes\Categorie;
use PDO;
use PDOStatement;

class LivreTest extends TestCase
{
    private $pdoMock;
    private Categorie $categorie;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->categorie = new Categorie(1, 'Roman');
    }

    public function testConstructeur(): void
    {
        $livre = new Livre(
            $this->pdoMock,
            1,
            'Le Petit Prince',
            'Antoine de Saint-Exupéry',
            $this->categorie
        );

        $this->assertSame(1, $livre->getIdLivre());
        $this->assertSame('Le Petit Prince', $livre->getTitre());
        $this->assertSame('Antoine de Saint-Exupéry', $livre->getAuteur());
        $this->assertSame($this->categorie, $livre->getCategorie());
        $this->assertSame('Disponible', $livre->getDisponibilite());
    }

    public function testConstructeurAvecDisponibilite(): void
    {
        $livre = new Livre(
            $this->pdoMock,
            1,
            'Le Petit Prince',
            'Antoine de Saint-Exupéry',
            $this->categorie,
            'Emprunté'
        );

        $this->assertSame('Emprunté', $livre->getDisponibilite());
    }

    public function testSetTitre(): void
    {
        $livre = new Livre($this->pdoMock, 1, 'Titre', 'Auteur', $this->categorie);
        $livre->setTitre('Nouveau Titre');

        $this->assertSame('Nouveau Titre', $livre->getTitre());
    }

    public function testSetAuteur(): void
    {
        $livre = new Livre($this->pdoMock, 1, 'Titre', 'Auteur', $this->categorie);
        $livre->setAuteur('Nouvel Auteur');

        $this->assertSame('Nouvel Auteur', $livre->getAuteur());
    }

    public function testSetDisponibilite(): void
    {
        $livre = new Livre($this->pdoMock, 1, 'Titre', 'Auteur', $this->categorie);
        $livre->setDisponibilite('Emprunté');

        $this->assertSame('Emprunté', $livre->getDisponibilite());
    }

    public function testIsAvailableReturnsTrueQuandDisponible(): void
    {
        $livre = new Livre($this->pdoMock, 1, 'Titre', 'Auteur', $this->categorie, 'Disponible');

        $this->assertTrue($livre->isAvailable());
    }

    public function testIsAvailableReturnsFalseQuandEmprunte(): void
    {
        $livre = new Livre($this->pdoMock, 1, 'Titre', 'Auteur', $this->categorie, 'Emprunté');

        $this->assertFalse($livre->isAvailable());
    }

    public function testCreateBook(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with(['Le Petit Prince', 'Antoine de Saint-Exupéry', 1])
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $livre = new Livre(
            $this->pdoMock,
            0,
            'Le Petit Prince',
            'Antoine de Saint-Exupéry',
            $this->categorie
        );

        $this->assertTrue($livre->createBook());
    }

    public function testDeleteBook(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([1])
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $livre = new Livre($this->pdoMock, 1, 'Titre', 'Auteur', $this->categorie);

        $this->assertTrue($livre->deleteBook());
    }

    public function testUpdateBook(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with(['Nouveau Titre', 'Nouvel Auteur', 'Emprunté', 1])
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $livre = new Livre($this->pdoMock, 1, 'Nouveau Titre', 'Nouvel Auteur', $this->categorie, 'Emprunté');

        $this->assertTrue($livre->updateBook());
    }
}
