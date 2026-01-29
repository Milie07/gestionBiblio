<?php

namespace Tests\Classes;

use PHPUnit\Framework\TestCase;
use App\Classes\Emprunt;
use App\Classes\Livre;
use App\Classes\Utilisateur;
use App\Classes\Categorie;
use DateTimeImmutable;
use PDO;
use PDOStatement;

class EmpruntTest extends TestCase
{
    private $pdoMock;
    private Livre $livre;
    private Utilisateur $utilisateur;
    private DateTimeImmutable $dateEmprunt;
    private DateTimeImmutable $dateRetour;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $categorie = new Categorie(1, 'Roman');

        $this->livre = new Livre($this->pdoMock, 1, 'Le Petit Prince', 'Saint-Exupéry', $categorie);
        $this->utilisateur = new Utilisateur($this->pdoMock, 1, 'john', 'john@test.com', 'pass', new DateTimeImmutable());
        $this->dateEmprunt = new DateTimeImmutable('2024-01-15');
        $this->dateRetour = new DateTimeImmutable('2024-01-29');
    }

    public function testConstructeur(): void
    {
        $emprunt = new Emprunt(
            $this->pdoMock,
            1,
            $this->livre,
            $this->utilisateur,
            $this->dateEmprunt,
            $this->dateRetour
        );

        $this->assertSame(1, $emprunt->getIdEmprunt());
        $this->assertSame($this->livre, $emprunt->getLivre());
        $this->assertSame($this->utilisateur, $emprunt->getUtilisateur());
        $this->assertSame($this->dateEmprunt, $emprunt->getDateEmprunt());
        $this->assertSame($this->dateRetour, $emprunt->getDateRetour());
    }

    public function testGetIdEmprunt(): void
    {
        $emprunt = new Emprunt($this->pdoMock, 5, $this->livre, $this->utilisateur, $this->dateEmprunt, $this->dateRetour);

        $this->assertSame(5, $emprunt->getIdEmprunt());
    }

    public function testGetLivre(): void
    {
        $emprunt = new Emprunt($this->pdoMock, 1, $this->livre, $this->utilisateur, $this->dateEmprunt, $this->dateRetour);

        $this->assertSame($this->livre, $emprunt->getLivre());
        $this->assertSame('Le Petit Prince', $emprunt->getLivre()->getTitre());
    }

    public function testGetUtilisateur(): void
    {
        $emprunt = new Emprunt($this->pdoMock, 1, $this->livre, $this->utilisateur, $this->dateEmprunt, $this->dateRetour);

        $this->assertSame($this->utilisateur, $emprunt->getUtilisateur());
        $this->assertSame('john', $emprunt->getUtilisateur()->getPseudo());
    }

    public function testGetDateEmprunt(): void
    {
        $emprunt = new Emprunt($this->pdoMock, 1, $this->livre, $this->utilisateur, $this->dateEmprunt, $this->dateRetour);

        $this->assertSame('2024-01-15', $emprunt->getDateEmprunt()->format('Y-m-d'));
    }

    public function testGetDateRetour(): void
    {
        $emprunt = new Emprunt($this->pdoMock, 1, $this->livre, $this->utilisateur, $this->dateEmprunt, $this->dateRetour);

        $this->assertSame('2024-01-29', $emprunt->getDateRetour()->format('Y-m-d'));
    }

    public function testCreate(): void
    {
        // Mock pour l'insertion de l'emprunt
        $stmtEmprunt = $this->createMock(PDOStatement::class);
        $stmtEmprunt->expects($this->once())
            ->method('execute')
            ->with([1, 1])
            ->willReturn(true);

        // Mock pour la mise à jour du livre
        $stmtLivre = $this->createMock(PDOStatement::class);
        $stmtLivre->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtEmprunt, $stmtLivre);

        $emprunt = new Emprunt($this->pdoMock, 0, $this->livre, $this->utilisateur, $this->dateEmprunt, $this->dateRetour);

        $this->assertTrue($emprunt->create());
        $this->assertSame('Emprunté', $this->livre->getDisponibilite());
    }

    public function testIsActive(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([1, 1])
            ->willReturn(true);
        $stmtMock->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(1);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $emprunt = new Emprunt($this->pdoMock, 1, $this->livre, $this->utilisateur, $this->dateEmprunt, $this->dateRetour);

        $this->assertTrue($emprunt->isActive());
    }

    public function testIsActiveReturnsFalseQuandPasEmprunt(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $stmtMock->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(0);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $emprunt = new Emprunt($this->pdoMock, 1, $this->livre, $this->utilisateur, $this->dateEmprunt, $this->dateRetour);

        $this->assertFalse($emprunt->isActive());
    }
}
