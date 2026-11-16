package fr.lpfh.service;

import fr.lpfh.modele.Utilisateur;

import java.util.Optional;

/**
 * Session de l'application : l'utilisateur connecté (identité + profil).
 */
public final class Session {

    private static Utilisateur utilisateur;

    private Session() {
    }

    public static void ouvrir(Utilisateur u) {
        utilisateur = u;
    }

    public static Optional<Utilisateur> utilisateur() {
        return Optional.ofNullable(utilisateur);
    }

    /** Déconnexion : la session est vidée. */
    public static void fermer() {
        utilisateur = null;
    }
}
