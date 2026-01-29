<?php

namespace Tests\Classes;

use PHPUnit\Framework\TestCase;
use App\Classes\Utilisateur;
use DateTimeImmutable;
use PDO;
use PDOStatement;

class UtilisateurTest extends TestCase
{
    private $pdoMock;
    private DateTimeImmutable $dateInscription;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->dateInscription = new DateTimeImmutable('2024-01-15');
    }

    public function testConstructeur(): void
    {
        $user = new Utilisateur(
            $this->pdoMock,
            1,
            'john_doe',
            'john@example.com',
            'hashed_password',
            $this->dateInscription
        );

        $this->assertSame(1, $user->getUserId());
        $this->assertSame('john_doe', $user->getPseudo());
        $this->assertSame('john@example.com', $user->getEmail());
        $this->assertSame('hashed_password', $user->getPassword());
        $this->assertSame($this->dateInscription, $user->getDateInscription());
    }

    public function testSetPseudo(): void
    {
        $user = new Utilisateur($this->pdoMock, 1, 'old_pseudo', 'email@test.com', 'pass', $this->dateInscription);
        $user->setPseudo('new_pseudo');

        $this->assertSame('new_pseudo', $user->getPseudo());
    }

    public function testSetEmail(): void
    {
        $user = new Utilisateur($this->pdoMock, 1, 'pseudo', 'old@test.com', 'pass', $this->dateInscription);
        $user->setEmail('new@test.com');

        $this->assertSame('new@test.com', $user->getEmail());
    }

    public function testSetPassword(): void
    {
        $user = new Utilisateur($this->pdoMock, 1, 'pseudo', 'email@test.com', 'old_pass', $this->dateInscription);
        $user->setPassword('new_pass');

        $this->assertSame('new_pass', $user->getPassword());
    }

    public function testVerifyPasswordAvecMotDePasseCorrect(): void
    {
        $hashedPassword = password_hash('mon_mot_de_passe', PASSWORD_DEFAULT);
        $user = new Utilisateur($this->pdoMock, 1, 'pseudo', 'email@test.com', $hashedPassword, $this->dateInscription);

        $this->assertTrue($user->verifyPassword('mon_mot_de_passe'));
    }

    public function testVerifyPasswordAvecMotDePasseIncorrect(): void
    {
        $hashedPassword = password_hash('mon_mot_de_passe', PASSWORD_DEFAULT);
        $user = new Utilisateur($this->pdoMock, 1, 'pseudo', 'email@test.com', $hashedPassword, $this->dateInscription);

        $this->assertFalse($user->verifyPassword('mauvais_mot_de_passe'));
    }

    public function testRegisterUser(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $user = new Utilisateur($this->pdoMock, 0, 'new_user', 'new@test.com', 'password123', $this->dateInscription);

        $this->assertTrue($user->registerUser());
    }

    public function testDeleteUser(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([1])
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $user = new Utilisateur($this->pdoMock, 1, 'pseudo', 'email@test.com', 'pass', $this->dateInscription);

        $this->assertTrue($user->deleteUser());
    }

    public function testUpdateUser(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $user = new Utilisateur($this->pdoMock, 1, 'updated_pseudo', 'updated@test.com', 'new_pass', $this->dateInscription);

        $this->assertTrue($user->updateUser());
    }
}
