package fr.lpfh.dao;

/**
 * Erreur technique d'accès à la base (le message affiché à l'utilisateur reste générique).
 */
public class DaoException extends RuntimeException {
    public DaoException(String message, Throwable cause) {
        super(message, cause);
    }
}
