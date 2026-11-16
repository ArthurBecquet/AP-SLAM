package fr.lpfh.dao;

import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.Reader;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.util.Properties;

/**
 * Ouvre les connexions JDBC.
 * <p>
 * 1. Valeurs par défaut : src/main/resources/db.properties (versionné, sans mot de passe).<br>
 * 2. Puis, s'il existe, le fichier local {@code db.local.properties} placé à côté du projet
 * (ou le fichier indiqué par {@code -Dlpfh.db=chemin}) : il contient l'adresse du serveur,
 * le compte applicatif et son mot de passe. Il est ignoré par Git : aucun mot de passe n'est versionné.
 */
public final class Database {

    static final String FICHIER_LOCAL = "db.local.properties";

    private static Properties config;

    private Database() {
    }

    public static Connection getConnection() throws SQLException {
        Properties p = config();
        return DriverManager.getConnection(p.getProperty("db.url"), p.getProperty("db.user"), p.getProperty("db.password", ""));
    }

    private static synchronized Properties config() {
        if (config == null) {
            Properties p = new Properties();
            try (InputStream in = Database.class.getResourceAsStream("/db.properties")) {
                if (in == null) {
                    throw new IllegalStateException("Fichier db.properties introuvable dans src/main/resources");
                }
                p.load(new InputStreamReader(in, StandardCharsets.UTF_8));
            } catch (IOException e) {
                throw new IllegalStateException("Lecture de db.properties impossible", e);
            }
            Path local = Path.of(System.getProperty("lpfh.db", FICHIER_LOCAL));
            if (Files.isRegularFile(local)) {
                try (Reader r = Files.newBufferedReader(local, StandardCharsets.UTF_8)) {
                    p.load(r); // écrase les valeurs par défaut
                } catch (IOException e) {
                    throw new IllegalStateException("Lecture de " + local.toAbsolutePath() + " impossible", e);
                }
            }
            config = p;
        }
        return config;
    }
}
