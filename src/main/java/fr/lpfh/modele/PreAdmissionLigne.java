package fr.lpfh.modele;

import java.time.LocalDate;

/**
 * Ligne de la liste des pré-admissions (écran SEC-01).
 */
public record PreAdmissionLigne(int numPreAdmission, String patient, int numSecretaire, String secretaire,
                                String service, LocalDate dateEntree, String statut) {
}
