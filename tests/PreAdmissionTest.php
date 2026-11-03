<?php
namespace Tests;

use App\PreAdmission;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Premiers tests de la création d'une pré-admission (seront complétés en Séance 8).
 */
final class PreAdmissionTest extends TestCase
{
    private PDO $pdo;
    private PreAdmission $repo;

    protected function setUp(): void
    {
        $this->pdo  = BaseDeTest::creer();
        $this->repo = new PreAdmission($this->pdo);
    }

    private function donneesValides(array $surcharge = []): array
    {
        return array_merge([
            'num_securite_sociale'  => '1 78 12 59 178 033 24',
            'nom' => 'roux', 'prenom' => 'Paul', 'date_naissance' => '1978-12-05',
            'adresse' => '8 rue Victor Hugo', 'ville' => '59400 Cambrai', 'telephone' => '06 11 22 33 44', 'email' => '',
            'num_service' => '1', 'date_entree_prevue' => date('Y-m-d', strtotime('+10 days')), 'duree_prevue' => '3',
            'motif_hospitalisation' => 'Opération du genou',
            'num_mutuelle' => '2', 'numero_adherent' => 'MG-555',
            'pc_nom' => 'Roux', 'pc_prenom' => 'Julie', 'pc_telephone' => '06 99 88 77 66', 'pc_lien' => 'épouse',
        ], $surcharge);
    }

    public function testNumSecuValide(): void
    {
        $this->assertTrue(PreAdmission::numSecuValide('1 62 03 59 122 045 04'));
        $this->assertFalse(PreAdmission::numSecuValide('1 62 03 59 122 045 78'), 'Clé incorrecte');
        $this->assertFalse(PreAdmission::numSecuValide('12345'));
    }

    public function testTrouverPatientParNumSecu(): void
    {
        $p = $this->repo->trouverPatient('1 62 03 59 122 045 04');
        $this->assertNotNull($p);
        $this->assertSame('GIRARD', $p['nom']);
        $this->assertNull($this->repo->trouverPatient('178125917803324'));
    }

    public function testEnregistrementNouveauPatient(): void
    {
        $res = $this->repo->enregistrer($this->donneesValides(), 1);

        $this->assertTrue($res['ok'], json_encode($res['erreurs'] ?? []));
        $this->assertTrue($res['nouveau_patient']);
        $pa = $this->pdo->query('SELECT * FROM pre_admission WHERE num_pre_admission = ' . $res['num_pre_admission'])->fetch();
        $this->assertSame(1, (int) $pa['num_personnel_saisie']);
        $pc = $this->pdo->query('SELECT * FROM personne_confiance WHERE num_personne_confiance = ' . $pa['num_personne_confiance'])->fetch();
        $this->assertSame('ROUX', $pc['nom_personne_confiance']);
    }

    public function testPatientExistantNonRecreeEtMutuelleMiseAJour(): void
    {
        $res = $this->repo->enregistrer($this->donneesValides([
            'num_securite_sociale' => '162035912204504', 'nom' => '', 'prenom' => '', 'date_naissance' => '',
            'adresse' => '', 'ville' => '', 'telephone' => '', 'num_mutuelle' => '2', 'numero_adherent' => 'MG-999',
        ]), 1);

        $this->assertTrue($res['ok'], json_encode($res['erreurs'] ?? []));
        $this->assertFalse($res['nouveau_patient']);
        $this->assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM patient')->fetchColumn());
        $this->assertSame('2', (string) $this->pdo->query('SELECT num_mutuelle FROM patient WHERE num_patient = 1')->fetchColumn());
    }

    public function testRefusChampObligatoireManquant(): void
    {
        $res = $this->repo->enregistrer($this->donneesValides(['pc_telephone' => '']), 1);
        $this->assertFalse($res['ok']);
        $this->assertArrayHasKey('pc_telephone', $res['erreurs']);
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM pre_admission')->fetchColumn(), 'Rien ne doit être enregistré');
    }

    public function testRefusFormatsInvalides(): void
    {
        $res = $this->repo->enregistrer($this->donneesValides([
            'num_securite_sociale' => '1 78 12 59 178 033 99', 'telephone' => '123', 'date_entree_prevue' => '31/02/2026',
        ]), 1);
        $this->assertFalse($res['ok']);
        $this->assertArrayHasKey('num_securite_sociale', $res['erreurs']);
        $this->assertArrayHasKey('date_entree_prevue', $res['erreurs']);
    }
}
