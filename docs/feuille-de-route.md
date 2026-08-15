# Feuille de route

État au 15 août 2026. Une section par session de travail, dans l'ordre où elles
apportent le plus de valeur.

## Ce qui fonctionne aujourd'hui

L'instance est **multi-utilisateur** : plusieurs jardiniers peuvent s'y inscrire,
chacun ne voyant que ses propres données. Le catalogue d'espèces livré reste
partagé ; les variétés ajoutées à la main sont personnelles.

- Authentification par identifiant libre, comptes créés en ligne de commande
- Page d'accueil publique, désactivable par `APP_PUBLIC_LANDING`
- Catalogue de 40 espèces, importable depuis `data/especes.csv`, enrichissable
  depuis l'application
- Emplacements de culture : sept types de contenants, six abris, substrat décrit
  par ses composants et sa couche de surface
- Semis et journal d'observations, avec effets de bord automatiques (date et
  nombre de levés, statut)
- Tableau de bord distinguant les levées en retard des levées à venir
- Inscription publique, protégée par un champ leurre et un délai minimal de
  saisie, sans captcha ni requête vers un tiers
- Suppression de compte avec effacement des photos sur disque
- 43 tests, dont le cloisonnement entre comptes et les pièges à robots
- Sans JavaScript, moins de 8 ko par page

Trois entités existent en base mais n'ont aucun écran : `Photo`, `SeedLot`,
`WeatherDay`.

---

## Session 1 — Photos

**Pourquoi en premier** : c'est la demande d'origine, « voir l'évolution ». Une
photo par semaine sur un semis raconte plus que dix notes écrites.

- Service `PhotoStorage` : réception de l'upload, rotation selon l'EXIF,
  redimensionnement à 1200 px de côté maximum, conversion en WebP qualité 75.
  **Ne pas dépasser ces valeurs** : les photos sont, et de très loin, le premier
  poste de consommation de cette application.
- Stockage dans `var/uploads/photos/{année}/{mois}/`, nom de fichier aléatoire.
- Contrôleur de service protégé par le pare-feu. Les fichiers ne doivent jamais
  être servis depuis `public/` : un jardin photographié reste une donnée privée.
- Vignettes générées à l'enregistrement (300 px), affichées dans la timeline ;
  la version complète n'est chargée qu'au clic.
- Validation stricte à l'upload : type MIME réel vérifié avec `finfo`, extension
  refusée si elle ne correspond pas, taille plafonnée. **C'est le principal
  vecteur d'attaque des applications PHP.**
- Champ `capture="environment"` sur le formulaire d'observation, pour ouvrir
  directement l'appareil photo sur mobile.

**Critère de fin** : une photo prise au téléphone apparaît dans la timeline du
semis, et le poids de la page reste sous 100 ko avec trois vignettes.

---

## Session 2 — Mise en production sur la VM

**Pourquoi maintenant** : l'application ne sert à rien tant qu'elle n'est pas
accessible depuis le jardin, téléphone en main. Déployer tôt, c'est aussi
découvrir les vrais manques d'usage plutôt que de les imaginer.

- Choix du sous-domaine, vhost Apache, certificat Let's Encrypt via certbot.
- PHP-FPM 8.4 : vérifier `pdo_sqlite` et `gd`, absents par défaut.
- Permissions sur `var/` : la base SQLite et les photos doivent être
  inscriptibles par `www-data`, et `var/` interdit d'accès depuis le web.
- Script de déploiement : `git pull`, `composer install --no-dev`,
  `doctrine:migrations:migrate`, `cache:clear`.
- Sauvegarde : la base est un fichier, `var/uploads/` un dossier. Une tâche cron
  qui copie les deux suffit — mais elle doit exister, et être testée en
  restauration au moins une fois.
- Mot de passe fort, `--allow-weak` interdit en production.
- Vérifier l'installation de la PWA depuis le téléphone, en HTTPS réel.
- Mettre à jour `~/doc/vm-freebox.md`.

**Critère de fin** : un semis saisi depuis le téléphone, au jardin, hors du
réseau local.

---

## Session 3 — Courriels

**Pourquoi c'est devenu nécessaire** : l'instance héberge désormais plusieurs
jardiniers. Sans courriel, une personne qui perd son mot de passe est
définitivement bloquée, et seul l'hébergeur peut la débloquer en ligne de
commande. Cela ne tient qu'à quelques comptes.

- Rétablir `symfony/mailer`, retiré lors du dégraissage.
- Champ e-mail sur `User`, **facultatif** : quelqu'un qui ne veut pas donner
  d'adresse doit pouvoir s'inscrire quand même, en acceptant de ne pas pouvoir
  récupérer son mot de passe.
- Réinitialisation par jeton à usage unique, valable une heure, stocké haché.
  **Ne jamais indiquer si l'adresse existe** : la page de demande répond la
  même chose dans tous les cas, sans quoi elle devient un moyen de découvrir
  qui a un compte.
- Limitation du nombre de demandes par adresse et par IP.
- Configuration SMTP documentée, et `MAILER_DSN=null://null` par défaut pour
  qu'une instance sans courriel fonctionne toujours.
- Vérification facultative de l'adresse à l'inscription.

**Attention au coût caché** : dès qu'on envoie des courriels, il faut s'occuper
de délivrabilité — SPF, DKIM, réputation de l'IP. Un message envoyé depuis une
adresse résidentielle finit presque toujours en indésirable. Prévoir un relais
SMTP.

**Critère de fin** : un jardinier qui a perdu son mot de passe le réinitialise
seul, sans intervention de l'hébergeur.

---

## Session 4 — Météo et calendrier

- Client `OpenMeteoClient` et commande `app:weather:sync`, lancée par cron
  quotidien. L'API archive permet de rattraper les jours manquants : l'historique
  depuis le premier semis pourra être récupéré d'un coup.
- Coordonnées réelles à renseigner dans `.env.local` (`WEATHER_LAT`,
  `WEATHER_LON`) — les valeurs livrées pointent sur Paris.
- Bandeau météo sur la fiche de semis, couvrant la période du semis à la levée.
- **Ne pas afficher les précipitations pour un emplacement sous abri.**
  `Shelter::blocksRain()` existe déjà pour ça : sous serre, les relevés de pluie
  ne décrivent rien de réel.
- Vue calendrier mensuelle : observations passées et échéances calculées
  (levées attendues, récoltes prévues).

**Critère de fin** : la fiche d'un semis montre les températures et la pluie
reçues pendant sa germination, et le calendrier affiche le mois en cours.

---

## Session 5 — Stock de graines et statistiques

- Écrans du stock : sachets, marque, lot, péremption, quantité restante. Le
  décrément au semis est déjà codé dans `SowingController`, il n'a pas encore
  d'interface.
- Alerte sur les sachets périmés ou proches de la péremption.
- `SowingStatsService` : taux de levée et écart au délai théorique, agrégés par
  couche de surface, par composant de substrat, par emplacement, par espèce et
  par saison.
- Écran `/stats`. **Afficher les effectifs à côté de chaque moyenne** : un taux
  calculé sur deux semis n'a pas la même valeur qu'un taux sur trente, et sans
  ce nombre on tire des conclusions fausses.
- Ne rien afficher tant qu'un groupe compte moins de trois semis.

**Critère de fin** : l'écran répond à « est-ce que mes semis lèvent mieux depuis
que je couvre de terreau ? », ou dit honnêtement qu'il est trop tôt.

---

## Session 6 — Préparation à la diffusion

- `README.md` : ce que fait l'application, captures d'écran, installation en
  trois commandes, licence.
- `docs/installation.md` : prérequis (PHP 8.4, `pdo_sqlite`, `gd`), procédure,
  mise à jour, sauvegarde, changement de base de données.
- `CONTRIBUTING.md` : comment proposer une espèce (via le CSV, sans savoir
  coder), comment proposer du code.
- En-têtes de licence AGPL sur les fichiers source.
- PHPStan niveau 6 avec `phpstan-symfony`, corrections associées.
- Tests fonctionnels du parcours complet : créer un emplacement, semer,
  observer une levée, vérifier les valeurs dénormalisées.
- Mesure EcoIndex de l'instance en production, publiée dans le README.
- Création du dépôt GitHub et première publication.

**Critère de fin** : quelqu'un qui ne connaît pas le projet l'installe sans
poser de question.

---

## Dette identifiée

À traiter quand l'occasion se présente, aucune n'est bloquante.

| Sujet | Détail |
|---|---|
| **Service worker absent** | Le manifeste existe, l'application s'installe, mais rien ne fonctionne hors ligne. Au fond d'un jardin sans réseau, c'est un vrai manque. Un worker de trente lignes mettant en cache l'app shell suffirait. |
| **Suppression d'observation** | Supprimer une levée ne recalcule pas `germinatedAt` ni `germinatedCount` sur le semis. L'utilisateur est averti par un message, ce qui est un pansement. À traiter dans `ObservationRecorder`. |
| **Bornes dupliquées** | Les valeurs `min` et `max` sont écrites dans les entités (`Assert\Range`) et dans les formulaires. Modifier l'une sans l'autre passerait inaperçu. |
| **`doctrine/orm` figé en 3.6.7** | À relever dès la publication de DBAL 4.5. Voir `docs/contraintes-versions.md`. |
| **Formulaire d'emplacement long** | Les descriptions des sept types allongent la page à 14 ko. Sans JavaScript, on ne peut pas les replier ; à réduire si la saisie sur mobile devient pénible. |
| **Aucun test fonctionnel** | Seuls les services et la sécurité sont testés. Un contrôleur peut casser sans que rien ne le signale. |
| **Aucune récupération de mot de passe** | Sans courriel, un mot de passe perdu ne se récupère qu'en ligne de commande, par l'hébergeur. Traité en session 3. |
| **Pas de mentions légales** | Héberger les données d'autres personnes impose d'indiquer qui héberge, quelles données sont collectées et comment les effacer. La suppression de compte existe, la page d'information non. |
| **Pas d'export de ses données** | Le RGPD prévoit la portabilité : un export CSV ou JSON de ses propres semis reste à faire. |
| **Pas d'analyse statique** | PHPStan n'est pas installé. |

---

## Hors périmètre, volontairement

Ces idées reviennent souvent sur ce type d'application. Elles sont écartées.

- **Partage de données entre comptes.** Chaque jardinier voit ses propres
  semis, et rien d'autre. Pas de jardin collectif, pas de suivi partagé.
- **Notifications par courriel ou push.** Le Mailer a été retiré du projet.
  Un rappel se lit sur le tableau de bord, qu'on ouvre quand on jardine.
- **Application mobile native.** La PWA suffit et ne demande aucun magasin
  d'applications.
- **Reconnaissance d'espèce par photo.** Cela impliquerait un service tiers, des
  photos qui sortent du serveur, et beaucoup de calcul pour peu d'usage.
- **Synchronisation multi-instances.** Complexité sans commune mesure avec le
  besoin.
