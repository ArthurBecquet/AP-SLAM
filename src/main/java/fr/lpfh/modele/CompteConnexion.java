package fr.lpfh.modele;

/**
 * Ligne lue en base pour vérifier une connexion (usage interne au service d'authentification).
 */
public record CompteConnexion(int numPersonnel, String nom, String prenom,
                              String motDePasseHash, String statutCompte, String libelleProfil) {
}
