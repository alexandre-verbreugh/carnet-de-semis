# Photos

## Ce qui est stocké

Aucune photo n'est conservée telle qu'envoyée. À la réception, chacune est
redressée selon son orientation EXIF, réduite à **1200 px** de plus grand côté,
convertie en **WebP qualité 75**, et accompagnée d'une **vignette de 300 px**.

Ordre de grandeur constaté : une photo de 384 ko en entrée occupe 27 ko sur le
disque, et sa vignette 6 ko. Une page de semis affichant plusieurs photos reste
sous les 10 ko de HTML, les vignettes étant chargées en différé.

**Ne pas relever ces plafonds sans raison sérieuse.** Les photos sont le premier
poste de consommation de l'application, loin devant tout le reste réuni.

## Où elles sont stockées

Dans `var/uploads/photos/{année}/{mois}/`, sous un nom aléatoire de 32
caractères. Volontairement **hors de `public/`** : un jardin photographié et les
abords d'une maison sont des données privées. Les fichiers sont servis par
`PhotoController`, derrière le pare-feu — sans session, la requête est redirigée
vers la page de connexion.

Deux fichiers par photo : `{id}.webp` et `{id}-vignette.webp`. La suppression
retire les deux.

## Configuration PHP requise

**C'est le principal piège d'installation.** Par défaut, PHP limite les envois à
2 Mo alors qu'une photo de téléphone en pèse 3 à 8. Pire, lorsque `post_max_size`
est dépassé, PHP vide entièrement `$_POST` : le jeton CSRF disparaît et l'erreur
affichée n'a aucun rapport avec la cause réelle.

Créer un fichier de surcharge plutôt que modifier le `php.ini` principal :

```ini
; /etc/php/8.4/fpm/conf.d/99-carnet-de-semis.ini
upload_max_filesize = 12M
post_max_size = 40M
```

Sur un serveur, redémarrer PHP-FPM ensuite. En développement avec
`symfony serve`, c'est le fichier `cli/conf.d/` qu'il faut créer, et le serveur
doit être relancé — il conserve la configuration chargée au démarrage.

Extensions nécessaires : `gd` compilée avec le support WebP, et `exif` pour le
redressement automatique. Sans `exif`, tout fonctionne mais les photos prises en
tenant le téléphone de côté apparaissent couchées.

Vérification :

```bash
php -r 'var_dump(gd_info()["WebP Support"], function_exists("exif_read_data"));'
```

## Contrôles à la réception

Trois filtres successifs, dans cet ordre :

1. **Contrainte `Image`** du validateur Symfony, sur le formulaire.
2. **Type MIME réel**, lu dans le contenu du fichier et non déduit de son
   extension. Un fichier PHP renommé en `.jpg` est le vecteur d'attaque classique
   des formulaires d'envoi.
3. **`getimagesize()`**, qui échoue sur tout ce qui n'est pas une image
   exploitable, y compris un fichier au type MIME correctement maquillé.

Une photo refusée **n'annule jamais l'observation** : la note écrite au jardin a
plus de valeur qu'un cliché. Le motif du refus est signalé séparément.

## Sauvegarde

Deux choses à copier, et deux seulement :

- `var/carnet_prod.db` — toutes les données
- `var/uploads/` — toutes les photos

Une restauration doit être testée au moins une fois. Une sauvegarde jamais
restaurée n'est pas une sauvegarde.
