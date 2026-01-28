<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\LoginService;
use App\Classes\Utilisateur;
use PDO;
use PDOStatement;

class LoginServiceTest extends TestCase
{
    private $pdoMock;
    private LoginService $service;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->service = new LoginService($this->pdoMock);

        // Reset session pour chaque test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testLoginUtilisateurInexistant(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn(false);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->service->login('inconnu@test.com', 'password');

        $this->assertNull($result);
    }

    public function testLoginMotDePasseIncorrect(): void
    {
        $hashedPassword = password_hash('correct_password', PASSWORD_DEFAULT);

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn([
            'ID_UTILISATEUR' => 1,
            'PSEUDO' => 'john',
            'EMAIL' => 'john@test.com',
            'PASSWORD' => $hashedPassword,
            'DATE_INSCRIPTION' => '2024-01-15'
        ]);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->service->login('john@test.com', 'wrong_password');

        $this->assertNull($result);
    }

    public function testLoginSucces(): void
    {
        $hashedPassword = password_hash('correct_password', PASSWORD_DEFAULT);

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn([
            'ID_UTILISATEUR' => 1,
            'PSEUDO' => 'john',
            'EMAIL' => 'john@test.com',
            'PASSWORD' => $hashedPassword,
            'DATE_INSCRIPTION' => '2024-01-15'
        ]);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->service->login('john@test.com', 'correct_password');

        $this->assertInstanceOf(Utilisateur::class, $result);
        $this->assertSame('john', $result->getPseudo());
        $this->assertSame('john@test.com', $result->getEmail());
    }

    public function testIsLoggedInReturnsFalseQuandNonConnecte(): void
    {
        $_SESSION = [];

        $this->assertFalse(LoginService::isLoggedIn());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIsLoggedInReturnsTrueQuandConnecte(): void
    {
        session_start();
        $_SESSION['id_utilisateur'] = 1;
        $_SESSION['logged_in'] = true;

        $this->assertTrue(LoginService::isLoggedIn());
    }

    public function testGetCurrentUserIdRetourneNullQuandNonConnecte(): void
    {
        $_SESSION = [];

        $this->assertNull(LoginService::getCurrentUserId());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetCurrentUserIdRetourneIdQuandConnecte(): void
    {
        session_start();
        $_SESSION['id_utilisateur'] = 42;

        $this->assertSame(42, LoginService::getCurrentUserId());
    }

    public function testGetCurrentUserPseudoRetourneNullQuandNonConnecte(): void
    {
        $_SESSION = [];

        $this->assertNull(LoginService::getCurrentUserPseudo());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetCurrentUserPseudoRetournePseudoQuandConnecte(): void
    {
        session_start();
        $_SESSION['pseudo'] = 'john_doe';

        $this->assertSame('john_doe', LoginService::getCurrentUserPseudo());
    }

    public function testGetCurrentUserRetourneNullQuandNonConnecte(): void
    {
        $_SESSION = [];

        $result = $this->service->getCurrentUser();

        $this->assertNull($result);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetCurrentUserRetourneUtilisateurQuandConnecte(): void
    {
        session_start();
        $_SESSION['id_utilisateur'] = 1;

        $pdoMock = $this->createMock(PDO::class);
        $service = new LoginService($pdoMock);

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn([
            'ID_UTILISATEUR' => 1,
            'PSEUDO' => 'john',
            'EMAIL' => 'john@test.com',
            'PASSWORD' => 'hashed',
            'DATE_INSCRIPTION' => '2024-01-15'
        ]);

        $pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $service->getCurrentUser();

        $this->assertInstanceOf(Utilisateur::class, $result);
        $this->assertSame('john', $result->getPseudo());
    }
}
