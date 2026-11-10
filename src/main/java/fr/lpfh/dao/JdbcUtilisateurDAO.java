package fr.lpfh.dao;

import fr.lpfh.modele.CompteConnexion;

import java.sql.Connection;
import java.sql.Date;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Timestamp;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.Optional;

/**
 * Implémentation JDBC — uniquement des requêtes préparées (aucune concaténation SQL).
 */
public class JdbcUtilisateurDAO implements UtilisateurDAO {

    private static final String SQL_COMPTE = """
            SELECT p.num_personnel, p.nom, p.prenom, p.mot_de_passe_hash, p.statut_compte, pr.libelle_profil
              FROM personnel p
              JOIN habilitation h ON h.num_personnel = p.num_personnel
              JOIN profil pr      ON pr.num_profil   = h.num_profil
             WHERE p.identifiant_connexion = ?
               AND h.statut_habilitation = 'active'
               AND h.date_debut_validite <= ?
               AND (h.date_fin_validite IS NULL OR h.date_fin_validite >= ?)
             LIMIT 1
            """;

    private static final String SQL_TRACE = """
            INSERT INTO journal_acces (date_heure_acces, type_action, ressource_consultee, adresse_ip, num_personnel)
            VALUES (?, ?, ?, ?, ?)
            """;

    @Override
    public Optional<CompteConnexion> trouverParIdentifiant(String identifiant, LocalDate jour) {
        try (Connection cnx = Database.getConnection();
             PreparedStatement ps = cnx.prepareStatement(SQL_COMPTE)) {
            ps.setString(1, identifiant);
            ps.setDate(2, Date.valueOf(jour));
            ps.setDate(3, Date.valueOf(jour));
            try (ResultSet rs = ps.executeQuery()) {
                if (!rs.next()) {
                    return Optional.empty();
                }
                return Optional.of(new CompteConnexion(
                        rs.getInt("num_personnel"), rs.getString("nom"), rs.getString("prenom"),
                        rs.getString("mot_de_passe_hash"), rs.getString("statut_compte"), rs.getString("libelle_profil")));
            }
        } catch (SQLException e) {
            throw new DaoException("Connexion à la base impossible", e);
        }
    }

    @Override
    public void tracer(int numPersonnel, String typeAction, String ressource, String adresseIp) {
        try (Connection cnx = Database.getConnection();
             PreparedStatement ps = cnx.prepareStatement(SQL_TRACE)) {
            ps.setTimestamp(1, Timestamp.valueOf(LocalDateTime.now()));
            ps.setString(2, typeAction);
            ps.setString(3, ressource);
            ps.setString(4, adresseIp);
            ps.setInt(5, numPersonnel);
            ps.executeUpdate();
        } catch (SQLException e) {
            throw new DaoException("Écriture du journal impossible", e);
        }
    }
}
