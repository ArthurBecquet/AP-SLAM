<?php
namespace Tests;

use App\Auth;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de l'authentification par profil (Séance 7).
 */
final class AuthTest extends TestCase
{
    private PDO $pdo;
    private Auth $auth;

    protected function setUp(): void
    {
        $this->pdo  = BaseDeTest::creer();
        $this->auth = new Auth($this->pdo);
    }

    public static function profils(): array
    {
        return [
            'secrétaire'    => ['secretaire.test',    Auth::SECRETAIRE,    '/secretaire/index.php'],
            'médecin'       => ['medecin.test',       Auth::MEDECIN,       '/medecin/index.php'],
            'administratif' => ['admin.test',         Auth::ADMINISTRATIF, '/admin/index.php'],
            'informaticien' => ['informaticien.test', Auth::INFORMATICIEN, '/informaticien/index.php'],
        ];
    }

    #[DataProvider('profils')]
    public function testConnexionReussiePourChaqueProfil(string $identifiant, string $profil, string $accueil): void
    {
        $u = $this->auth->connecter($identifiant, BaseDeTest::MDP);

        $this->assertNotNull($u, "La connexion de $identifiant doit réussir");
        $this->assertSame($profil, $u['profil']);
        $this->assertSame($accueil, Auth::accueilDe($u), 'Chaque profil est redirigé vers son propre écran');
        $this->assertArrayNotHasKey('mot_de_passe_hash', $u, 'Le hash ne doit jamais être mis en session');
    }

    public function testRefusAvecMauvaisMotDePasse(): void
    {
        $this->assertNull($this->auth->connecter('secretaire.test', 'mauvais'));
    }

    public function testRefusAvecIdentifiantInconnu(): void
    {
        $this->assertNull($this->auth->connecter('inconnu', BaseDeTest::MDP));
    }

    public function testRefusAvecChampsVides(): void
    {
        $this->assertNull($this->auth->connecter('', ''));
        $this->assertNull($this->auth->connecter('secretaire.test', ''));
    }

    public function testRefusTentativeInjectionSql(): void
    {
        $this->assertNull($this->auth->connecter("' OR '1'='1", "' OR '1'='1"));
    }

    public function testRefusCompteDesactive(): void
    {
        $this->assertNull($this->auth->connecter('desactive.test', BaseDeTest::MDP));
    }

    public function testRefusHabilitationExpiree(): void
    {
        $this->assertNull($this->auth->connecter('expire.test', BaseDeTest::MDP, '2026-11-03'));
    }

    public function testMessageEchecGenerique(): void
    {
        // Le message ne doit pas dire si c'est l'identifiant ou le mot de passe qui est faux
        $this->assertSame('Identifiant ou mot de passe incorrect.', Auth::MESSAGE_ECHEC);
    }

    public function testAccesRefuseSansSession(): void
    {
        $this->assertFalse(Auth::peutAcceder(null, [Auth::SECRETAIRE]));
        $this->assertFalse(Auth::peutAcceder([], [Auth::SECRETAIRE]));
    }

    public function testAccesRefuseAvecProfilNonAutorise(): void
    {
        $medecin = $this->auth->connecter('medecin.test', BaseDeTest::MDP);
        $this->assertFalse(Auth::peutAcceder($medecin, [Auth::SECRETAIRE]), 'Un médecin ne doit pas accéder aux pages secrétaire');
    }

    public function testAccesAutoriseAvecBonProfil(): void
    {
        $secretaire = $this->auth->connecter('secretaire.test', BaseDeTest::MDP);
        $this->assertTrue(Auth::peutAcceder($secretaire, [Auth::SECRETAIRE]));
    }

    public function testConnexionTraceeDansJournal(): void
    {
        $u = $this->auth->connecter('secretaire.test', BaseDeTest::MDP);
        $this->auth->tracer($u['num_personnel'], 'CONNEXION', '/index.php', '127.0.0.1');

        $nb = (int) $this->pdo->query("SELECT COUNT(*) FROM journal_acces WHERE type_action = 'CONNEXION' AND num_personnel = 1")->fetchColumn();
        $this->assertSame(1, $nb);
    }
}
