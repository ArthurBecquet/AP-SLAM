<?php

use PHPUnit\Framework\TestCase;

/**
 * Création d'une pré-admission : enregistrement, contrôles de saisie, contrôle d'accès.
 */
final class PreAdmissionTest extends TestCase
{
    private PDO $pdo;
    private PreAdmission $service;
    private array $secretaire;
    private DateTimeImmutable $aujourdhui;

    protected function setUp(): void
    {
        $this->pdo = BaseDeTest::creer();
        $this->service = new PreAdmission($this->pdo);
        $this->secretaire = BaseDeTest::utilisateur(1, Auth::SECRETAIRE);
        $this->aujourdhui = new DateTimeImmutable('2026-11-17');
    }

    /** Saisie complète et valide pour un nouveau patient. */
    private function saisie(array $modifs = []): array
    {
        return array_merge([
            'num_secu' => '1 78 12 59 178 033 24', 'nom' => 'Roux', 'prenom' => 'Paul', 'date_naissance' => '05/12/1978',
            'adresse' => '8 rue Victor Hugo', 'ville' => '59400 Cambrai', 'telephone' => '06 11 22 33 44', 'email' => '',
            'service' => '1', 'date_entree' => '27/11/2026', 'duree' => '3', 'motif' => 'Opération du genou',
            'organisme' => '1', 'mutuelle' => '2', 'numero_adherent' => 'MG-555',
            'pc_nom' => 'Roux', 'pc_prenom' => 'Julie', 'pc_telephone' => '06 99 88 77 66', 'pc_lien' => 'épouse',
        ], $modifs);
    }

    private function enregistrer(array $modifs = [], ?array $utilisateur = null): array
    {
        return $this->service->enregistrer($utilisateur ?? $this->secretaire, $this->saisie($modifs), $this->aujourdhui);
    }

    private function compter(string $table): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    }

    private function assertRefus(array $modifs, string $champ): void
    {
        $r = $this->enregistrer($modifs);
        $this->assertFalse($r['ok']);
        $this->assertArrayHasKey($champ, $r['erreurs']);
        $this->assertSame(0, $this->compter('pre_admission'), 'rien ne doit être enregistré');
    }

    public function testNumSecuValide(): void
    {
        $this->assertTrue(Validation::numSecuValide('1 62 03 59 122 045 04'));
        $this->assertFalse(Validation::numSecuValide('1 62 03 59 122 045 99'));
        $this->assertFalse(Validation::numSecuValide('123'));
    }

    public function testRecherchePatientParNumSecu(): void
    {
        $p = $this->service->rechercherPatient($this->secretaire, '1 62 03 59 122 045 04');
        $this->assertSame('GIRARD', $p['nom']);
        $this->assertSame(1, $p['num_organisme']);
        $this->assertNull($this->service->rechercherPatient($this->secretaire, '1 78 12 59 178 033 24'));
    }

    public function testEnregistrementNouveauPatient(): void
    {
        $r = $this->enregistrer();
        $this->assertTrue($r['ok']);
        $this->assertTrue($r['nouveau_patient']);
        $this->assertSame(1, $this->compter('pre_admission'));
        $this->assertSame(2, $this->compter('patient'));
    }

    public function testEnregistrementCompletAvecLiaisons(): void
    {
        $r = $this->enregistrer();
        $pa = $this->pdo->query('SELECT * FROM pre_admission WHERE num_pre_admission = ' . $r['num'])->fetch();
        $this->assertSame('Opération du genou', $pa['motif_hospitalisation']);
        $this->assertSame('2026-11-27', $pa['date_entree_prevue']);
        $this->assertSame('chez le medecin', $pa['statut']);
        $this->assertSame(1, (int) $pa['num_service']);
        $this->assertSame(1, (int) $pa['num_personnel_saisie']);

        $patient = $this->pdo->query('SELECT * FROM patient WHERE num_patient = ' . $pa['num_patient'])->fetch();
        $this->assertSame(['ROUX', 'Paul', '1978-12-05', 'H', 2, 'MG-555'],
            [$patient['nom'], $patient['prenom'], $patient['date_naissance'], $patient['sexe'], (int) $patient['num_mutuelle'], $patient['numero_adherent']]);

        $pc = $this->pdo->query('SELECT * FROM personne_confiance WHERE num_personne_confiance = ' . $pa['num_personne_confiance'])->fetch();
        $this->assertSame(['ROUX', 'Julie', 'épouse'], [$pc['nom_personne_confiance'], $pc['prenom_personne_confiance'], $pc['lien_avec_patient']]);

        $couvrir = $this->pdo->query('SELECT num_organisme FROM couvrir WHERE num_patient = ' . $pa['num_patient'])->fetchColumn();
        $this->assertSame(1, (int) $couvrir);
    }

    public function testPatientExistantMutuelleModifiee(): void
    {
        $r = $this->enregistrer(['num_secu' => '162035912204504', 'nom' => 'Autre', 'mutuelle' => '2', 'numero_adherent' => 'MG-1']);
        $this->assertTrue($r['ok']);
        $this->assertFalse($r['nouveau_patient']);
        $this->assertSame(1, $this->compter('patient'));
        $p = $this->pdo->query('SELECT nom, num_mutuelle, numero_adherent FROM patient WHERE num_patient = 1')->fetch();
        $this->assertSame(['GIRARD', 2, 'MG-1'], [$p['nom'], (int) $p['num_mutuelle'], $p['numero_adherent']], 'identité inchangée, mutuelle mise à jour');
        $this->assertSame(1, $this->compter('couvrir'), 'pas de doublon dans couvrir');
    }

    public function testChampsFacultatifsVides(): void
    {
        $r = $this->enregistrer(['email' => '', 'duree' => '', 'organisme' => '', 'mutuelle' => '', 'numero_adherent' => '', 'pc_lien' => '']);
        $this->assertTrue($r['ok']);
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM couvrir WHERE num_patient <> 1')->fetchColumn());
    }

    public function testRefusChaqueChampObligatoireManquant(): void
    {
        foreach (['num_secu', 'nom', 'prenom', 'date_naissance', 'adresse', 'ville', 'telephone', 'service',
                  'date_entree', 'motif', 'pc_nom', 'pc_prenom', 'pc_telephone'] as $champ) {
            $this->assertRefus([$champ => ''], $champ);
        }
    }

    public function testRefusFormatDate(): void
    {
        $this->assertRefus(['date_entree' => '31/02/2027'], 'date_entree');
        $this->assertRefus(['date_entree' => '2027-01-10'], 'date_entree');
        $this->assertRefus(['date_entree' => '01/01/2020'], 'date_entree');       // dans le passé
        $this->assertRefus(['date_naissance' => '01/01/2090'], 'date_naissance'); // dans le futur
    }

    public function testRefusFormatTelephone(): void
    {
        $this->assertRefus(['telephone' => '123'], 'telephone');
        $this->assertRefus(['pc_telephone' => 'abc'], 'pc_telephone');
    }

    public function testRefusFormatNumSecu(): void
    {
        $this->assertRefus(['num_secu' => '1 78 12 59 178 033 99'], 'num_secu');
    }

    public function testRefusAutresFormats(): void
    {
        $this->assertRefus(['email' => 'pas-un-email'], 'email');
        $this->assertRefus(['duree' => 'trois'], 'duree');
        $this->assertRefus(['nom' => 'R0ux'], 'nom');
        $this->assertRefus(['mutuelle' => '', 'numero_adherent' => 'X1'], 'numero_adherent');
    }

    public function testRefusServiceOuCouvertureInconnus(): void
    {
        $this->assertRefus(['service' => '99'], 'service');
        $this->assertRefus(['organisme' => '99'], 'organisme');
        $this->assertRefus(['mutuelle' => '99'], 'mutuelle');
    }

    public function testErreurBaseRienNestEnregistre(): void
    {
        $this->pdo->exec('DROP TABLE pre_admission'); // la dernière étape échoue
        $r = $this->enregistrer();
        $this->assertFalse($r['ok']);
        $this->assertArrayHasKey('global', $r['erreurs']);
        $this->assertSame(1, $this->compter('patient'), 'transaction annulée : le patient n\'est pas créé');
    }

    public function testRefusAccesPourChaqueAutreProfil(): void
    {
        foreach ([[2, Auth::MEDECIN], [3, Auth::ADMINISTRATIF], [4, Auth::INFORMATICIEN]] as [$num, $profil]) {
            try {
                $this->enregistrer([], BaseDeTest::utilisateur($num, $profil));
                $this->fail("$profil ne doit pas pouvoir enregistrer");
            } catch (AccesRefuseException $e) {
                $this->assertSame(0, $this->compter('pre_admission'));
            }
        }
    }

    public function testRefusAccesSansSession(): void
    {
        $this->expectException(AccesRefuseException::class);
        $this->service->enregistrer(null, $this->saisie(), $this->aujourdhui);
    }

    public function testRefusListeEtRecherchePourUnAutreProfil(): void
    {
        $medecin = BaseDeTest::utilisateur(2, Auth::MEDECIN);
        $this->expectException(AccesRefuseException::class);
        $this->service->lister($medecin);
    }

    public function testListePartageeEtFiltre(): void
    {
        $this->enregistrer();
        $this->service->enregistrer(BaseDeTest::utilisateur(5, Auth::SECRETAIRE),
            $this->saisie(['num_secu' => '162035912204504']), $this->aujourdhui);

        $this->assertCount(2, $this->service->lister($this->secretaire));
        $miennes = $this->service->lister($this->secretaire, true);
        $this->assertCount(1, $miennes);
        $this->assertSame('ROUX', $miennes[0]['p_nom']);
    }
}
