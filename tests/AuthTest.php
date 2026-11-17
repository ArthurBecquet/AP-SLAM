<?php

use PHPUnit\Framework\TestCase;

/**
 * Authentification par profil : connexion, refus, contrôle d'accès, journal.
 */
final class AuthTest extends TestCase
{
    private PDO $pdo;
    private Auth $auth;

    protected function setUp(): void
    {
        $this->pdo = BaseDeTest::creer();
        $this->auth = new Auth($this->pdo);
    }

    private function connecter(string $identifiant, ?string $mdp = null, ?string $jour = null): ?array
    {
        return $this->auth->connecter($identifiant, $mdp ?? BaseDeTest::motDePasse(), $jour ?? '2026-11-17');
    }

    public function testConnexionDesQuatreProfils(): void
    {
        $attendus = [
            'secretaire.test'    => [Auth::SECRETAIRE, 'preadmissions.php'],
            'medecin.test'       => [Auth::MEDECIN, 'accueil.php'],
            'admin.test'         => [Auth::ADMINISTRATIF, 'accueil.php'],
            'informaticien.test' => [Auth::INFORMATICIEN, 'accueil.php'],
        ];
        foreach ($attendus as $identifiant => [$profil, $ecran]) {
            $u = $this->connecter($identifiant);
            $this->assertNotNull($u, $identifiant);
            $this->assertSame($profil, $u['profil']);
            $this->assertSame($ecran, Auth::ACCUEIL[$u['profil']]);
        }
    }

    public function testLeHashNestPasRenvoye(): void
    {
        $this->assertArrayNotHasKey('mot_de_passe_hash', $this->connecter('secretaire.test'));
    }

    public function testRefusMauvaisMotDePasse(): void
    {
        $this->assertNull($this->connecter('secretaire.test', 'mauvais'));
    }

    public function testRefusIdentifiantInconnu(): void
    {
        $this->assertNull($this->connecter('inconnu'));
    }

    public function testRefusChampsVides(): void
    {
        $this->assertNull($this->connecter('', ''));
        $this->assertNull($this->connecter('secretaire.test', ''));
        $this->assertNull($this->connecter('   '));
    }

    public function testRefusTentativeInjectionSql(): void
    {
        $this->assertNull($this->connecter("' OR '1'='1"));
        $this->assertNull($this->connecter("secretaire.test' --", 'x'));
    }

    public function testRefusCompteDesactive(): void
    {
        $this->assertNull($this->connecter('desactive.test'));
    }

    public function testRefusHabilitationExpiree(): void
    {
        $this->assertNull($this->connecter('expire.test'));
    }

    public function testMessageEchecGenerique(): void
    {
        $this->assertSame('Identifiant ou mot de passe incorrect.', Auth::MESSAGE_ECHEC);
    }

    public function testAccesRefuseSansSession(): void
    {
        $this->assertFalse(Auth::peutAcceder(null, [Auth::SECRETAIRE]));
    }

    public function testAccesRefuseMauvaisProfil(): void
    {
        $medecin = $this->connecter('medecin.test');
        $this->assertFalse(Auth::peutAcceder($medecin, [Auth::SECRETAIRE]));
    }

    public function testAccesAutoriseBonProfil(): void
    {
        $secretaire = $this->connecter('secretaire.test');
        $this->assertTrue(Auth::peutAcceder($secretaire, [Auth::SECRETAIRE]));
    }

    public function testConnexionTracee(): void
    {
        $u = $this->connecter('secretaire.test');
        $this->auth->tracer($u['num_personnel'], 'CONNEXION', 'index.php', '127.0.0.1');
        $ligne = $this->pdo->query('SELECT type_action, num_personnel FROM journal_acces')->fetch();
        $this->assertSame(['type_action' => 'CONNEXION', 'num_personnel' => 1], $ligne);
    }

    public function testHachageMotDePasse(): void
    {
        $hash = password_hash('Secret123!', PASSWORD_DEFAULT);
        $this->assertNotSame('Secret123!', $hash);
        $this->assertTrue(password_verify('Secret123!', $hash));
        $this->assertFalse(password_verify('autre', $hash));
    }
}
