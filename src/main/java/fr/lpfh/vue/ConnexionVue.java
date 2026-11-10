package fr.lpfh.vue;

import javax.swing.BorderFactory;
import javax.swing.Box;
import javax.swing.JButton;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JPasswordField;
import javax.swing.JTextField;
import java.awt.Color;
import java.awt.Component;
import java.awt.Dimension;
import java.awt.GridBagLayout;
import java.awt.event.ActionListener;

/**
 * Écran de connexion : identifiant + mot de passe.
 */
public class ConnexionVue extends JFrame {

    private final JTextField identifiant = new JTextField();
    private final JPasswordField motDePasse = new JPasswordField();
    private final JButton seConnecter = Theme.bouton("Se connecter", true);
    private final JLabel message = Theme.texte(" ", 13, false, Theme.ROUGE);

    public ConnexionVue() {
        super("Connexion – LPFH Pré-admission");
        setDefaultCloseOperation(EXIT_ON_CLOSE);
        setSize(1000, 680);
        setLocationRelativeTo(null);

        JPanel fond = new JPanel(new GridBagLayout());
        fond.setBackground(Theme.FOND);

        JPanel carte = Theme.colonne();
        carte.setOpaque(true);
        carte.setBackground(Color.WHITE);
        carte.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(Theme.BORD), BorderFactory.createEmptyBorder(32, 32, 32, 32)));
        carte.setPreferredSize(new Dimension(400, 420));

        identifiant.setBorder(Theme.bordureChamp());
        motDePasse.setBorder(Theme.bordureChamp());
        identifiant.setName("identifiant");
        motDePasse.setName("motDePasse");

        ajouter(carte, Theme.texte("LPFH Clinique", 16, true, Theme.TEXTE));
        ajouter(carte, Theme.texte("Pré-admission", 12, false, Theme.GRIS));
        carte.add(Box.createVerticalStrut(22));
        ajouter(carte, Theme.titre("Connexion"));
        carte.add(Box.createVerticalStrut(12));
        ajouter(carte, message);
        carte.add(Box.createVerticalStrut(8));
        ajouter(carte, Theme.champ("Identifiant", identifiant));
        carte.add(Box.createVerticalStrut(14));
        ajouter(carte, Theme.champ("Mot de passe", motDePasse));
        carte.add(Box.createVerticalStrut(22));
        seConnecter.setMaximumSize(new Dimension(Integer.MAX_VALUE, 42));
        ajouter(carte, seConnecter);

        fond.add(carte);
        setContentPane(fond);
        getRootPane().setDefaultButton(seConnecter); // Entrée = se connecter
    }

    private static void ajouter(JPanel p, Component c) {
        if (c instanceof javax.swing.JComponent jc) {
            jc.setAlignmentX(Component.LEFT_ALIGNMENT);
            if (jc instanceof JPanel) {
                jc.setMaximumSize(new Dimension(Integer.MAX_VALUE, 64));
            }
        }
        p.add(c);
    }

    public String getIdentifiant() {
        return identifiant.getText();
    }

    public String getMotDePasse() {
        return new String(motDePasse.getPassword());
    }

    public void viderMotDePasse() {
        motDePasse.setText("");
    }

    public void afficherErreur(String texte) {
        message.setForeground(Theme.ROUGE);
        message.setText(texte);
    }

    public void afficherInfo(String texte) {
        message.setForeground(Theme.GRIS);
        message.setText(texte);
    }

    public void surConnexion(ActionListener l) {
        seConnecter.addActionListener(l);
    }
}
