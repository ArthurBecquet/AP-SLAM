package fr.lpfh.modele;

/**
 * Élément simple d'une liste déroulante (service, mutuelle…).
 */
public record Reference(int id, String libelle) {

    @Override
    public String toString() {
        return libelle; // affiché dans les JComboBox
    }
}
