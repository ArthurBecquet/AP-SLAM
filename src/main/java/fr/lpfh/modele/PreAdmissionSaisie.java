package fr.lpfh.modele;

/**
 * Données saisies dans le formulaire « Nouvelle pré-admission » (texte brut, contrôlé ensuite côté service).
 * Les dates sont saisies au format JJ/MM/AAAA.
 */
public class PreAdmissionSaisie {
    // Patient
    public String numSecuriteSociale = "";
    public String nom = "";
    public String prenom = "";
    public String dateNaissance = "";
    public String adresse = "";
    public String ville = "";
    public String telephone = "";
    public String email = "";
    // Hospitalisation
    public Integer numService;
    public String dateEntree = "";
    public String dureePrevue = "";
    public String motif = "";
    // Assurance maladie : caisse qui couvre le patient (table couvrir)
    public Integer numOrganisme;
    // Mutuelle (modifiable si le patient a changé de mutuelle)
    public Integer numMutuelle;
    public String numeroAdherent = "";
    // Personne de confiance / à prévenir
    public String pcNom = "";
    public String pcPrenom = "";
    public String pcTelephone = "";
    public String pcLien = "";
}
