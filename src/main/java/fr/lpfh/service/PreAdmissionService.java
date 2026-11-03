package fr.lpfh.service;

import fr.lpfh.dao.DaoException;
import fr.lpfh.dao.PreAdmissionDAO;
import fr.lpfh.modele.Patient;
import fr.lpfh.modele.PreAdmissionLigne;
import fr.lpfh.modele.PreAdmissionSaisie;
import fr.lpfh.modele.Profil;
import fr.lpfh.modele.Reference;
import fr.lpfh.modele.Utilisateur;

import java.time.LocalDate;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.Optional;

import static fr.lpfh.service.Validation.vide;

/**
 * Création d'une pré-admission par la secrétaire médicale :
 * contrôles de saisie puis enregistrement (patient si nouveau + personne de confiance + pré-admission).
 */
public class PreAdmissionService {

    private final PreAdmissionDAO dao;

    public PreAdmissionService(PreAdmissionDAO dao) {
        this.dao = dao;
    }

    public Optional<Patient> rechercherPatient(String numSecu) {
        AuthService.exiger(Profil.SECRETAIRE);
        return dao.trouverPatient(Validation.normaliserNumSecu(numSecu));
    }

    public List<Reference> services() {
        return dao.listerServices();
    }

    public List<Reference> mutuelles() {
        return dao.listerMutuelles();
    }

    public List<PreAdmissionLigne> lister(boolean seulementLesMiennes) {
        Utilisateur u = AuthService.exiger(Profil.SECRETAIRE);
        return dao.lister(seulementLesMiennes ? u.numPersonnel() : null);
    }

    /**
     * Contrôles de saisie.
     * @return champ → message d'erreur (vide si tout est correct)
     */
    public Map<String, String> valider(PreAdmissionSaisie s, boolean patientExistant, LocalDate aujourdhui) {
        Map<String, String> err = new LinkedHashMap<>();

        if (vide(s.numSecuriteSociale)) {
            err.put("numSecu", "N° de sécurité sociale : champ obligatoire.");
        } else if (!Validation.numSecuValide(s.numSecuriteSociale)) {
            err.put("numSecu", "N° de sécurité sociale invalide (15 chiffres, clé incorrecte).");
        }

        if (!patientExistant) {
            obligatoire(err, "nom", s.nom, "Nom");
            obligatoire(err, "prenom", s.prenom, "Prénom");
            obligatoire(err, "adresse", s.adresse, "Adresse");
            obligatoire(err, "ville", s.ville, "Ville");
            if (vide(s.dateNaissance)) {
                err.put("dateNaissance", "Date de naissance : champ obligatoire.");
            } else {
                LocalDate d = Validation.date(s.dateNaissance);
                if (d == null || d.isAfter(aujourdhui)) {
                    err.put("dateNaissance", "Date de naissance invalide (JJ/MM/AAAA).");
                }
            }
            if (vide(s.telephone)) {
                err.put("telephone", "Téléphone : champ obligatoire.");
            } else if (!Validation.telephoneValide(s.telephone)) {
                err.put("telephone", "Téléphone invalide (ex. 06 12 34 56 78).");
            }
            if (!vide(s.email) && !Validation.emailValide(s.email)) {
                err.put("email", "Email invalide.");
            }
        }

        if (s.numService == null) {
            err.put("service", "Service : champ obligatoire.");
        }
        if (vide(s.dateEntree)) {
            err.put("dateEntree", "Date d'entrée : champ obligatoire.");
        } else {
            LocalDate d = Validation.date(s.dateEntree);
            if (d == null) {
                err.put("dateEntree", "Date d'entrée invalide (JJ/MM/AAAA).");
            } else if (d.isBefore(aujourdhui)) {
                err.put("dateEntree", "La date d'entrée ne peut pas être dans le passé.");
            }
        }
        if (!vide(s.dureePrevue)) {
            try {
                int duree = Integer.parseInt(s.dureePrevue.trim());
                if (duree < 0 || duree > 365) {
                    err.put("duree", "Durée prévue invalide (0 à 365 jours).");
                }
            } catch (NumberFormatException e) {
                err.put("duree", "Durée prévue invalide (nombre de jours).");
            }
        }
        if (vide(s.motif)) {
            err.put("motif", "Motif : champ obligatoire.");
        } else if (s.motif.length() > 255) {
            err.put("motif", "Motif trop long (255 caractères max).");
        }

        obligatoire(err, "pcNom", s.pcNom, "Nom de la personne à prévenir");
        obligatoire(err, "pcPrenom", s.pcPrenom, "Prénom de la personne à prévenir");
        if (vide(s.pcTelephone)) {
            err.put("pcTelephone", "Téléphone de la personne à prévenir : champ obligatoire.");
        } else if (!Validation.telephoneValide(s.pcTelephone)) {
            err.put("pcTelephone", "Téléphone de la personne à prévenir invalide.");
        }
        return err;
    }

    private static void obligatoire(Map<String, String> err, String cle, String valeur, String libelle) {
        if (vide(valeur)) {
            err.put(cle, libelle + " : champ obligatoire.");
        }
    }

    /** Résultat d'un enregistrement. */
    public record Resultat(boolean ok, int numPreAdmission, boolean nouveauPatient, Map<String, String> erreurs) {
    }

    /** Contrôle d'accès + contrôles de saisie + enregistrement en base. */
    public Resultat enregistrer(PreAdmissionSaisie s) {
        Utilisateur secretaire = AuthService.exiger(Profil.SECRETAIRE);
        LocalDate aujourdhui = LocalDate.now();
        String numSecu = Validation.normaliserNumSecu(s.numSecuriteSociale);
        Optional<Patient> patient = vide(numSecu) ? Optional.empty() : dao.trouverPatient(numSecu);

        Map<String, String> erreurs = valider(s, patient.isPresent(), aujourdhui);
        if (!erreurs.isEmpty()) {
            return new Resultat(false, 0, false, erreurs);
        }

        Patient p = patient.orElse(null);
        PreAdmissionDAO.Enregistrement e = new PreAdmissionDAO.Enregistrement(
                p == null ? null : p.numPatient(),
                numSecu,
                p == null ? s.nom.trim().toUpperCase() : p.nom(),
                p == null ? s.prenom.trim() : p.prenom(),
                p == null ? Validation.date(s.dateNaissance) : p.dateNaissance(),
                p == null ? s.adresse.trim() : p.adresse(),
                p == null ? s.ville.trim() : p.ville(),
                p == null ? s.telephone.trim() : p.telephone(),
                p == null ? s.email.trim() : p.email(),
                s.numMutuelle, s.numeroAdherent == null ? null : s.numeroAdherent.trim(),
                s.numService, Validation.date(s.dateEntree),
                vide(s.dureePrevue) ? null : Integer.parseInt(s.dureePrevue.trim()), s.motif.trim(),
                s.pcNom.trim().toUpperCase(), s.pcPrenom.trim(), s.pcTelephone.trim(), s.pcLien == null ? null : s.pcLien.trim(),
                secretaire.numPersonnel());
        try {
            int num = dao.enregistrer(e);
            return new Resultat(true, num, p == null, Map.of());
        } catch (DaoException ex) {
            return new Resultat(false, 0, false,
                    Map.of("global", "Erreur lors de l'enregistrement. Aucune donnée n'a été enregistrée."));
        }
    }
}
