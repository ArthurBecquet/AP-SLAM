package fr.lpfh;

import fr.lpfh.dao.UtilisateurDAO;
import fr.lpfh.modele.CompteConnexion;

import java.time.LocalDate;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.Optional;

/**
 * Faux DAO en mémoire : les tests n'ont pas besoin de la base MariaDB.
 */
public class FakeUtilisateurDAO implements UtilisateurDAO {

    /** Hash bcrypt de « Lpfh2026! » (le même que dans sql/jeu_de_test.sql, généré par PHP). */
    public static final String HASH = "$2y$10$2T6zrSyQbU2xMzJiisguq.J6eF91OViuCs3KxHAJXXzm6TwcAaVLm";
    public static final String MDP = "Lpfh2026!";

    private record Ligne(CompteConnexion compte, LocalDate debut, LocalDate fin) {
    }

    private final Map<String, Ligne> comptes = new HashMap<>();
    public final List<String> journal = new ArrayList<>();

    public FakeUtilisateurDAO() {
        ajouter(1, "secretaire.test", "actif", "Secrétaire médicale", null);
        ajouter(2, "medecin.test", "actif", "Médecin", null);
        ajouter(3, "admin.test", "actif", "Département administratif", null);
        ajouter(4, "informaticien.test", "actif", "Informaticien", null);
        ajouter(5, "desactive.test", "desactive", "Secrétaire médicale", null);
        ajouter(6, "expire.test", "actif", "Secrétaire médicale", LocalDate.of(2025, 1, 1));
    }

    private void ajouter(int num, String identifiant, String statut, String profil, LocalDate fin) {
        comptes.put(identifiant, new Ligne(new CompteConnexion(num, "NOM" + num, "Prenom" + num, HASH, statut, profil),
                LocalDate.of(2024, 1, 1), fin));
    }

    @Override
    public Optional<CompteConnexion> trouverParIdentifiant(String identifiant, LocalDate jour) {
        Ligne l = comptes.get(identifiant); // recherche exacte, comme une requête préparée
        if (l == null || jour.isBefore(l.debut()) || (l.fin() != null && jour.isAfter(l.fin()))) {
            return Optional.empty();
        }
        return Optional.of(l.compte());
    }

    @Override
    public void tracer(int numPersonnel, String typeAction, String ressource, String adresseIp) {
        journal.add(numPersonnel + ":" + typeAction);
    }
}
