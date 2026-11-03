package fr.lpfh.vue;

import fr.lpfh.modele.Utilisateur;

import javax.swing.BorderFactory;
import javax.swing.Box;
import javax.swing.JButton;
import javax.swing.JComponent;
import javax.swing.JFrame;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Component;
import java.awt.Cursor;
import java.awt.Dimension;
import java.awt.Font;
import java.util.LinkedHashMap;
import java.util.Map;

/**
 * Fenêtre principale : barre latérale (menu du profil + déconnexion) et zone de contenu.
 */
public class FenetrePrincipale extends JFrame {

    private final JPanel menu = Theme.colonne();
    private final JPanel contenu = new JPanel(new BorderLayout());
    private final Map<String, JButton> entrees = new LinkedHashMap<>();
    private final JButton deconnexion = new JButton("Se déconnecter");

    public FenetrePrincipale(Utilisateur u) {
        super("LPFH Pré-admission – " + u.profil().getLibelle());
        setDefaultCloseOperation(EXIT_ON_CLOSE);
        setSize(1440, 900);
        setLocationRelativeTo(null);

        JPanel barre = new JPanel(new BorderLayout());
        barre.setBackground(Theme.BARRE);
        barre.setPreferredSize(new Dimension(248, 100));
        barre.setBorder(BorderFactory.createEmptyBorder(24, 16, 24, 16));

        JPanel haut = Theme.colonne();
        haut.add(Theme.texte("LPFH Clinique", 16, true, Color.WHITE));
        haut.add(Theme.texte("Pré-admission", 12, false, new Color(0xB8C4D6)));
        haut.add(Box.createVerticalStrut(28));
        haut.add(Theme.texte(u.profil().getLibelle().toUpperCase(), 11, true, new Color(0xB8C4D6)));
        haut.add(Box.createVerticalStrut(10));
        haut.add(menu);
        barre.add(haut, BorderLayout.NORTH);

        JPanel profil = Theme.colonne();
        profil.setOpaque(true);
        profil.setBackground(new Color(0x2A4570));
        profil.setBorder(BorderFactory.createEmptyBorder(12, 12, 12, 12));
        profil.add(Theme.texte(u.nomComplet(), 13, true, Color.WHITE));
        profil.add(Theme.texte("Profil : " + u.profil().getLibelle(), 11, false, new Color(0xB8C4D6)));
        deconnexion.setFont(Theme.police(Font.PLAIN, 12));
        deconnexion.setForeground(new Color(0x9CC8F5));
        deconnexion.setContentAreaFilled(false);
        deconnexion.setBorderPainted(false);
        deconnexion.setBorder(BorderFactory.createEmptyBorder(4, 0, 0, 0));
        deconnexion.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        profil.add(deconnexion);
        barre.add(profil, BorderLayout.SOUTH);

        contenu.setBackground(Theme.FOND);
        add(barre, BorderLayout.WEST);
        add(contenu, BorderLayout.CENTER);
    }

    /** Ajoute une entrée dans le menu latéral. */
    public JButton ajouterEntree(String libelle) {
        JButton b = new JButton(libelle);
        b.setFont(Theme.police(Font.PLAIN, 14));
        b.setForeground(Color.WHITE);
        b.setHorizontalAlignment(JButton.LEFT);
        b.setBorder(BorderFactory.createEmptyBorder(11, 12, 11, 12));
        b.setFocusPainted(false);
        b.setBorderPainted(false);
        b.setContentAreaFilled(false);
        b.setOpaque(false);
        b.setBackground(Theme.BLEU);
        b.setMaximumSize(new Dimension(Integer.MAX_VALUE, 42));
        b.setAlignmentX(Component.LEFT_ALIGNMENT);
        b.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        entrees.put(libelle, b);
        menu.add(b);
        menu.add(Box.createVerticalStrut(4));
        return b;
    }

    /** Affiche un écran dans la zone de contenu et met l'entrée du menu en surbrillance. */
    public void afficher(String entreeActive, JComponent ecran) {
        entrees.forEach((libelle, b) -> {
            boolean actif = libelle.equals(entreeActive);
            b.setOpaque(actif);
            b.setFont(Theme.police(actif ? Font.BOLD : Font.PLAIN, 14));
        });
        contenu.removeAll();
        JScrollPane defilement = new JScrollPane(new SuitLargeur(ecran));
        defilement.setHorizontalScrollBarPolicy(JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        defilement.setBorder(null);
        defilement.getViewport().setBackground(Theme.FOND);
        defilement.getVerticalScrollBar().setUnitIncrement(16);
        contenu.add(defilement, BorderLayout.CENTER);
        contenu.revalidate();
        contenu.repaint();
    }

    public JButton getDeconnexion() {
        return deconnexion;
    }

    /** Conteneur qui s'adapte à la largeur de la fenêtre (pas de défilement horizontal). */
    private static class SuitLargeur extends JPanel implements javax.swing.Scrollable {
        SuitLargeur(JComponent ecran) {
            super(new BorderLayout());
            setBackground(Theme.FOND);
            add(ecran, BorderLayout.CENTER);
        }

        @Override
        public Dimension getPreferredScrollableViewportSize() {
            return getPreferredSize();
        }

        @Override
        public int getScrollableUnitIncrement(java.awt.Rectangle r, int o, int d) {
            return 16;
        }

        @Override
        public int getScrollableBlockIncrement(java.awt.Rectangle r, int o, int d) {
            return 200;
        }

        @Override
        public boolean getScrollableTracksViewportWidth() {
            return true;
        }

        @Override
        public boolean getScrollableTracksViewportHeight() {
            return false;
        }
    }
}
