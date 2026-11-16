package fr.lpfh.dao;

import fr.lpfh.modele.Patient;
import fr.lpfh.modele.PreAdmissionLigne;
import fr.lpfh.modele.Reference;

import java.time.LocalDate;
import java.util.List;
import java.util.Optional;

/**
 * Accès aux données de la pré-admission (patient, personne de confiance, pré-admission).
 */
public interface PreAdmissionDAO {

    Optional<Patient> trouverPatient(String numSecuNormalise);

    List<Reference> listerServices();

    List<Reference> listerMutuelles();

    /** Caisses d'assurance maladie (table organisme). */
    List<Reference> listerOrganismes();

    /** Toutes les pré-admissions (partagées entre secrétaires), ou celles créées par une secrétaire si creeesPar != null. */
    List<PreAdmissionLigne> lister(Integer creeesPar);

    /**
     * Enregistre en une seule transaction : patient (création, ou mise à jour de la mutuelle si déjà inscrit)
     * + couverture (caisse d'assurance maladie) + personne de confiance + pré-admission.
     * Renvoie le n° de la pré-admission.
     */
    int enregistrer(Enregistrement e);

    /** Données déjà contrôlées et converties, prêtes à être insérées. */
    record Enregistrement(
            Integer numPatientExistant,
            String numSecu, String nom, String prenom, String sexe, LocalDate dateNaissance,
            String adresse, String ville, String telephone, String email,
            Integer numOrganisme, Integer numMutuelle, String numeroAdherent,
            int numService, LocalDate dateEntree, Integer dureePrevue, String motif,
            String pcNom, String pcPrenom, String pcTelephone, String pcLien,
            int numSecretaire) {
    }
}
