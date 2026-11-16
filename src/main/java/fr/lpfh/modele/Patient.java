package fr.lpfh.modele;

import java.time.LocalDate;

/**
 * Patient tel qu'enregistré en base.
 */
public record Patient(int numPatient, String numSecuriteSociale, String nom, String prenom,
                      LocalDate dateNaissance, String adresse, String ville, String telephone, String email,
                      Integer numMutuelle, String numeroAdherent,
                      Integer numOrganisme /* caisse d'assurance maladie (table couvrir), null si aucune */) {
}
