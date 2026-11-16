package fr.lpfh;

import fr.lpfh.controleur.ConnexionControleur;
import fr.lpfh.dao.JdbcPreAdmissionDAO;
import fr.lpfh.dao.JdbcUtilisateurDAO;
import fr.lpfh.service.AuthService;
import fr.lpfh.service.PreAdmissionService;
import fr.lpfh.vue.ConnexionVue;

import javax.swing.SwingUtilities;
import javax.swing.UIManager;

/**
 * Point d'entrée de l'application de pré-admission LPFH (clic droit → Run 'Main.main()' dans IntelliJ).
 */
public class Main {

    public static void main(String[] args) {
        SwingUtilities.invokeLater(() -> {
            try {
                UIManager.setLookAndFeel(UIManager.getCrossPlatformLookAndFeelClassName());
            } catch (Exception ignore) {
                // apparence par défaut
            }
            ConnexionControleur.Fabrique fabrique = new ConnexionControleur.Fabrique() {
                @Override
                public PreAdmissionService preAdmissionService() {
                    return new PreAdmissionService(new JdbcPreAdmissionDAO());
                }

                @Override
                public AuthService authService() {
                    return new AuthService(new JdbcUtilisateurDAO());
                }
            };
            new ConnexionControleur(new ConnexionVue(), fabrique.authService(), fabrique).afficher(null);
        });
    }
}
