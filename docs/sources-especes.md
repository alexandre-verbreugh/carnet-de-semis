# Catalogue d'espèces : origine et limites des valeurs

Le catalogue livré vit dans [`data/especes.csv`](../data/especes.csv) et s'importe avec :

```bash
php bin/console app:species:import
```

La commande est idempotente : on peut la relancer à chaque mise à jour du projet.
Elle ne touche jamais aux fiches marquées « perso » (`is_custom`), c'est-à-dire
celles créées ou modifiées depuis l'application.

## D'où viennent ces chiffres

Les valeurs sont des ordres de grandeur consensuels issus de la littérature
potagère courante : fiches techniques de semenciers, tables de semis classiques
et manuels de maraîchage sur petite surface. Aucune ne provient d'une base de
données automatisée — les API de plantes librement accessibles (Trefle, OpenFarm)
sont abandonnées ou ne fournissent pas ce niveau de détail agronomique.

## Ce que ces chiffres ne sont pas

**Ce ne sont pas des garanties.** Un délai de levée « 3 à 6 jours » suppose une
température correcte, une humidité constante et une graine de l'année. En
jardinière, avec un substrat qui sèche vite et se réchauffe plus qu'une pleine
terre, les écarts sont normaux.

**Les valeurs sont données pour un climat tempéré français**, à mi-chemin entre
Nord et Sud. Les mois de semis sont à décaler de deux à trois semaines selon la
région et l'année.

**Les températures minimales de germination** sont des seuils en dessous desquels
la levée devient très lente ou nulle, pas des optima. La plupart des espèces
germent nettement plus vite quelques degrés au-dessus.

C'est précisément l'intérêt de l'application : ces chiffres servent de point de
départ, et l'écran de statistiques compare ensuite le délai **réellement observé**
au délai théorique. Au bout de deux saisons, tes propres chiffres valent mieux
que n'importe quelle table.

## Corriger ou compléter

Deux façons de faire, selon l'usage.

**Pour une correction qui profite à tout le monde** : modifier `data/especes.csv`
et proposer la modification au projet. Le fichier est volontairement en CSV avec
des points-virgules, éditable dans n'importe quel tableur — aucune connaissance
de PHP n'est nécessaire.

**Pour une variété personnelle** : passer par l'application, écran « Espèces »
puis « Ajouter une espèce ». La fiche est alors marquée perso et protégée des
imports.

### Format du fichier

Séparateur `;`, encodage UTF-8, une ligne d'en-tête obligatoire.

| Colonne | Contenu attendu |
|---|---|
| `nom`, `variete`, `famille` | texte libre ; le couple nom + variété doit être unique |
| `categorie` | `legume_feuille`, `legume_racine`, `legume_fruit`, `legumineuse`, `aromatique`, `fleur` |
| `profondeur_mm`, `espacement_cm` | entiers |
| `mois_semis` | numéros de mois séparés par des virgules, par exemple `3,4,5` |
| `levee_min_j`, `levee_max_j` | fenêtre de levée en jours ; alimente les prévisions |
| `recolte_min_j`, `recolte_max_j` | délai avant récolte en jours, compté depuis le semis |
| `temp_min_germination_c` | entier, en degrés Celsius |
| `exposition` | `plein_soleil`, `mi_ombre`, `ombre` |
| `besoin_eau` | `faible`, `moyen`, `eleve` |
| `semis_direct` | `oui` ou `non` |
| `notes` | texte libre ; c'est ce qui est lu au jardin, privilégier le concret |

Vérifier un fichier avant de l'importer, sans rien écrire en base :

```bash
php bin/console app:species:import --dry-run
```
