package fr.lpfh;

import fr.lpfh.dao.PreAdmissionDAO;
import fr.lpfh.modele.PreAdmissionSaisie;
import fr.lpfh.modele.Utilisateur;
import fr.lpfh.service.AccesRefuseException;
import fr.lpfh.service.AuthService;
import fr.lpfh.service.PreAdmissionService;
import fr.lpfh.service.Session;
import fr.lpfh.service.Validation;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;

import java.time.LocalDate;

import static org.junit.jupiter.api.Assertions.*;

/**
 * Premiers tests de la création d'une pré-admission (seront complétés en Séance 8).
 */
public class PreAdmissionServiceTest {

    private FakePreAdmissionDAO dao;
    private PreAdmissionService service;
    private AuthService auth;

    @BeforeEach
    void setUp() {
        dao = new FakePreAdmissionDAO();
        service = new PreAdmissionService(dao);
        auth = new AuthService(new FakeUtilisateurDAO());
        Utilisateur secretaire = auth.connecter("secretaire.test", FakeUtilisateurDAO.MDP).orElseThrow();
        Session.ouvrir(secretaire);
    }

    @AfterEach
    void tearDown() {
        Session.fermer();
    }

    private PreAdmissionSaisie saisieValide() {
        PreAdmissionSaisie s = new PreAdmissionSaisie();
        s.numSecuriteSociale = "1 78 12 59 178 033 24";
        s.nom = "roux";
        s.prenom = "Paul";
        s.dateNaissance = "05/12/1978";
        s.adresse = "8 rue Victor Hugo";
        s.ville = "59400 Cambrai";
        s.telephone = "06 11 22 33 44";
        s.numService = 1;
        s.dateEntree = LocalDate.now().plusDays(10).format(Validation.FORMAT_DATE);
        s.dureePrevue = "3";
        s.motif = "Opération du genou";
        s.numMutuelle = 2;
        s.numeroAdherent = "MG-555";
        s.pcNom = "Roux";
        s.pcPrenom = "Julie";
        s.pcTelephone = "06 99 88 77 66";
        s.pcLien = "épouse";
        return s;
    }

    @Test
    void testNumSecuValide() {
        assertTrue(Validation.numSecuValide("1 62 03 59 122 045 04"));
        assertFalse(Validation.numSecuValide("1 62 03 59 122 045 78"), "Clé incorrecte");
        assertFalse(Validation.numSecuValide("12345"));
    }

    @Test
    void testRecherchePatientParNumSecu() {
        assertTrue(service.rechercherPatient("1 62 03 59 122 045 04").isPresent());
        assertTrue(service.rechercherPatient("178125917803324").isEmpty());
    }

    @Test
    void testEnregistrementNouveauPatient() {
        PreAdmissionService.Resultat r = service.enregistrer(saisieValide());
        assertTrue(r.ok(), "Erreurs : " + r.erreurs());
        assertTrue(r.nouveauPatient());
        PreAdmissionDAO.Enregistrement e = dao.enregistrements.get(0);
        assertNull(e.numPatientExistant());
        assertEquals("ROUX", e.nom());
        assertEquals("ROUX", e.pcNom());
        assertEquals(1, e.numSecretaire(), "La pré-admission est rattachée à la secrétaire connectée");
    }

    @Test
    void testPatientExistantMutuelleModifiee() {
        PreAdmissionSaisie s = saisieValide();
        s.numSecuriteSociale = "162035912204504";
        s.nom = s.prenom = s.dateNaissance = s.adresse = s.ville = s.telephone = "";
        s.numMutuelle = 2;
        s.numeroAdherent = "MG-999";
        PreAdmissionService.Resultat r = service.enregistrer(s);
        assertTrue(r.ok(), "Erreurs : " + r.erreurs());
        assertFalse(r.nouveauPatient());
        PreAdmissionDAO.Enregistrement e = dao.enregistrements.get(0);
        assertEquals(1, e.numPatientExistant());
        assertEquals(2, e.numMutuelle());
        assertEquals("GIRARD", e.nom(), "Les infos du patient existant sont reprises de la base");
    }

    @Test
    void testRefusChampObligatoireManquant() {
        PreAdmissionSaisie s = saisieValide();
        s.pcTelephone = "";
        PreAdmissionService.Resultat r = service.enregistrer(s);
        assertFalse(r.ok());
        assertTrue(r.erreurs().containsKey("pcTelephone"));
        assertTrue(dao.enregistrements.isEmpty(), "Rien ne doit être enregistré");
    }

    @Test
    void testRefusFormatsInvalides() {
        PreAdmissionSaisie s = saisieValide();
        s.numSecuriteSociale = "1 78 12 59 178 033 99";
        s.telephone = "123";
        s.dateEntree = "31/02/2026";
        PreAdmissionService.Resultat r = service.enregistrer(s);
        assertFalse(r.ok());
        assertTrue(r.erreurs().containsKey("numSecu"));
        assertTrue(r.erreurs().containsKey("telephone"));
        assertTrue(r.erreurs().containsKey("dateEntree"));
        assertTrue(dao.enregistrements.isEmpty(), "Rien ne doit être enregistré");
    }

    // ---------------------------------------------------------------
    //  Séance 8 — enregistrement complet
    // ---------------------------------------------------------------

    /** Raccourci : la saisie doit être refusée, avec une erreur sur le champ donné, et rien n'est enregistré. */
    private void assertRefus(PreAdmissionSaisie s, String champEnErreur) {
        PreAdmissionService.Resultat r = service.enregistrer(s);
        assertFalse(r.ok(), "La saisie aurait dû être refusée (champ " + champEnErreur + ")");
        assertTrue(r.erreurs().containsKey(champEnErreur), "Erreur attendue sur " + champEnErreur + " : " + r.erreurs());
        assertTrue(dao.enregistrements.isEmpty(), "Rien ne doit être enregistré");
    }

    @Test
    void testEnregistrementCompletAvecLiaisons() {
        PreAdmissionSaisie s = saisieValide();
        s.numOrganisme = 1;
        s.email = "paul.roux@exemple.test";
        PreAdmissionService.Resultat r = service.enregistrer(s);
        assertTrue(r.ok(), "Erreurs : " + r.erreurs());
        assertEquals(1, r.numPreAdmission());
        PreAdmissionDAO.Enregistrement e = dao.enregistrements.get(0);
        assertEquals("178125917803324", e.numSecu(), "Le n° de sécu est enregistré sans espaces");
        assertEquals("H", e.sexe(), "Sexe déduit du n° de sécu (1 = homme)");
        assertEquals(LocalDate.of(1978, 12, 5), e.dateNaissance());
        assertEquals(1, e.numService(), "Liaison vers le service");
        assertEquals(1, e.numOrganisme(), "Liaison vers la caisse d'assurance maladie (couverture)");
        assertEquals(2, e.numMutuelle(), "Liaison vers la mutuelle");
        assertEquals("MG-555", e.numeroAdherent());
        assertEquals(3, e.dureePrevue());
        assertEquals("Opération du genou", e.motif());
        assertEquals("épouse", e.pcLien());
    }

    @Test
    void testChampsFacultatifsVides() {
        PreAdmissionSaisie s = saisieValide();
        s.email = "";
        s.dureePrevue = "";
        s.numOrganisme = null;
        s.numMutuelle = null;
        s.numeroAdherent = "";
        s.pcLien = "";
        PreAdmissionService.Resultat r = service.enregistrer(s);
        assertTrue(r.ok(), "Les champs facultatifs peuvent rester vides : " + r.erreurs());
        assertNull(dao.enregistrements.get(0).dureePrevue());
        assertNull(dao.enregistrements.get(0).numOrganisme());
    }

    @Test
    void testRefusChaqueChampObligatoireManquant() {
        PreAdmissionSaisie s;
        s = saisieValide(); s.numSecuriteSociale = ""; assertRefus(s, "numSecu");
        s = saisieValide(); s.nom = "";               assertRefus(s, "nom");
        s = saisieValide(); s.prenom = "  ";          assertRefus(s, "prenom");
        s = saisieValide(); s.dateNaissance = "";     assertRefus(s, "dateNaissance");
        s = saisieValide(); s.adresse = "";           assertRefus(s, "adresse");
        s = saisieValide(); s.ville = "";             assertRefus(s, "ville");
        s = saisieValide(); s.telephone = "";         assertRefus(s, "telephone");
        s = saisieValide(); s.numService = null;      assertRefus(s, "service");
        s = saisieValide(); s.dateEntree = "";        assertRefus(s, "dateEntree");
        s = saisieValide(); s.motif = "";             assertRefus(s, "motif");
        s = saisieValide(); s.pcNom = "";             assertRefus(s, "pcNom");
        s = saisieValide(); s.pcPrenom = "";          assertRefus(s, "pcPrenom");
        s = saisieValide(); s.pcTelephone = "";       assertRefus(s, "pcTelephone");
    }

    @Test
    void testRefusFormatDate() {
        PreAdmissionSaisie s;
        s = saisieValide(); s.dateNaissance = "1978-12-05"; assertRefus(s, "dateNaissance");
        s = saisieValide(); s.dateNaissance = "31/02/1978"; assertRefus(s, "dateNaissance");
        s = saisieValide(); s.dateNaissance = LocalDate.now().plusDays(1).format(Validation.FORMAT_DATE);
        assertRefus(s, "dateNaissance");
        s = saisieValide(); s.dateEntree = "demain";        assertRefus(s, "dateEntree");
        s = saisieValide(); s.dateEntree = LocalDate.now().minusDays(1).format(Validation.FORMAT_DATE);
        assertRefus(s, "dateEntree");
    }

    @Test
    void testRefusFormatTelephone() {
        PreAdmissionSaisie s;
        s = saisieValide(); s.telephone = "06 11 22 33";      assertRefus(s, "telephone");
        s = saisieValide(); s.telephone = "abcdefghij";       assertRefus(s, "telephone");
        s = saisieValide(); s.pcTelephone = "0011223344";     assertRefus(s, "pcTelephone");
        // Formats acceptés
        assertTrue(Validation.telephoneValide("06.11.22.33.44"));
        assertTrue(Validation.telephoneValide("+33 6 11 22 33 44"));
    }

    @Test
    void testRefusFormatNumSecu() {
        PreAdmissionSaisie s;
        s = saisieValide(); s.numSecuriteSociale = "1 78 12 59 178 033";     assertRefus(s, "numSecu"); // trop court
        s = saisieValide(); s.numSecuriteSociale = "3 78 12 59 178 033 24";  assertRefus(s, "numSecu"); // ne commence pas par 1 ou 2
        s = saisieValide(); s.numSecuriteSociale = "1 78 12 59 178 033 25";  assertRefus(s, "numSecu"); // clé fausse
    }

    @Test
    void testRefusAutresFormats() {
        PreAdmissionSaisie s;
        s = saisieValide(); s.email = "paul.roux";            assertRefus(s, "email");
        s = saisieValide(); s.dureePrevue = "trois";          assertRefus(s, "duree");
        s = saisieValide(); s.dureePrevue = "-2";             assertRefus(s, "duree");
        s = saisieValide(); s.nom = "Roux123";                assertRefus(s, "nom");
        s = saisieValide(); s.motif = "x".repeat(256);        assertRefus(s, "motif");
        s = saisieValide(); s.numMutuelle = null;             assertRefus(s, "numeroAdherent"); // n° d'adhérent sans mutuelle
    }

    @Test
    void testRefusServiceOuCouvertureInconnus() {
        PreAdmissionSaisie s;
        s = saisieValide(); s.numService = 999;   assertRefus(s, "service");
        s = saisieValide(); s.numOrganisme = 999; assertRefus(s, "organisme");
        s = saisieValide(); s.numMutuelle = 999;  assertRefus(s, "mutuelle");
    }

    @Test
    void testErreurBaseMessageClair() {
        dao.panne = true;
        PreAdmissionService.Resultat r = service.enregistrer(saisieValide());
        assertFalse(r.ok());
        assertTrue(r.erreurs().containsKey("global"), "Un message d'erreur clair est renvoyé à l'écran");
    }

    @Test
    void testRefusAccesPourChaqueAutreProfil() {
        for (String compte : new String[]{"medecin.test", "admin.test", "informaticien.test"}) {
            Session.ouvrir(auth.connecter(compte, FakeUtilisateurDAO.MDP).orElseThrow());
            assertThrows(AccesRefuseException.class, () -> service.enregistrer(saisieValide()), compte + " : enregistrement");
            assertThrows(AccesRefuseException.class, () -> service.rechercherPatient("162035912204504"), compte + " : recherche");
            assertThrows(AccesRefuseException.class, () -> service.lister(false), compte + " : liste");
            assertThrows(AccesRefuseException.class, () -> service.services(), compte + " : écran (listes)");
        }
        assertTrue(dao.enregistrements.isEmpty(), "Rien ne doit être enregistré");
    }

    @Test
    void testListePartageeEtFiltre() {
        service.enregistrer(saisieValide());
        assertEquals(1, service.lister(false).size());
        assertEquals(1, service.lister(true).size(), "Créée par la secrétaire connectée");
    }

    @Test
    void testRefusAccesAutreProfil() {
        Session.ouvrir(auth.connecter("medecin.test", FakeUtilisateurDAO.MDP).orElseThrow());
        assertThrows(AccesRefuseException.class, () -> service.enregistrer(saisieValide()),
                "Seule la secrétaire médicale peut créer une pré-admission");
        assertTrue(dao.enregistrements.isEmpty());
    }

    @Test
    void testRefusAccesSansSession() {
        Session.fermer();
        assertThrows(AccesRefuseException.class, () -> service.enregistrer(saisieValide()));
    }
}
