package fr.lpfh.service;

import fr.lpfh.dao.UtilisateurDAO;
import fr.lpfh.modele.CompteConnexion;
import fr.lpfh.modele.Profil;
import fr.lpfh.modele.Utilisateur;

import java.time.LocalDate;
import java.util.Arrays;
import java.util.Optional;

/**
 * Authentification par profil + contrôle d'accès.
 */
public class AuthService {

    /** Message générique : on ne dit jamais si c'est l'identifiant ou le mot de passe qui est faux. */
    public static final String MESSAGE_ECHEC = "Identifiant ou mot de passe incorrect.";

    // Hash factice : garde un temps de réponse identique quand l'identifiant n'existe pas.
    private static final String HASH_FACTICE = "$2y$10$bFQG3AkWv/VhYSaNMxx5W.oqEu10yZ9GSNRbHOq3kgWhsO3K0Vuh2";

    private final UtilisateurDAO dao;

    public AuthService(UtilisateurDAO dao) {
        this.dao = dao;
    }

    /** Connexion : renvoie l'utilisateur (sans hash) si tout est valide, sinon vide. */
    public Optional<Utilisateur> connecter(String identifiant, String motDePasse) {
        return connecter(identifiant, motDePasse, LocalDate.now());
    }

    public Optional<Utilisateur> connecter(String identifiant, String motDePasse, LocalDate jour) {
        if (identifiant == null || identifiant.isBlank() || motDePasse == null || motDePasse.isEmpty()) {
            return Optional.empty();
        }
        Optional<CompteConnexion> compte = dao.trouverParIdentifiant(identifiant.trim(), jour);
        if (compte.isEmpty()) {
            MotDePasse.verifier(motDePasse, HASH_FACTICE);
            return Optional.empty();
        }
        CompteConnexion c = compte.get();
        if (!MotDePasse.verifier(motDePasse, c.motDePasseHash()) || !"actif".equals(c.statutCompte())) {
            return Optional.empty();
        }
        return Profil.depuisLibelle(c.libelleProfil())
                .map(profil -> new Utilisateur(c.numPersonnel(), c.nom(), c.prenom(), profil));
    }

    /** Vrai si l'utilisateur est connecté ET a un des profils autorisés. */
    public static boolean peutAcceder(Utilisateur u, Profil... profilsAutorises) {
        return u != null && u.profil() != null && Arrays.asList(profilsAutorises).contains(u.profil());
    }

    /**
     * À appeler avant chaque écran / action protégée : contrôle fait par le code, pas seulement en cachant des boutons.
     * @return l'utilisateur connecté
     */
    public static Utilisateur exiger(Profil... profilsAutorises) {
        Utilisateur u = Session.utilisateur().orElseThrow(() -> new AccesRefuseException(true));
        if (!peutAcceder(u, profilsAutorises)) {
            throw new AccesRefuseException(false);
        }
        return u;
    }

    public void tracer(Utilisateur u, String action) {
        dao.tracer(u.numPersonnel(), action, "application-bureau", ipLocale());
    }

    private static String ipLocale() {
        try {
            return java.net.InetAddress.getLocalHost().getHostAddress();
        } catch (Exception e) {
            return "inconnue";
        }
    }
}
