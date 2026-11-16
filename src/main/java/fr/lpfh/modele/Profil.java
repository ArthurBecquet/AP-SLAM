package fr.lpfh.modele;

import java.util.Optional;

/**
 * Les 4 profils de l'application (libellés identiques à la table profil).
 */
public enum Profil {
    SECRETAIRE("Secrétaire médicale"),
    MEDECIN("Médecin"),
    ADMINISTRATIF("Département administratif"),
    INFORMATICIEN("Informaticien");

    private final String libelle;

    Profil(String libelle) {
        this.libelle = libelle;
    }

    public String getLibelle() {
        return libelle;
    }

    public static Optional<Profil> depuisLibelle(String libelle) {
        for (Profil p : values()) {
            if (p.libelle.equals(libelle)) {
                return Optional.of(p);
            }
        }
        return Optional.empty();
    }
}
