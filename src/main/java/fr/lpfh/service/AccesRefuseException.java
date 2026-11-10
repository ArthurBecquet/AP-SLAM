package fr.lpfh.service;

/**
 * Levée quand une action est tentée sans session ou avec un profil non autorisé.
 */
public class AccesRefuseException extends RuntimeException {

    private final boolean sansSession;

    public AccesRefuseException(boolean sansSession) {
        super(sansSession ? "Veuillez vous connecter." : "Votre profil ne permet pas d'accéder à cet écran.");
        this.sansSession = sansSession;
    }

    public boolean isSansSession() {
        return sansSession;
    }
}
