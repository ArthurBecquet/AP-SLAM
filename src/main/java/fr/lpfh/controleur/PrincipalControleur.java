package fr.lpfh.controleur;

import fr.lpfh.modele.Profil;
import fr.lpfh.modele.Utilisateur;
import fr.lpfh.service.AccesRefuseException;
import fr.lpfh.service.AuthService;
import fr.lpfh.service.Session;
import fr.lpfh.vue.AccueilProfilPanel;
import fr.lpfh.vue.ConnexionVue;
import fr.lpfh.vue.FenetrePrincipale;

import javax.swing.JOptionPane;

/**
 * Fenêtre principale : construit le menu selon le profil et contrôle l'accès à chaque écran.
 */
public class PrincipalControleur {

    private final Utilisateur utilisateur;
    private final AuthService auth;
    private final ConnexionControleur.Fabrique fabrique;
    private final FenetrePrincipale fenetre;

    public PrincipalControleur(Utilisateur u, AuthService auth, ConnexionControleur.Fabrique fabrique) {
        this.utilisateur = u;
        this.auth = auth;
        this.fabrique = fabrique;
        this.fenetre = new FenetrePrincipale(u);
        fenetre.getDeconnexion().addActionListener(e -> deconnecter());

        switch (u.profil()) {
            case SECRETAIRE -> {
                PreAdmissionControleur pa = new PreAdmissionControleur(fabrique.preAdmissionService(), this);
                fenetre.ajouterEntree("Pré-admissions").addActionListener(e -> ouvrir(() -> pa.ouvrirListe(null)));
                fenetre.ajouterEntree("Nouvelle pré-admission").addActionListener(e -> ouvrir(pa::ouvrirNouvelle));
                ouvrir(() -> pa.ouvrirListe(null));
            }
            case MEDECIN -> accueil("Espace médecin", "Pré-admissions à compléter (volet médical) — développé au sprint suivant.", Profil.MEDECIN);
            case ADMINISTRATIF -> accueil("Département administratif", "Pré-admissions à valider — développé au sprint suivant.", Profil.ADMINISTRATIF);
            case INFORMATICIEN -> accueil("Espace informaticien", "Comptes et habilitations — développé au sprint suivant.", Profil.INFORMATICIEN);
        }
    }

    private void accueil(String titre, String aVenir, Profil profil) {
        fenetre.ajouterEntree("Accueil").addActionListener(e -> ouvrir(() -> afficherAccueil(titre, aVenir, profil)));
        ouvrir(() -> afficherAccueil(titre, aVenir, profil));
    }

    private void afficherAccueil(String titre, String aVenir, Profil profil) {
        AuthService.exiger(profil);
        fenetre.afficher("Accueil", new AccueilProfilPanel(titre, utilisateur, aVenir));
    }

    /** Ouvre un écran ; si l'accès est refusé (pas de session / mauvais profil), on l'affiche et on bloque. */
    void ouvrir(Runnable ecran) {
        try {
            ecran.run();
        } catch (AccesRefuseException e) {
            if (e.isSansSession()) {
                retourConnexion("Veuillez vous connecter pour accéder à cet écran.");
            } else {
                JOptionPane.showMessageDialog(fenetre, e.getMessage(), "Accès refusé", JOptionPane.WARNING_MESSAGE);
            }
        }
    }

    public FenetrePrincipale getFenetre() {
        return fenetre;
    }

    public Utilisateur getUtilisateur() {
        return utilisateur;
    }

    public void afficher() {
        fenetre.setVisible(true);
    }

    /** Déconnexion : trace, vide la session, retour à l'écran de connexion. */
    public void deconnecter() {
        auth.tracer(utilisateur, "DECONNEXION");
        Session.fermer();
        retourConnexion("Vous êtes déconnecté.");
    }

    private void retourConnexion(String info) {
        fenetre.dispose();
        new ConnexionControleur(new ConnexionVue(), fabrique.authService(), fabrique).afficher(info);
    }
}
