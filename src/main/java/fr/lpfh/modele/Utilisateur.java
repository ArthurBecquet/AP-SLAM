package fr.lpfh.modele;

/**
 * Utilisateur connecté (identité + profil). Ne contient JAMAIS le mot de passe ni son hash.
 */
public record Utilisateur(int numPersonnel, String nom, String prenom, Profil profil) {

    public String nomComplet() {
        return prenom + " " + nom;
    }
}
