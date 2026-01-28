<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\EmpruntService;
use App\Classes\Emprunt;
use PDO;
use PDOStatement;

class EmpruntServiceTest extends TestCase
{
    private $pdoMock;
    private EmpruntService $service;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->service = new EmpruntService($this->pdoMock);
    }

    public function testBorrowBookLivreInexistant(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn(false);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->service->borrowBook(999, 1);

        $this->assertIsString($result);
        $this->assertStringContainsString("n'existe pas", $result);
    }

    public function testBorrowBookLivreDejaEmprunte(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn([
            'ID_LIVRE' => 1,
            'TITRE' => 'Test',
            'AUTEUR' => 'Auteur',
            'ID_CATEGORIE' => 1,
            'DISPONIBILITE' => 'Emprunté'
        ]);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->service->borrowBook(1, 1);

        $this->assertIsString($result);
        $this->assertStringContainsString("déjà emprunté", $result);
    }

    public function testBorrowBookUtilisateurADejaLeLivre(): void
    {
        $stmtLivre = $this->createMock(PDOStatement::class);
        $stmtLivre->method('execute')->willReturn(true);
        $stmtLivre->method('fetch')->willReturn([
            'ID_LIVRE' => 1,
            'TITRE' => 'Test',
            'AUTEUR' => 'Auteur',
            'ID_CATEGORIE' => 1,
            'DISPONIBILITE' => 'Disponible'
        ]);

        $stmtEmprunt = $this->createMock(PDOStatement::class);
        $stmtEmprunt->method('execute')->willReturn(true);
        $stmtEmprunt->method('fetchColumn')->willReturn(1); // L'utilisateur a déjà ce livre

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtLivre, $stmtEmprunt);

        $result = $this->service->borrowBook(1, 1);

        $this->assertIsString($result);
        $this->assertStringContainsString("déjà emprunté ce livre", $result);
    }

    public function testReturnBookEmpruntInexistant(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn(false);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->service->returnBook(999);

        $this->assertIsString($result);
        $this->assertStringContainsString("n'existe pas", $result);
    }

    public function testReturnBookDejaRetourne(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn([
            'ID_EMPRUNT' => 1,
            'ID_LIVRE' => 1,
            'ID_UTILISATEUR' => 1,
            'DATE_EMPRUNT' => '2024-01-15',
            'DATE_RETOUR' => '2024-01-20' // Déjà retourné
        ]);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->service->returnBook(1);

        $this->assertIsString($result);
        $this->assertStringContainsString("déjà été retourné", $result);
    }

    public function testReturnBookSucces(): void
    {
        $stmtSelect = $this->createMock(PDOStatement::class);
        $stmtSelect->method('execute')->willReturn(true);
        $stmtSelect->method('fetch')->willReturn([
            'ID_EMPRUNT' => 1,
            'ID_LIVRE' => 1,
            'ID_UTILISATEUR' => 1,
            'DATE_EMPRUNT' => '2024-01-15',
            'DATE_RETOUR' => null // Pas encore retourné
        ]);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtSelect, $stmtUpdate, $stmtUpdate);

        $result = $this->service->returnBook(1);

        $this->assertTrue($result);
    }

    public function testGetActiveLoans(): void
    {
        $expectedLoans = [
            ['ID_EMPRUNT' => 1, 'TITRE' => 'Livre 1'],
            ['ID_EMPRUNT' => 2, 'TITRE' => 'Livre 2'],
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($expectedLoans);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->service->getActiveLoans(1);

        $this->assertCount(2, $result);
        $this->assertSame('Livre 1', $result[0]['TITRE']);
    }

    public function testGetHistory(): void
    {
        $expectedHistory = [
            ['ID_EMPRUNT' => 1, 'TITRE' => 'Livre 1', 'DATE_RETOUR' => '2024-01-20'],
            ['ID_EMPRUNT' => 2, 'TITRE' => 'Livre 2', 'DATE_RETOUR' => null],
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($expectedHistory);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->service->getHistory(1);

        $this->assertCount(2, $result);
    }
}
