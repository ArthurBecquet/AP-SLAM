package fr.lpfh.vue;

import javax.swing.BorderFactory;
import javax.swing.BoxLayout;
import javax.swing.JButton;
import javax.swing.JComponent;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.border.Border;
import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Component;
import java.awt.Cursor;
import java.awt.Dimension;
import java.awt.Font;
import java.awt.GridLayout;

/**
 * Couleurs et composants communs, repris des maquettes Figma.
 */
public final class Theme {

    public static final Color FOND = new Color(0xF3F6FA);
    public static final Color BARRE = new Color(0x16325C);
    public static final Color BLEU = new Color(0x1F5FAE);
    public static final Color TEXTE = new Color(0x1B2430);
    public static final Color GRIS = new Color(0x5F6B7A);
    public static final Color BORD = new Color(0xDCE3EC);
    public static final Color CLAIR = new Color(0xEEF1F5);
    public static final Color VERT = new Color(0x16803D);
    public static final Color VERT_CLAIR = new Color(0xE6F7EB);
    public static final Color ORANGE = new Color(0xBF6B0D);
    public static final Color ORANGE_CLAIR = new Color(0xFFF5E0);
    public static final Color ROUGE = new Color(0xB42318);
    public static final Color ROUGE_CLAIR = new Color(0xFDECEA);

    public static final String POLICE = "SansSerif";

    private Theme() {
    }

    public static Font police(int style, int taille) {
        return new Font(POLICE, style, taille);
    }

    public static JLabel texte(String t, int taille, boolean gras, Color couleur) {
        JLabel l = new JLabel(t);
        l.setFont(police(gras ? Font.BOLD : Font.PLAIN, taille));
        l.setForeground(couleur);
        return l;
    }

    public static JLabel titre(String t) {
        return texte(t, 24, true, TEXTE);
    }

    public static JLabel sousTitre(String t) {
        return texte(t, 14, false, GRIS);
    }

    /** Carte blanche avec un titre (et un badge optionnel à droite). */
    public static JPanel carte(String titre, JComponent droite) {
        JPanel carte = new JPanel(new BorderLayout(0, 14));
        carte.setBackground(Color.WHITE);
        carte.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(BORD), BorderFactory.createEmptyBorder(16, 20, 20, 20)));
        JPanel entete = new JPanel(new BorderLayout());
        entete.setOpaque(false);
        entete.add(texte(titre, 16, true, TEXTE), BorderLayout.WEST);
        if (droite != null) {
            entete.add(droite, BorderLayout.EAST);
        }
        carte.add(entete, BorderLayout.NORTH);
        carte.setAlignmentX(Component.LEFT_ALIGNMENT);
        return carte;
    }

    /** Grille de champs sur 3 colonnes (comme les maquettes). */
    public static JPanel grille(int colonnes) {
        JPanel g = new JPanel(new GridLayout(0, colonnes, 28, 14));
        g.setOpaque(false);
        return g;
    }

    /** Libellé gris au-dessus du composant de saisie. */
    public static JPanel champ(String libelle, JComponent saisie) {
        JPanel p = new JPanel(new BorderLayout(0, 5));
        p.setOpaque(false);
        p.add(texte(libelle, 12, false, GRIS), BorderLayout.NORTH);
        saisie.setPreferredSize(new Dimension(100, 38));
        saisie.setFont(police(Font.PLAIN, 14));
        p.add(saisie, BorderLayout.CENTER);
        return p;
    }

    public static Border bordureChamp() {
        return BorderFactory.createCompoundBorder(BorderFactory.createLineBorder(BORD), BorderFactory.createEmptyBorder(0, 10, 0, 10));
    }

    public static JButton bouton(String texte, boolean principal) {
        JButton b = new JButton(texte);
        b.setFont(police(Font.BOLD, 14));
        b.setForeground(principal ? Color.WHITE : TEXTE);
        b.setBackground(principal ? BLEU : CLAIR);
        b.setOpaque(true);
        b.setContentAreaFilled(true);
        b.setBorderPainted(false);
        b.setFocusPainted(false);
        b.setBorder(BorderFactory.createEmptyBorder(10, 20, 10, 20));
        b.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        return b;
    }

    public static JLabel badge(String texte, Color couleur, Color fond) {
        JLabel l = texte(texte, 12, true, couleur);
        l.setOpaque(true);
        l.setBackground(fond);
        l.setBorder(BorderFactory.createEmptyBorder(4, 10, 4, 10));
        return l;
    }

    /** Colonne verticale qui empile des composants pleine largeur. */
    public static JPanel colonne() {
        JPanel p = new JPanel();
        p.setLayout(new BoxLayout(p, BoxLayout.Y_AXIS));
        p.setOpaque(false);
        return p;
    }
}
