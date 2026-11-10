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
 * contrôle d'accès, contrôles de saisie, puis enregistrement
 * (patient si nouveau + couverture + personne de confiance + pré-admission).
 * Toutes les méthodes sont réservées au profil secrétaire médicale.
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
        AuthService.exiger(Profil.SECRETAIRE);
        return dao.listerServices();
    }

    public List<Reference> mutuelles() {
        AuthService.exiger(Profil.SECRETAIRE);
        return dao.listerMutuelles();
    }

    /** Caisses d'assurance maladie proposées dans le formulaire. */
    public List<Reference> organismes() {
        AuthService.exiger(Profil.SECRETAIRE);
        return dao.listerOrganismes();
    }

    public List<PreAdmissionLigne> lister(boolean seulementLesMiennes) {
        Utilisateur u = AuthService.exiger(Profil.SECRETAIRE);
        return dao.lister(seulementLesMiennes ? u.numPersonnel() : null);
    }

    /**
     * Contrôles de saisie (refaits ici, même si l'écran a déjà vérifié).
     * @return champ → message d'erreur (vide si tout est correct)
     */
    public Map<String, String> valider(PreAdmissionSaisie s, boolean patientExistant, LocalDate aujourdhui) {
        Map<String, String> err = new LinkedHashMap<>();

        // --- N° de sécurité sociale ---
        if (vide(s.numSecuriteSociale)) {
            err.put("numSecu", "N° de sécurité sociale : champ obligatoire.");
        } else if (!Validation.numSecuValide(s.numSecuriteSociale)) {
            err.put("numSecu", "N° de sécurité sociale invalide (15 chiffres, clé incorrecte).");
        }

        // --- Identité et coordonnées (seulement si le patient n'est pas déjà inscrit) ---
        if (!patientExistant) {
            nom(err, "nom", s.nom, "Nom");
            nom(err, "prenom", s.prenom, "Prénom");
            if (vide(s.dateNaissance)) {
                err.put("dateNaissance", "Date de naissance : champ obligatoire.");
            } else {
                LocalDate d = Validation.date(s.dateNaissance);
                if (d == null) {
                    err.put("dateNaissance", "Date de naissance invalide (JJ/MM/AAAA).");
                } else if (d.isAfter(aujourdhui)) {
                    err.put("dateNaissance", "La date de naissance ne peut pas être dans le futur.");
                }
            }
            texte(err, "adresse", s.adresse, "Adresse", 150);
            texte(err, "ville", s.ville, "Ville", 80);
            if (vide(s.telephone)) {
                err.put("telephone", "Téléphone : champ obligatoire.");
            } else if (!Validation.telephoneValide(s.telephone)) {
                err.put("telephone", "Téléphone invalide (ex. 06 12 34 56 78).");
            }
            if (!vide(s.email)) {
                if (!Validation.emailValide(s.email.trim())) {
                    err.put("email", "Email invalide (ex. nom@exemple.fr).");
                } else if (s.email.trim().length() > 100) {
                    err.put("email", "Email trop long (100 caractères max).");
                }
            }
        }

        // --- Hospitalisation ---
        if (s.numService == null) {
            err.put("service", "Service : champ obligatoire.");
        } else if (!existe(dao.listerServices(), s.numService)) {
            err.put("service", "Service inconnu.");
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
        texte(err, "motif", s.motif, "Motif", 255);

        // --- Assurance maladie et mutuelle (facultatives, mais doivent exister dans les listes) ---
        if (s.numOrganisme != null && !existe(dao.listerOrganismes(), s.numOrganisme)) {
            err.put("organisme", "Caisse d'assurance maladie inconnue.");
        }
        if (s.numMutuelle != null && !existe(dao.listerMutuelles(), s.numMutuelle)) {
            err.put("mutuelle", "Mutuelle inconnue.");
        }
        if (!vide(s.numeroAdherent)) {
            if (s.numMutuelle == null) {
                err.put("numeroAdherent", "N° d'adhérent saisi sans mutuelle : choisissez la mutuelle.");
            } else if (s.numeroAdherent.trim().length() > 30) {
                err.put("numeroAdherent", "N° d'adhérent trop long (30 caractères max).");
            }
        }

        // --- Personne à prévenir ---
        nom(err, "pcNom", s.pcNom, "Nom de la personne à prévenir");
        nom(err, "pcPrenom", s.pcPrenom, "Prénom de la personne à prévenir");
        if (vide(s.pcTelephone)) {
            err.put("pcTelephone", "Téléphone de la personne à prévenir : champ obligatoire.");
        } else if (!Validation.telephoneValide(s.pcTelephone)) {
            err.put("pcTelephone", "Téléphone de la personne à prévenir invalide (ex. 06 12 34 56 78).");
        }
        if (!vide(s.pcLien) && s.pcLien.trim().length() > 50) {
            err.put("pcLien", "Lien avec le patient trop long (50 caractères max).");
        }
        return err;
    }

    /** Texte obligatoire avec une longueur maximale (celle de la colonne en base). */
    private static void texte(Map<String, String> err, String cle, String valeur, String libelle, int max) {
        if (vide(valeur)) {
            err.put(cle, libelle + " : champ obligatoire.");
        } else if (valeur.trim().length() > max) {
            err.put(cle, libelle + " trop long (" + max + " caractères max).");
        }
    }

    /** Nom ou prénom obligatoire : 50 caractères max, lettres / espaces / tirets / apostrophes. */
    private static void nom(Map<String, String> err, String cle, String valeur, String libelle) {
        texte(err, cle, valeur, libelle, 50);
        if (!err.containsKey(cle) && !Validation.nomValide(valeur)) {
            err.put(cle, libelle + " invalide (lettres, espaces et tirets uniquement).");
        }
    }

    private static boolean existe(List<Reference> liste, int id) {
        return liste.stream().anyMatch(r -> r.id() == id);
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
                Validation.sexeDepuisNumSecu(numSecu),
                p == null ? Validation.date(s.dateNaissance) : p.dateNaissance(),
                p == null ? s.adresse.trim() : p.adresse(),
                p == null ? s.ville.trim() : p.ville(),
                p == null ? s.telephone.trim() : p.telephone(),
                p == null ? (s.email == null ? null : s.email.trim()) : p.email(),
                s.numOrganisme,
                s.numMutuelle,
                s.numMutuelle == null || s.numeroAdherent == null ? null : s.numeroAdherent.trim(),
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
