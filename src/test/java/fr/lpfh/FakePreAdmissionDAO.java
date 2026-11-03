package fr.lpfh;

import fr.lpfh.dao.PreAdmissionDAO;
import fr.lpfh.modele.Patient;
import fr.lpfh.modele.PreAdmissionLigne;
import fr.lpfh.modele.Reference;

import java.time.LocalDate;
import java.util.ArrayList;
import java.util.List;
import java.util.Optional;

/**
 * Faux DAO en mémoire pour tester la pré-admission sans base de données.
 */
public class FakePreAdmissionDAO implements PreAdmissionDAO {

    public final List<Patient> patients = new ArrayList<>();
    public final List<Enregistrement> enregistrements = new ArrayList<>();

    public FakePreAdmissionDAO() {
        patients.add(new Patient(1, "162035912204504", "GIRARD", "Thomas", LocalDate.of(1962, 3, 14),
                "12 rue des Lilas", "59400 Cambrai", "0612345678", null, 1, "HM-784512"));
    }

    @Override
    public Optional<Patient> trouverPatient(String numSecu) {
        return patients.stream().filter(p -> p.numSecuriteSociale().equals(numSecu)).findFirst();
    }

    @Override
    public List<Reference> listerServices() {
        return List.of(new Reference(1, "Chirurgie"), new Reference(2, "Neurologie"));
    }

    @Override
    public List<Reference> listerMutuelles() {
        return List.of(new Reference(1, "Harmonie Mutuelle"), new Reference(2, "MGEN"));
    }

    @Override
    public List<PreAdmissionLigne> lister(Integer creeesPar) {
        List<PreAdmissionLigne> l = new ArrayList<>();
        for (int i = 0; i < enregistrements.size(); i++) {
            Enregistrement e = enregistrements.get(i);
            if (creeesPar == null || creeesPar == e.numSecretaire()) {
                l.add(new PreAdmissionLigne(i + 1, e.prenom() + " " + e.nom(), e.numSecretaire(), "S. DURAND",
                        "Chirurgie", e.dateEntree(), "chez le medecin"));
            }
        }
        return l;
    }

    @Override
    public int enregistrer(Enregistrement e) {
        enregistrements.add(e);
        return enregistrements.size();
    }
}
