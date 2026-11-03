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
        assertTrue(r.erreurs().containsKey("dateEntree"));
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
