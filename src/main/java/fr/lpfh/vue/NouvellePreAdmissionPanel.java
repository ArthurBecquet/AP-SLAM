package fr.lpfh.vue;

import fr.lpfh.modele.Patient;
import fr.lpfh.modele.PreAdmissionSaisie;
import fr.lpfh.modele.Reference;
import fr.lpfh.service.Validation;

import javax.swing.BorderFactory;
import javax.swing.Box;
import javax.swing.DefaultComboBoxModel;
import javax.swing.JButton;
import javax.swing.JComboBox;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JTextField;
import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Dimension;
import java.awt.FlowLayout;
import java.util.List;
import java.util.Map;

/**
 * SEC-02 – Nouvelle pré-admission (maquette Figma) : recherche du patient par n° de sécu,
 * patient, hospitalisation, mutuelle (modifiable), personne à prévenir, volet médical (restreint).
 */
public class NouvellePreAdmissionPanel extends JPanel {

    private final JTextField numSecu = champTexte();
    private final JButton rechercher = Theme.bouton("Rechercher", true);
    private final JLabel etatRecherche = new JLabel();

    private final JTextField nom = champTexte(), prenom = champTexte(), dateNaissance = champTexte(),
            adresse = champTexte(), ville = champTexte(), telephone = champTexte(), email = champTexte();
    private final JComboBox<Reference> service = new JComboBox<>();
    private final JTextField dateEntree = champTexte(), duree = champTexte(), motif = champTexte();
    private final JComboBox<Reference> mutuelle = new JComboBox<>();
    private final JTextField numeroAdherent = champTexte();
    private final JLabel rappelMutuelle = Theme.badge("⚠ Vérifier avec le patient si sa mutuelle a changé", Theme.ORANGE, Theme.ORANGE_CLAIR);
    private final JTextField pcNom = champTexte(), pcPrenom = champTexte(), pcTelephone = champTexte(), pcLien = champTexte();

    private final JLabel erreurs = Theme.texte("", 13, false, Theme.ROUGE);
    private final JButton annuler = Theme.bouton("Annuler", false);
    private final JButton enregistrer = Theme.bouton("Enregistrer", true);

    private static final Reference AUCUNE = new Reference(0, "— Aucune —");
    private static final Reference CHOISIR = new Reference(0, "— Choisir —");

    public NouvellePreAdmissionPanel() {
        super(new BorderLayout());
        setBackground(Theme.FOND);
        setBorder(BorderFactory.createEmptyBorder(32, 32, 32, 32));

        JPanel col = Theme.colonne();
        col.add(gauche(Theme.titre("Nouvelle pré-admission")));
        col.add(Box.createVerticalStrut(4));
        col.add(gauche(Theme.sousTitre("Recherchez d'abord le patient par son n° de sécu. S'il n'est pas inscrit, remplissez ses informations. Les champs avec * sont obligatoires.")));
        col.add(Box.createVerticalStrut(16));

        erreurs.setOpaque(true);
        erreurs.setBackground(Theme.ROUGE_CLAIR);
        erreurs.setVisible(false);
        col.add(gauche(erreurs));
        col.add(Box.createVerticalStrut(8));

        // Recherche
        JPanel recherche = new JPanel(new BorderLayout(16, 0));
        recherche.setOpaque(false);
        recherche.add(Theme.champ("N° de sécurité sociale *", numSecu), BorderLayout.CENTER);
        JPanel bas = new JPanel(new BorderLayout());
        bas.setOpaque(false);
        bas.add(rechercher, BorderLayout.SOUTH);
        recherche.add(bas, BorderLayout.EAST);
        JPanel cRecherche = Theme.carte("Rechercher le patient", etatRecherche);
        cRecherche.add(recherche, BorderLayout.CENTER);
        ajouterCarte(col, cRecherche);

        // Patient
        JPanel gp = Theme.grille(3);
        gp.add(Theme.champ("Nom *", nom));
        gp.add(Theme.champ("Prénom *", prenom));
        gp.add(Theme.champ("Date de naissance * (JJ/MM/AAAA)", dateNaissance));
        gp.add(Theme.champ("Adresse *", adresse));
        gp.add(Theme.champ("Ville *", ville));
        gp.add(Theme.champ("Téléphone *", telephone));
        gp.add(Theme.champ("Email", email));
        JPanel cPatient = Theme.carte("Patient", null);
        cPatient.add(gp, BorderLayout.CENTER);
        ajouterCarte(col, cPatient);

        // Hospitalisation
        JPanel gh = Theme.grille(3);
        gh.add(Theme.champ("Service *", service));
        gh.add(Theme.champ("Date d'entrée * (JJ/MM/AAAA)", dateEntree));
        gh.add(Theme.champ("Durée prévue (jours)", duree));
        JPanel ch = Theme.carte("Hospitalisation", null);
        JPanel hosp = new JPanel(new BorderLayout(0, 14));
        hosp.setOpaque(false);
        hosp.add(gh, BorderLayout.NORTH);
        hosp.add(Theme.champ("Motif *", motif), BorderLayout.CENTER);
        ch.add(hosp, BorderLayout.CENTER);
        ajouterCarte(col, ch);

        // Mutuelle
        JPanel gm = Theme.grille(3);
        gm.add(Theme.champ("Nom de la mutuelle", mutuelle));
        gm.add(Theme.champ("N° d'adhérent", numeroAdherent));
        gm.add(new JLabel());
        rappelMutuelle.setVisible(false);
        JPanel cm = Theme.carte("Mutuelle", rappelMutuelle);
        cm.add(gm, BorderLayout.CENTER);
        ajouterCarte(col, cm);

        // Personne à prévenir
        JPanel gpc = Theme.grille(3);
        gpc.add(Theme.champ("Nom *", pcNom));
        gpc.add(Theme.champ("Prénom *", pcPrenom));
        gpc.add(Theme.champ("Téléphone *", pcTelephone));
        gpc.add(Theme.champ("Lien avec le patient", pcLien));
        JPanel cpc = Theme.carte("Personne à prévenir", null);
        cpc.add(gpc, BorderLayout.CENTER);
        ajouterCarte(col, cpc);

        // Volet médical (accès restreint)
        JPanel cv = Theme.carte("Volet médical", Theme.badge("Accès restreint", Theme.GRIS, Theme.CLAIR));
        cv.setBackground(Theme.CLAIR);
        cv.add(Theme.texte("Rempli par le médecin du service. La secrétaire n'y a pas accès.", 13, false, Theme.GRIS), BorderLayout.CENTER);
        ajouterCarte(col, cv);

        JPanel actions = new JPanel(new FlowLayout(FlowLayout.RIGHT, 14, 0));
        actions.setOpaque(false);
        actions.add(annuler);
        actions.add(enregistrer);
        col.add(gauche(actions));

        add(col, BorderLayout.NORTH);
        modePatient(null, false);

        // Noms des composants (utiles pour les tests d'interface / captures)
        numSecu.setName("numSecu");
        service.setName("service");
        dateEntree.setName("dateEntree");
        motif.setName("motif");
        pcNom.setName("pcNom");
        pcPrenom.setName("pcPrenom");
        pcTelephone.setName("pcTelephone");
    }

    private static JTextField champTexte() {
        JTextField t = new JTextField();
        t.setBorder(Theme.bordureChamp());
        return t;
    }

    private static <T extends javax.swing.JComponent> T gauche(T c) {
        c.setAlignmentX(LEFT_ALIGNMENT);
        return c;
    }

    private static void ajouterCarte(JPanel col, JPanel carte) {
        carte.setMaximumSize(new Dimension(Integer.MAX_VALUE, carte.getPreferredSize().height));
        col.add(gauche(carte));
        col.add(Box.createVerticalStrut(16));
    }

    public void setListes(List<Reference> services, List<Reference> mutuelles) {
        DefaultComboBoxModel<Reference> ms = new DefaultComboBoxModel<>();
        ms.addElement(CHOISIR);
        services.forEach(ms::addElement);
        service.setModel(ms);
        DefaultComboBoxModel<Reference> mm = new DefaultComboBoxModel<>();
        mm.addElement(AUCUNE);
        mutuelles.forEach(mm::addElement);
        mutuelle.setModel(mm);
    }

    /**
     * Patient trouvé → champs pré-remplis et verrouillés, rappel mutuelle affiché.
     * Patient absent (recherche faite) → champs vides à remplir pour l'inscrire.
     */
    public void modePatient(Patient p, boolean rechercheFaite) {
        JTextField[] champs = {nom, prenom, dateNaissance, adresse, ville, telephone, email};
        for (JTextField t : champs) {
            t.setEditable(p == null);
            t.setBackground(p == null ? Color.WHITE : Theme.CLAIR);
            if (rechercheFaite) {
                t.setText("");
            }
        }
        if (p != null) {
            nom.setText(p.nom());
            prenom.setText(p.prenom());
            dateNaissance.setText(p.dateNaissance() == null ? "" : p.dateNaissance().format(Validation.FORMAT_DATE));
            adresse.setText(p.adresse());
            ville.setText(p.ville());
            telephone.setText(p.telephone());
            email.setText(p.email() == null ? "" : p.email());
            selectionner(mutuelle, p.numMutuelle());
            numeroAdherent.setText(p.numeroAdherent() == null ? "" : p.numeroAdherent());
            etat("✓ Patient déjà inscrit – infos pré-remplies", Theme.VERT, Theme.VERT_CLAIR);
        } else if (rechercheFaite) {
            etat("Patient non trouvé – remplissez ses informations", Theme.ORANGE, Theme.ORANGE_CLAIR);
        } else {
            etatRecherche.setVisible(false);
        }
        rappelMutuelle.setVisible(p != null);
    }

    private void etat(String texte, Color c, Color fond) {
        JLabel b = Theme.badge(texte, c, fond);
        etatRecherche.setText(b.getText());
        etatRecherche.setFont(b.getFont());
        etatRecherche.setForeground(c);
        etatRecherche.setBackground(fond);
        etatRecherche.setOpaque(true);
        etatRecherche.setBorder(b.getBorder());
        etatRecherche.setVisible(true);
    }

    private static void selectionner(JComboBox<Reference> combo, Integer id) {
        for (int i = 0; i < combo.getItemCount(); i++) {
            Reference r = combo.getItemAt(i);
            if ((id == null && r.id() == 0) || (id != null && r.id() == id)) {
                combo.setSelectedIndex(i);
                return;
            }
        }
    }

    public PreAdmissionSaisie getSaisie() {
        PreAdmissionSaisie s = new PreAdmissionSaisie();
        s.numSecuriteSociale = numSecu.getText();
        s.nom = nom.getText();
        s.prenom = prenom.getText();
        s.dateNaissance = dateNaissance.getText();
        s.adresse = adresse.getText();
        s.ville = ville.getText();
        s.telephone = telephone.getText();
        s.email = email.getText();
        Reference sv = (Reference) service.getSelectedItem();
        s.numService = sv == null || sv.id() == 0 ? null : sv.id();
        s.dateEntree = dateEntree.getText();
        s.dureePrevue = duree.getText();
        s.motif = motif.getText();
        Reference m = (Reference) mutuelle.getSelectedItem();
        s.numMutuelle = m == null || m.id() == 0 ? null : m.id();
        s.numeroAdherent = numeroAdherent.getText();
        s.pcNom = pcNom.getText();
        s.pcPrenom = pcPrenom.getText();
        s.pcTelephone = pcTelephone.getText();
        s.pcLien = pcLien.getText();
        return s;
    }

    public void afficherErreurs(Map<String, String> err) {
        if (err.isEmpty()) {
            erreurs.setVisible(false);
            return;
        }
        StringBuilder html = new StringBuilder("<html><div style='padding:8px 12px'><b>La pré-admission n'a pas été enregistrée :</b><ul style='margin:4px 0 0 18px'>");
        err.values().forEach(m -> html.append("<li>").append(m.replace("<", "&lt;")).append("</li>"));
        erreurs.setText(html.append("</ul></div></html>").toString());
        erreurs.setVisible(true);
        revalidate();
    }

    public String getNumSecu() {
        return numSecu.getText();
    }

    public JButton getRechercher() {
        return rechercher;
    }

    public JButton getAnnuler() {
        return annuler;
    }

    public JButton getEnregistrer() {
        return enregistrer;
    }
}
