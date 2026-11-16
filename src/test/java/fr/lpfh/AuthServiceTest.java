package fr.lpfh;

import fr.lpfh.modele.Profil;
import fr.lpfh.modele.Utilisateur;
import fr.lpfh.service.AccesRefuseException;
import fr.lpfh.service.AuthService;
import fr.lpfh.service.MotDePasse;
import fr.lpfh.service.Session;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;

import java.time.LocalDate;
import java.util.Optional;

import static org.junit.jupiter.api.Assertions.*;

/**
 * Tests unitaires de l'authentification par profil (Séance 7).
 */
public class AuthServiceTest {

    private FakeUtilisateurDAO dao;
    private AuthService auth;

    @BeforeEach
    void setUp() {
        dao = new FakeUtilisateurDAO();
        auth = new AuthService(dao);
        Session.fermer();
    }

    @AfterEach
    void tearDown() {
        Session.fermer();
    }

    private void verifierConnexion(String identifiant, Profil attendu) {
        Optional<Utilisateur> u = auth.connecter(identifiant, FakeUtilisateurDAO.MDP);
        assertTrue(u.isPresent(), "La connexion de " + identifiant + " doit réussir");
        assertEquals(attendu, u.get().profil(), "Le profil doit être " + attendu.getLibelle());
    }

    @Test
    void testConnexionSecretaire() {
        verifierConnexion("secretaire.test", Profil.SECRETAIRE);
    }

    @Test
    void testConnexionMedecin() {
        verifierConnexion("medecin.test", Profil.MEDECIN);
    }

    @Test
    void testConnexionAdministratif() {
        verifierConnexion("admin.test", Profil.ADMINISTRATIF);
    }

    @Test
    void testConnexionInformaticien() {
        verifierConnexion("informaticien.test", Profil.INFORMATICIEN);
    }

    @Test
    void testRefusMauvaisMotDePasse() {
        assertTrue(auth.connecter("secretaire.test", "mauvais").isEmpty(), "Un mauvais mot de passe doit être refusé");
    }

    @Test
    void testRefusIdentifiantInconnu() {
        assertTrue(auth.connecter("inconnu", FakeUtilisateurDAO.MDP).isEmpty());
    }

    @Test
    void testRefusChampsVides() {
        assertTrue(auth.connecter("", "").isEmpty());
        assertTrue(auth.connecter("secretaire.test", "").isEmpty());
        assertTrue(auth.connecter(null, null).isEmpty());
    }

    @Test
    void testRefusTentativeInjectionSql() {
        assertTrue(auth.connecter("' OR '1'='1", "' OR '1'='1").isEmpty());
    }

    @Test
    void testRefusCompteDesactive() {
        assertTrue(auth.connecter("desactive.test", FakeUtilisateurDAO.MDP).isEmpty());
    }

    @Test
    void testRefusHabilitationExpiree() {
        assertTrue(auth.connecter("expire.test", FakeUtilisateurDAO.MDP, LocalDate.of(2026, 11, 3)).isEmpty());
    }

    @Test
    void testMessageEchecGenerique() {
        assertEquals("Identifiant ou mot de passe incorrect.", AuthService.MESSAGE_ECHEC,
                "Le message ne doit pas dire quel champ est faux");
    }

    @Test
    void testAccesRefuseSansSession() {
        AccesRefuseException e = assertThrows(AccesRefuseException.class, () -> AuthService.exiger(Profil.SECRETAIRE));
        assertTrue(e.isSansSession());
    }

    @Test
    void testAccesRefuseMauvaisProfil() {
        Session.ouvrir(auth.connecter("medecin.test", FakeUtilisateurDAO.MDP).orElseThrow());
        AccesRefuseException e = assertThrows(AccesRefuseException.class, () -> AuthService.exiger(Profil.SECRETAIRE),
                "Un médecin ne doit pas accéder aux écrans secrétaire");
        assertFalse(e.isSansSession());
    }

    @Test
    void testAccesAutoriseBonProfil() {
        Session.ouvrir(auth.connecter("secretaire.test", FakeUtilisateurDAO.MDP).orElseThrow());
        assertDoesNotThrow(() -> AuthService.exiger(Profil.SECRETAIRE));
    }

    @Test
    void testDeconnexionVideLaSession() {
        Session.ouvrir(auth.connecter("secretaire.test", FakeUtilisateurDAO.MDP).orElseThrow());
        Session.fermer();
        assertTrue(Session.utilisateur().isEmpty());
        assertThrows(AccesRefuseException.class, () -> AuthService.exiger(Profil.SECRETAIRE));
    }

    @Test
    void testConnexionTracee() {
        Utilisateur u = auth.connecter("secretaire.test", FakeUtilisateurDAO.MDP).orElseThrow();
        auth.tracer(u, "CONNEXION");
        assertEquals(1, dao.journal.size());
        assertEquals("1:CONNEXION", dao.journal.get(0));
    }

    @Test
    void testHachageMotDePasse() {
        String hash = MotDePasse.hacher("Secret123!");
        assertNotEquals("Secret123!", hash, "Le mot de passe ne doit jamais être stocké en clair");
        assertTrue(MotDePasse.verifier("Secret123!", hash));
        assertFalse(MotDePasse.verifier("autre", hash));
    }
}
