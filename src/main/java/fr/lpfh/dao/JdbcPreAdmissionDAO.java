package fr.lpfh.dao;

import fr.lpfh.modele.Patient;
import fr.lpfh.modele.PreAdmissionLigne;
import fr.lpfh.modele.Reference;

import java.sql.Connection;
import java.sql.Date;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.sql.Timestamp;
import java.sql.Types;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;
import java.util.Optional;

/**
 * Implémentation JDBC — requêtes préparées + transaction pour l'enregistrement.
 */
public class JdbcPreAdmissionDAO implements PreAdmissionDAO {

    @Override
    public Optional<Patient> trouverPatient(String numSecu) {
        String sql = "SELECT * FROM patient WHERE num_securite_sociale = ?";
        try (Connection cnx = Database.getConnection(); PreparedStatement ps = cnx.prepareStatement(sql)) {
            ps.setString(1, numSecu);
            try (ResultSet rs = ps.executeQuery()) {
                if (!rs.next()) {
                    return Optional.empty();
                }
                Date naiss = rs.getDate("date_naissance");
                int mut = rs.getInt("num_mutuelle");
                Integer numMutuelle = rs.wasNull() ? null : mut;
                int numPatient = rs.getInt("num_patient");
                return Optional.of(new Patient(numPatient, rs.getString("num_securite_sociale"),
                        rs.getString("nom"), rs.getString("prenom"), naiss == null ? null : naiss.toLocalDate(),
                        rs.getString("adresse"), rs.getString("ville"), rs.getString("telephone"), rs.getString("email"),
                        numMutuelle, rs.getString("numero_adherent"), organismeDuPatient(cnx, numPatient)));
            }
        } catch (SQLException e) {
            throw new DaoException("Recherche du patient impossible", e);
        }
    }

    /** Caisse d'assurance maladie la plus récente du patient (table couvrir), ou null. */
    private Integer organismeDuPatient(Connection cnx, int numPatient) throws SQLException {
        String sql = "SELECT num_organisme FROM couvrir WHERE num_patient = ? ORDER BY date_debut DESC LIMIT 1";
        try (PreparedStatement ps = cnx.prepareStatement(sql)) {
            ps.setInt(1, numPatient);
            try (ResultSet rs = ps.executeQuery()) {
                return rs.next() ? rs.getInt(1) : null;
            }
        }
    }

    @Override
    public List<Reference> listerServices() {
        return references("SELECT num_service, libelle_service FROM service ORDER BY libelle_service");
    }

    @Override
    public List<Reference> listerMutuelles() {
        return references("SELECT num_mutuelle, nom_mutuelle FROM mutuelle ORDER BY nom_mutuelle");
    }

    @Override
    public List<Reference> listerOrganismes() {
        return references("SELECT num_organisme, nom_organisme FROM organisme ORDER BY nom_organisme");
    }

    private List<Reference> references(String sql) {
        List<Reference> liste = new ArrayList<>();
        try (Connection cnx = Database.getConnection();
             PreparedStatement ps = cnx.prepareStatement(sql);
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                liste.add(new Reference(rs.getInt(1), rs.getString(2)));
            }
        } catch (SQLException e) {
            throw new DaoException("Lecture des listes impossible", e);
        }
        return liste;
    }

    @Override
    public List<PreAdmissionLigne> lister(Integer creeesPar) {
        String sql = """
                SELECT pa.num_pre_admission, pa.date_entree_prevue, pa.statut,
                       pt.nom AS p_nom, pt.prenom AS p_prenom, s.libelle_service,
                       pe.num_personnel, pe.nom AS s_nom, pe.prenom AS s_prenom
                  FROM pre_admission pa
                  JOIN patient pt   ON pt.num_patient   = pa.num_patient
                  JOIN service s    ON s.num_service    = pa.num_service
                  JOIN personnel pe ON pe.num_personnel = pa.num_personnel_saisie
                """ + (creeesPar != null ? " WHERE pa.num_personnel_saisie = ?" : "") + " ORDER BY pa.date_entree_prevue";
        List<PreAdmissionLigne> liste = new ArrayList<>();
        try (Connection cnx = Database.getConnection(); PreparedStatement ps = cnx.prepareStatement(sql)) {
            if (creeesPar != null) {
                ps.setInt(1, creeesPar);
            }
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    liste.add(new PreAdmissionLigne(rs.getInt("num_pre_admission"),
                            rs.getString("p_prenom") + " " + rs.getString("p_nom"),
                            rs.getInt("num_personnel"),
                            rs.getString("s_prenom").charAt(0) + ". " + rs.getString("s_nom"),
                            rs.getString("libelle_service"), rs.getDate("date_entree_prevue").toLocalDate(),
                            rs.getString("statut")));
                }
            }
        } catch (SQLException e) {
            throw new DaoException("Lecture des pré-admissions impossible", e);
        }
        return liste;
    }

    @Override
    public int enregistrer(Enregistrement e) {
        try (Connection cnx = Database.getConnection()) {
            cnx.setAutoCommit(false);
            try {
                int numPatient = e.numPatientExistant() == null ? insererPatient(cnx, e) : majMutuelle(cnx, e);
                lierCouverture(cnx, e, numPatient);
                int numPc = insererPersonneConfiance(cnx, e);
                int numPa = insererPreAdmission(cnx, e, numPatient, numPc);
                cnx.commit();
                return numPa;
            } catch (SQLException ex) {
                cnx.rollback(); // rien n'est enregistré si une étape échoue
                throw ex;
            }
        } catch (SQLException ex) {
            throw new DaoException("Enregistrement de la pré-admission impossible", ex);
        }
    }

    private int insererPatient(Connection cnx, Enregistrement e) throws SQLException {
        String sql = """
                INSERT INTO patient (num_securite_sociale, nom, prenom, date_naissance, sexe, adresse, ville, telephone, email,
                                     num_mutuelle, numero_adherent, date_debut_adhesion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                """;
        try (PreparedStatement ps = cnx.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {
            ps.setString(1, e.numSecu());
            ps.setString(2, e.nom());
            ps.setString(3, e.prenom());
            ps.setDate(4, Date.valueOf(e.dateNaissance()));
            setTexteOuNull(ps, 5, e.sexe());
            ps.setString(6, e.adresse());
            ps.setString(7, e.ville());
            ps.setString(8, e.telephone());
            setTexteOuNull(ps, 9, e.email());
            setEntierOuNull(ps, 10, e.numMutuelle());
            setTexteOuNull(ps, 11, e.numeroAdherent());
            if (e.numMutuelle() != null) {
                ps.setDate(12, Date.valueOf(LocalDate.now()));
            } else {
                ps.setNull(12, Types.DATE);
            }
            ps.executeUpdate();
            return cle(ps);
        }
    }

    /**
     * Liaison « Couvrir » : rattache le patient à sa caisse d'assurance maladie.
     * Si la liaison existe déjà, on n'y touche pas (pas de doublon).
     */
    private void lierCouverture(Connection cnx, Enregistrement e, int numPatient) throws SQLException {
        if (e.numOrganisme() == null) {
            return;
        }
        String existe = "SELECT 1 FROM couvrir WHERE num_patient = ? AND num_organisme = ?";
        try (PreparedStatement ps = cnx.prepareStatement(existe)) {
            ps.setInt(1, numPatient);
            ps.setInt(2, e.numOrganisme());
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return;
                }
            }
        }
        String sql = "INSERT INTO couvrir (num_patient, num_organisme, numero_adherent, date_debut) VALUES (?, ?, ?, ?)";
        try (PreparedStatement ps = cnx.prepareStatement(sql)) {
            ps.setInt(1, numPatient);
            ps.setInt(2, e.numOrganisme());
            ps.setString(3, e.numSecu());
            ps.setDate(4, Date.valueOf(LocalDate.now()));
            ps.executeUpdate();
        }
    }

    /** Patient déjà inscrit : on ne met à jour que sa mutuelle (il a pu en changer). */
    private int majMutuelle(Connection cnx, Enregistrement e) throws SQLException {
        String sql = "UPDATE patient SET num_mutuelle = ?, numero_adherent = ? WHERE num_patient = ?";
        try (PreparedStatement ps = cnx.prepareStatement(sql)) {
            setEntierOuNull(ps, 1, e.numMutuelle());
            setTexteOuNull(ps, 2, e.numeroAdherent());
            ps.setInt(3, e.numPatientExistant());
            ps.executeUpdate();
        }
        return e.numPatientExistant();
    }

    private int insererPersonneConfiance(Connection cnx, Enregistrement e) throws SQLException {
        String sql = """
                INSERT INTO personne_confiance (nom_personne_confiance, prenom_personne_confiance,
                                                telephone_personne_confiance, lien_avec_patient)
                VALUES (?, ?, ?, ?)
                """;
        try (PreparedStatement ps = cnx.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {
            ps.setString(1, e.pcNom());
            ps.setString(2, e.pcPrenom());
            ps.setString(3, e.pcTelephone());
            setTexteOuNull(ps, 4, e.pcLien());
            ps.executeUpdate();
            return cle(ps);
        }
    }

    private int insererPreAdmission(Connection cnx, Enregistrement e, int numPatient, int numPc) throws SQLException {
        String sql = """
                INSERT INTO pre_admission (date_demande, motif_hospitalisation, date_entree_prevue, duree_prevue, statut,
                                           date_derniere_modification, num_patient, num_service, num_personnel_saisie,
                                           num_personne_confiance)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                """;
        try (PreparedStatement ps = cnx.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {
            ps.setDate(1, Date.valueOf(LocalDate.now()));
            ps.setString(2, e.motif());
            ps.setDate(3, Date.valueOf(e.dateEntree()));
            setEntierOuNull(ps, 4, e.dureePrevue());
            ps.setString(5, "chez le medecin");
            ps.setTimestamp(6, Timestamp.valueOf(LocalDateTime.now()));
            ps.setInt(7, numPatient);
            ps.setInt(8, e.numService());
            ps.setInt(9, e.numSecretaire());
            ps.setInt(10, numPc);
            ps.executeUpdate();
            return cle(ps);
        }
    }

    private static int cle(PreparedStatement ps) throws SQLException {
        try (ResultSet rs = ps.getGeneratedKeys()) {
            if (!rs.next()) {
                throw new SQLException("Aucune clé générée");
            }
            return rs.getInt(1);
        }
    }

    private static void setTexteOuNull(PreparedStatement ps, int i, String v) throws SQLException {
        if (v == null || v.isBlank()) {
            ps.setNull(i, Types.VARCHAR);
        } else {
            ps.setString(i, v);
        }
    }

    private static void setEntierOuNull(PreparedStatement ps, int i, Integer v) throws SQLException {
        if (v == null) {
            ps.setNull(i, Types.INTEGER);
        } else {
            ps.setInt(i, v);
        }
    }
}
