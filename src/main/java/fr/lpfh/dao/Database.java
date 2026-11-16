package fr.lpfh.dao;

import java.io.IOException;
import java.io.InputStream;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.util.Properties;

/**
 * Ouvre les connexions JDBC à partir de src/main/resources/db.properties.
 */
public final class Database {

    private static Properties config;

    private Database() {
    }

    public static Connection getConnection() throws SQLException {
        Properties p = config();
        return DriverManager.getConnection(p.getProperty("db.url"), p.getProperty("db.user"), p.getProperty("db.password", ""));
    }

    private static synchronized Properties config() {
        if (config == null) {
            config = new Properties();
            try (InputStream in = Database.class.getResourceAsStream("/db.properties")) {
                if (in == null) {
                    throw new IllegalStateException("Fichier db.properties introuvable dans src/main/resources");
                }
                config.load(new java.io.InputStreamReader(in, java.nio.charset.StandardCharsets.UTF_8));
            } catch (IOException e) {
                throw new IllegalStateException("Lecture de db.properties impossible", e);
            }
        }
        return config;
    }
}
