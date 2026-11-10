package fr.lpfh.controleur;

import fr.lpfh.dao.DaoException;
import fr.lpfh.modele.Patient;
import fr.lpfh.modele.Profil;
import fr.lpfh.service.AuthService;
import fr.lpfh.service.PreAdmissionService;
import fr.lpfh.service.Validation;
import fr.lpfh.vue.ListePreAdmissionsPanel;
import fr.lpfh.vue.NouvellePreAdmissionPanel;

import javax.swing.JOptionPane;
import java.util.Map;
import java.util.Optional;

/**
 * Écrans de la secrétaire médicale : liste partagée et nouvelle pré-admission.
 */
public class PreAdmissionControleur {

    private final PreAdmissionService service;
    private final PrincipalControleur principal;

    public PreAdmissionControleur(PreAdmissionService service, PrincipalControleur principal) {
        this.service = service;
        this.principal = principal;
    }

    public void ouvrirListe(String messageSucces) {
        AuthService.exiger(Profil.SECRETAIRE);
        ListePreAdmissionsPanel vue = new ListePreAdmissionsPanel();
        Runnable charger = () -> vue.afficher(service.lister(vue.seulementLesMiennes()), principal.getUtilisateur().numPersonnel());
        charger.run();
        if (messageSucces != null) {
            vue.afficherSucces(messageSucces);
        }
        vue.getFiltre().addActionListener(e -> principal.ouvrir(charger));
        vue.getNouvelle().addActionListener(e -> principal.ouvrir(this::ouvrirNouvelle));
        principal.getFenetre().afficher("Pré-admissions", vue);
    }

    public void ouvrirNouvelle() {
        AuthService.exiger(Profil.SECRETAIRE);
        NouvellePreAdmissionPanel vue = new NouvellePreAdmissionPanel();
        vue.setListes(service.services(), service.organismes(), service.mutuelles());

        vue.getRechercher().addActionListener(e -> principal.ouvrir(() -> {
            if (!Validation.numSecuValide(vue.getNumSecu())) {
                vue.afficherErreurs(Map.of("numSecu", "N° de sécurité sociale invalide (15 chiffres, clé incorrecte)."));
                return;
            }
            vue.afficherErreurs(Map.of());
            Optional<Patient> p = service.rechercherPatient(vue.getNumSecu());
            vue.modePatient(p.orElse(null), true);
        }));

        vue.getAnnuler().addActionListener(e -> principal.ouvrir(() -> ouvrirListe(null)));

        vue.getEnregistrer().addActionListener(e -> principal.ouvrir(() -> {
            try {
                PreAdmissionService.Resultat r = service.enregistrer(vue.getSaisie());
                if (r.ok()) {
                    ouvrirListe("Pré-admission n° " + r.numPreAdmission() + " enregistrée"
                            + (r.nouveauPatient() ? " (nouveau patient inscrit)" : "")
                            + ". Le dossier a été transmis au médecin du service.");
                } else {
                    vue.afficherErreurs(r.erreurs());
                }
            } catch (DaoException ex) {
                JOptionPane.showMessageDialog(principal.getFenetre(), "Base de données injoignable.", "Erreur", JOptionPane.ERROR_MESSAGE);
            }
        }));

        principal.getFenetre().afficher("Nouvelle pré-admission", vue);
    }
}
