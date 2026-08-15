#!/usr/bin/env bash
#
# Installation du carnet de semis sur un serveur Debian ou Ubuntu avec Apache.
#
# A lancer sur le serveur, en root :
#   sudo CARNET_DOMAINE=semis.example.com bash installation-serveur.sh
#
# Le script est reentrant : le relancer ne casse rien et reprend ou il en est.
set -euo pipefail

DOMAINE="${CARNET_DOMAINE:?Renseigner CARNET_DOMAINE, par exemple semis.example.com}"
RACINE="${CARNET_RACINE:-/var/www/carnet-de-semis}"
DEPOT="${CARNET_DEPOT:-https://github.com/alexandre-verbreugh/carnet-de-semis.git}"
PHP_VERSION="${CARNET_PHP:-8.4}"

if [ "$(id -u)" -ne 0 ]; then
    echo "A lancer en root (sudo)." >&2
    exit 1
fi

etape() { printf '\n\033[1;32m==>\033[0m %s\n' "$1"; }

etape "Extensions PHP"
# pdo_sqlite est la seule reellement manquante sur une installation Symfony
# classique, mais les autres sont verifiees au cas ou.
apt-get update -qq
apt-get install -y -qq \
    "php${PHP_VERSION}-sqlite3" "php${PHP_VERSION}-gd" "php${PHP_VERSION}-intl" \
    "php${PHP_VERSION}-mbstring" "php${PHP_VERSION}-xml" "php${PHP_VERSION}-curl" \
    sqlite3 git unzip

if ! php -r 'exit(extension_loaded("pdo_sqlite") ? 0 : 1);'; then
    echo "pdo_sqlite toujours absent, abandon." >&2
    exit 1
fi

etape "Limites d'envoi pour les photos"
# Par defaut PHP plafonne a 2 Mo, alors qu'une photo de telephone en pese 3 a 8.
for sapi in fpm cli; do
    dossier="/etc/php/${PHP_VERSION}/${sapi}/conf.d"
    [ -d "$dossier" ] || continue
    cat > "${dossier}/99-carnet-de-semis.ini" <<'INI'
; Photos de telephone : 3 a 8 Mo par cliche, plusieurs par observation.
upload_max_filesize = 12M
post_max_size = 40M
INI
done

etape "Code source"
if [ -d "$RACINE/.git" ]; then
    git -C "$RACINE" fetch --quiet origin main
    git -C "$RACINE" reset --hard origin/main --quiet
else
    git clone --quiet "$DEPOT" "$RACINE"
fi
cd "$RACINE"

etape "Configuration"
# Ecrit avant l'installation des dependances : --no-dev retire
# web-profiler-bundle, que Symfony charge tant que APP_ENV vaut dev.
if [ ! -f .env.local ]; then
    cat > .env.local <<ENV
APP_ENV=prod
APP_SECRET=$(php -r 'echo bin2hex(random_bytes(16));')

# Coordonnees utilisees pour les releves meteo. A ajuster.
WEATHER_LAT=47.0844
WEATHER_LON=2.3964
WEATHER_TIMEZONE=Europe/Paris

APP_PUBLIC_LANDING=true
APP_REPOSITORY_URL=https://github.com/alexandre-verbreugh/carnet-de-semis
ENV
    chmod 640 .env.local
    echo "  .env.local cree, APP_SECRET genere aleatoirement"
else
    echo "  .env.local existant, conserve"
fi

etape "Dependances"
if ! command -v composer > /dev/null; then
    apt-get install -y -qq composer
fi
# Sans --quiet : quand un script post-installation echoue, Composer n'affiche
# que « Script @auto-scripts was called », le message utile etant masque.
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-interaction --optimize-autoloader

etape "Base de donnees"
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
php bin/console app:species:import
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug

etape "Permissions"
# var/ contient la base et les photos : seul PHP doit y ecrire.
chown -R www-data:www-data "$RACINE"
chmod -R 750 "$RACINE/var"
mkdir -p "$RACINE/var/uploads/photos"
chown -R www-data:www-data "$RACINE/var/uploads"

etape "Vhost Apache"
a2enmod rewrite > /dev/null 2>&1 || true
cat > "/etc/apache2/sites-available/${DOMAINE}.conf" <<VHOST
<VirtualHost *:80>
    ServerName ${DOMAINE}
    DocumentRoot ${RACINE}/public

    <Directory ${RACINE}/public>
        AllowOverride None
        Require all granted
        FallbackResource /index.php
    </Directory>

    # La base et les photos vivent dans var/ : hors de portee du web.
    <DirectoryMatch "^${RACINE}/(var|src|config|vendor|bin|tests|migrations)">
        Require all denied
    </DirectoryMatch>

    ErrorLog \${APACHE_LOG_DIR}/${DOMAINE}-error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAINE}-access.log combined
</VirtualHost>
VHOST

a2ensite "${DOMAINE}.conf" > /dev/null
apache2ctl configtest
systemctl reload apache2

etape "Certificat TLS"
# getent plutot que host ou dig : toujours present, aucun paquet a installer.
if getent hosts "$DOMAINE" > /dev/null 2>&1; then
    certbot --apache -d "$DOMAINE" --non-interactive --agree-tos --redirect --register-unsafely-without-email \
        || echo "  certbot a echoue : verifier que ${DOMAINE} pointe bien vers ce serveur."
else
    echo "  ${DOMAINE} ne resout pas encore."
    echo "  Creer l'enregistrement DNS, puis lancer :"
    echo "    sudo certbot --apache -d ${DOMAINE} --redirect"
fi

etape "Deploiement automatique"
cat > /etc/cron.d/carnet-de-semis <<CRON
# Deploiement par tirage : le serveur va chercher les nouveautes lui-meme.
# Aucune cle SSH confiee a GitHub, aucun port ouvert.
*/5 * * * * root ${RACINE}/bin/deploiement.sh >> /var/log/carnet-deploiement.log 2>&1
CRON
chmod 644 /etc/cron.d/carnet-de-semis
mkdir -p /var/backups/carnet

printf '\n\033[1;32mInstallation terminee.\033[0m\n\n'
echo "Il reste a creer un compte :"
echo "  cd ${RACINE} && sudo -u www-data php bin/console app:user:create <identifiant>"
echo
echo "Puis ouvrir : https://${DOMAINE}"
