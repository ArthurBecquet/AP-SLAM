package fr.lpfh.dao;

import fr.lpfh.modele.CompteConnexion;

import java.time.LocalDate;
import java.util.Optional;

/**
 * Accès aux comptes du personnel (tables personnel / habilitation / profil / journal_acces).
 * Interface → permet de tester le service d'authentification sans base de données.
 */
public interface UtilisateurDAO {

    /** Compte dont l'habilitation est active à la date donnée, ou vide. */
    Optional<CompteConnexion> trouverParIdentifiant(String identifiant, LocalDate jour);

    /** Trace une action dans journal_acces (association « Tracer »). */
    void tracer(int numPersonnel, String typeAction, String ressource, String adresseIp);
}
