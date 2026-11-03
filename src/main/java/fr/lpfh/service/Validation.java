package fr.lpfh.service;

import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.time.format.DateTimeParseException;
import java.time.format.ResolverStyle;
import java.util.regex.Pattern;

/**
 * Contrôles de format (refaits côté application avant tout enregistrement).
 */
public final class Validation {

    public static final DateTimeFormatter FORMAT_DATE = DateTimeFormatter.ofPattern("dd/MM/uuuu").withResolverStyle(ResolverStyle.STRICT);

    private static final Pattern SECU = Pattern.compile("^[12]\\d{2}(0[1-9]|1[0-2]|[2-9]\\d)(\\d{2}|2A|2B)\\d{6}\\d{2}$");
    private static final Pattern TEL = Pattern.compile("^(0[1-9]\\d{8}|\\+33[1-9]\\d{8})$");
    private static final Pattern EMAIL = Pattern.compile("^[^@\\s]+@[^@\\s]+\\.[^@\\s]+$");

    private Validation() {
    }

    /** Supprime espaces, points et tirets ; majuscules (Corse 2A/2B). */
    public static String normaliserNumSecu(String num) {
        return num == null ? "" : num.replaceAll("[\\s.\\-]", "").toUpperCase();
    }

    /** 15 caractères, clé = 97 - (13 premiers chiffres mod 97). */
    public static boolean numSecuValide(String num) {
        String n = normaliserNumSecu(num);
        if (!SECU.matcher(n).matches()) {
            return false;
        }
        String corps = n.substring(0, 13).replace("2A", "19").replace("2B", "18");
        int cle = Integer.parseInt(n.substring(13));
        int reste = 0;
        for (char c : corps.toCharArray()) {
            reste = (reste * 10 + (c - '0')) % 97;
        }
        return 97 - reste == cle;
    }

    public static boolean telephoneValide(String tel) {
        return tel != null && TEL.matcher(tel.replaceAll("[\\s.\\-]", "")).matches();
    }

    public static boolean emailValide(String email) {
        return email != null && EMAIL.matcher(email).matches();
    }

    /** Date au format JJ/MM/AAAA, ou null si invalide (ex. 31/02/2026). */
    public static LocalDate date(String texte) {
        if (texte == null || texte.isBlank()) {
            return null;
        }
        try {
            return LocalDate.parse(texte.trim(), FORMAT_DATE);
        } catch (DateTimeParseException e) {
            return null;
        }
    }

    public static boolean vide(String s) {
        return s == null || s.isBlank();
    }
}
