package fr.lpfh.controleur;

import fr.lpfh.dao.DaoException;
import fr.lpfh.modele.Utilisateur;
import fr.lpfh.service.AuthService;
import fr.lpfh.service.Session;
import fr.lpfh.vue.ConnexionVue;

import java.util.Optional;

/**
 * Contrôleur de l'écran de connexion : vérifie les identifiants puis ouvre l'écran du profil.
 */
public class ConnexionControleur {

    private final ConnexionVue vue;
    private final AuthService auth;
    private final Fabrique fabrique;

    public ConnexionControleur(ConnexionVue vue, AuthService auth, Fabrique fabrique) {
        this.vue = vue;
        this.auth = auth;
        this.fabrique = fabrique;
        vue.surConnexion(e -> connecter());
    }

    public void afficher(String info) {
        if (info != null) {
            vue.afficherInfo(info);
        }
        vue.setVisible(true);
    }

    private void connecter() {
        Optional<Utilisateur> u;
        try {
            u = auth.connecter(vue.getIdentifiant(), vue.getMotDePasse());
        } catch (DaoException e) {
            vue.afficherErreur("Base de données injoignable. Vérifiez db.properties.");
            return;
        }
        vue.viderMotDePasse();
        if (u.isEmpty()) {
            vue.afficherErreur(AuthService.MESSAGE_ECHEC);
            return;
        }
        Session.ouvrir(u.get());
        auth.tracer(u.get(), "CONNEXION");
        vue.dispose();
        new PrincipalControleur(u.get(), auth, fabrique).afficher(); // écran propre au profil
    }

    /** Crée les objets (DAO réels ou de test) : permet de lancer l'appli sans changer le code. */
    public interface Fabrique {
        fr.lpfh.service.PreAdmissionService preAdmissionService();

        AuthService authService();
    }
}
