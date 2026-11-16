package fr.lpfh.vue;

import fr.lpfh.modele.Utilisateur;

import javax.swing.BorderFactory;
import javax.swing.Box;
import javax.swing.JLabel;
import javax.swing.JPanel;
import java.awt.BorderLayout;

/**
 * Écran d'accueil des profils médecin, administratif et informaticien (développés aux sprints suivants).
 */
public class AccueilProfilPanel extends JPanel {

    public AccueilProfilPanel(String titre, Utilisateur u, String aVenir) {
        super(new BorderLayout());
        setBackground(Theme.FOND);
        setBorder(BorderFactory.createEmptyBorder(32, 32, 32, 32));

        JPanel col = Theme.colonne();
        col.add(Theme.titre(titre));
        col.add(Box.createVerticalStrut(4));
        col.add(Theme.sousTitre("Bienvenue " + u.prenom() + ". Vous êtes connecté avec le profil « " + u.profil().getLibelle() + " »."));
        col.add(Box.createVerticalStrut(20));
        JLabel bandeau = Theme.texte(aVenir, 13, false, Theme.GRIS);
        bandeau.setOpaque(true);
        bandeau.setBackground(Theme.CLAIR);
        bandeau.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 4, 0, 0, Theme.GRIS), BorderFactory.createEmptyBorder(12, 16, 12, 16)));
        col.add(bandeau);
        add(col, BorderLayout.NORTH);
    }
}
