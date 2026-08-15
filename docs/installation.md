# Installation

## Prérequis

- **PHP 8.4 ou 8.5**, avec les extensions `pdo_sqlite`, `gd` (compilée avec le
  support WebP), `exif`, `intl` et `mbstring`
- **Composer 2**
- Un serveur web, ou rien du tout en développement

Sur Debian ou Ubuntu :

```bash
sudo apt install php8.4-cli php8.4-sqlite3 php8.4-gd php8.4-intl php8.4-mbstring php8.4-xml composer
```

Vérifier que tout est en place :

```bash
php -r 'var_dump(extension_loaded("pdo_sqlite"), gd_info()["WebP Support"], function_exists("exif_read_data"));'
```

Les trois doivent renvoyer `true`. Sans `exif`, tout fonctionne, mais les photos
prises en tenant le téléphone de côté apparaîtront couchées.

## Installation

**Créer `.env.local` avec `APP_ENV=prod` avant `composer install`** (voir la
section suivante). Dans l'autre ordre, l'installation échoue : `--no-dev` retire
`web-profiler-bundle`, que Symfony tente pourtant de charger tant que
l'environnement vaut `dev`.

```bash
git clone https://github.com/alexandre-verbreugh/carnet-de-semis.git
cd carnet-de-semis
printf 'APP_ENV=prod\nAPP_SECRET=%s\n' "$(php -r 'echo bin2hex(random_bytes(16));')" > .env.local
composer install --no-dev
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:species:import
php bin/console app:user:create <votre-identifiant>
```

La base est créée automatiquement dans `var/`. Aucun serveur de base de données
à installer.

En développement, `symfony serve -d` suffit à tester.

## Configuration

Ne jamais modifier `.env`, qui est versionné. Créer un fichier `.env.local`,
ignoré par git :

```dotenv
APP_ENV=prod
APP_SECRET=<chaine aleatoire unique>

# Coordonnees utilisees pour les releves meteo
WEATHER_LAT=47.0844
WEATHER_LON=2.3964

# Page de presentation publique. false pour une instance discrete :
# la racine redirige alors directement vers la connexion.
APP_PUBLIC_LANDING=true
APP_REPOSITORY_URL=https://github.com/alexandre-verbreugh/carnet-de-semis
```

**`APP_SECRET` doit être unique à votre instance.** Celui présent dans `.env.dev`
est un secret de développement partagé par le dépôt, il ne convient pas en
production. En générer un :

```bash
php -r 'echo bin2hex(random_bytes(16)), PHP_EOL;'
```

## Si l'installation échoue sur « Script @auto-scripts was called »

C'est le symptôme d'un `composer install --no-dev` lancé alors que
`APP_ENV` vaut encore `dev`. L'option `--no-dev` retire `web-profiler-bundle`,
`debug-bundle` et `maker-bundle`, que `config/bundles.php` déclare pour
l'environnement de développement : Symfony cherche des classes absentes.

Vérifier :

```bash
grep APP_ENV .env.local          # doit contenir APP_ENV=prod
ls vendor/symfony/web-profiler-bundle 2>/dev/null || echo "absent, normal en prod"
```

Corriger en créant `.env.local` avec `APP_ENV=prod`, puis relancer
`composer install --no-dev`.

Composer masque la cause réelle derrière « Script @auto-scripts was called via
post-install-cmd ». Pour voir le message utile :

```bash
composer install --no-dev -vvv
```

## Configuration PHP pour les photos

**C'est le principal piège.** Par défaut, PHP limite les envois à 2 Mo alors
qu'une photo de téléphone en pèse 3 à 8. Créer un fichier de surcharge :

```ini
; /etc/php/8.4/fpm/conf.d/99-carnet-de-semis.ini
upload_max_filesize = 12M
post_max_size = 40M
```

Puis redémarrer PHP-FPM. Détails dans [`photos.md`](photos.md).

## Serveur web

La racine web est `public/`, **jamais** le dossier du projet. `var/` contient la
base de données et les photos : il ne doit être accessible que par PHP.

Exemple de vhost Apache :

```apache
<VirtualHost *:443>
    ServerName semis.example.com
    DocumentRoot /var/www/carnet-de-semis/public

    <Directory /var/www/carnet-de-semis/public>
        AllowOverride None
        Require all granted
        FallbackResource /index.php
    </Directory>

    <Directory /var/www/carnet-de-semis/public/bundles>
        FallbackResource disabled
    </Directory>

    # var/ contient la base et les photos : hors de portee du web.
    <DirectoryMatch "^/var/www/carnet-de-semis/(var|src|config|vendor)">
        Require all denied
    </DirectoryMatch>

    SSLEngine on
</VirtualHost>
```

Le certificat s'obtient avec `sudo certbot --apache -d semis.example.com`.

## Permissions

PHP doit pouvoir écrire dans `var/` — la base et les photos y vivent :

```bash
sudo chown -R www-data:www-data var/
sudo chmod -R 750 var/
```

## Mise à jour

```bash
git pull
composer install --no-dev
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:species:import
php bin/console cache:clear
```

`app:species:import` est sans risque : la commande met à jour les fiches livrées
et **ne touche jamais** aux espèces créées ou modifiées depuis l'application.

## Sauvegarde

Deux choses à copier, et deux seulement :

- `var/carnet_prod.db` — toutes les données
- `var/uploads/` — toutes les photos

```bash
sqlite3 var/carnet_prod.db ".backup '/chemin/sauvegarde/carnet.db'"
tar czf /chemin/sauvegarde/photos.tar.gz var/uploads/
```

Utiliser `.backup` plutôt qu'une copie de fichier : cela garantit un instantané
cohérent même si quelqu'un écrit pendant la sauvegarde.

**Tester une restauration au moins une fois.** Une sauvegarde jamais restaurée
n'est pas une sauvegarde.

## Utiliser MariaDB ou PostgreSQL

Possible, mais les migrations livrées sont générées pour SQLite. Il faut les
régénérer :

```bash
rm migrations/Version*.php
# renseigner DATABASE_URL dans .env.local, puis :
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

Pour un usage personnel, SQLite est largement suffisant : quelques centaines
d'écritures par saison, un seul utilisateur.
