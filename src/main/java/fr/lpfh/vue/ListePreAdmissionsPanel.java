package fr.lpfh.vue;

import fr.lpfh.modele.PreAdmissionLigne;
import fr.lpfh.service.Validation;

import javax.swing.BorderFactory;
import javax.swing.Box;
import javax.swing.JButton;
import javax.swing.JComboBox;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JTable;
import javax.swing.table.DefaultTableModel;
import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Dimension;
import java.awt.Font;
import java.util.List;

/**
 * SEC-01 – Pré-admissions (partagées entre toutes les secrétaires, filtre « Créées par »).
 */
public class ListePreAdmissionsPanel extends JPanel {

    private final JComboBox<String> filtre = new JComboBox<>(new String[]{"Toutes les secrétaires", "Moi uniquement"});
    private final JButton nouvelle = Theme.bouton("+ Nouvelle pré-admission", true);
    private final DefaultTableModel modele = new DefaultTableModel(
            new String[]{"PATIENT", "CRÉÉE PAR", "SERVICE", "DATE D'ENTRÉE", "STATUT"}, 0) {
        @Override
        public boolean isCellEditable(int r, int c) {
            return false;
        }
    };
    private final JLabel message = Theme.texte(" ", 14, false, Theme.VERT);

    public ListePreAdmissionsPanel() {
        super(new BorderLayout());
        setBackground(Theme.FOND);
        setBorder(BorderFactory.createEmptyBorder(32, 32, 32, 32));

        JPanel haut = new JPanel(new BorderLayout());
        haut.setOpaque(false);
        JPanel titres = Theme.colonne();
        titres.add(Theme.titre("Pré-admissions"));
        titres.add(Box.createVerticalStrut(4));
        titres.add(Theme.sousTitre("Dossiers créés par toutes les secrétaires médicales (partagés entre collègues)"));
        haut.add(titres, BorderLayout.WEST);
        JPanel droite = new JPanel(new BorderLayout());
        droite.setOpaque(false);
        droite.add(nouvelle, BorderLayout.NORTH);
        haut.add(droite, BorderLayout.EAST);

        JPanel col = Theme.colonne();
        haut.setAlignmentX(LEFT_ALIGNMENT);
        col.add(haut);
        col.add(Box.createVerticalStrut(16));
        message.setAlignmentX(LEFT_ALIGNMENT);
        col.add(message);
        col.add(Box.createVerticalStrut(8));
        JPanel champFiltre = Theme.champ("Créées par", filtre);
        champFiltre.setMaximumSize(new Dimension(300, 64));
        champFiltre.setAlignmentX(LEFT_ALIGNMENT);
        col.add(champFiltre);
        col.add(Box.createVerticalStrut(20));

        JTable table = new JTable(modele);
        table.setRowHeight(44);
        table.setFont(Theme.police(Font.PLAIN, 13));
        table.setShowVerticalLines(false);
        table.setGridColor(Theme.BORD);
        table.getTableHeader().setFont(Theme.police(Font.BOLD, 11));
        table.getTableHeader().setForeground(Theme.GRIS);
        table.getTableHeader().setBackground(Theme.CLAIR);
        table.getTableHeader().setPreferredSize(new Dimension(100, 38));
        JScrollPane sp = new JScrollPane(table);
        sp.getViewport().setBackground(Color.WHITE);
        sp.setBorder(BorderFactory.createLineBorder(Theme.BORD));
        sp.setPreferredSize(new Dimension(800, 320));
        sp.setAlignmentX(LEFT_ALIGNMENT);
        col.add(sp);
        col.add(Box.createVerticalStrut(20));

        JLabel bandeau = Theme.texte("<html>Toutes les secrétaires voient et peuvent reprendre les pré-admissions de leurs collègues, mais pas le contenu du volet médical.</html>",
                13, false, Theme.GRIS);
        bandeau.setOpaque(true);
        bandeau.setBackground(Theme.CLAIR);
        bandeau.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 4, 0, 0, Theme.GRIS), BorderFactory.createEmptyBorder(12, 16, 12, 16)));
        bandeau.setAlignmentX(LEFT_ALIGNMENT);
        col.add(bandeau);

        add(col, BorderLayout.NORTH);
    }

    public void afficher(List<PreAdmissionLigne> lignes, int numUtilisateur) {
        modele.setRowCount(0);
        for (PreAdmissionLigne l : lignes) {
            modele.addRow(new Object[]{
                    l.patient(),
                    l.numSecretaire() == numUtilisateur ? "Moi" : l.secretaire(),
                    l.service(),
                    l.dateEntree().format(Validation.FORMAT_DATE),
                    l.statut().replace("medecin", "médecin")});
        }
    }

    public void afficherSucces(String texte) {
        message.setText(texte);
    }

    public boolean seulementLesMiennes() {
        return filtre.getSelectedIndex() == 1;
    }

    public JComboBox<String> getFiltre() {
        return filtre;
    }

    public JButton getNouvelle() {
        return nouvelle;
    }
}
