package fr.lpfh.service;

import org.bouncycastle.crypto.generators.OpenBSDBCrypt;

import java.security.SecureRandom;

/**
 * Hachage / vérification des mots de passe avec BCrypt.
 * Compatible avec les hash « $2y$ » déjà présents en base (générés par PHP password_hash).
 */
public final class MotDePasse {

    private static final int COUT = 10;
    private static final SecureRandom ALEA = new SecureRandom();

    private MotDePasse() {
    }

    public static String hacher(String motDePasse) {
        byte[] sel = new byte[16];
        ALEA.nextBytes(sel);
        return OpenBSDBCrypt.generate("2y", motDePasse.toCharArray(), sel, COUT);
    }

    public static boolean verifier(String motDePasse, String hash) {
        if (motDePasse == null || hash == null || !hash.startsWith("$2")) {
            return false;
        }
        try {
            return OpenBSDBCrypt.checkPassword(hash, motDePasse.toCharArray());
        } catch (IllegalArgumentException e) {
            return false; // hash mal formé
        }
    }
}
