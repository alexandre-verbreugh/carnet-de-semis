#!/usr/bin/env bash
#
# Deploiement par tirage, lance par une tache planifiee sur le serveur.
#
# Le serveur va chercher les nouveautes lui-meme, plutot que GitHub ne pousse
# vers lui. Consequence : aucune cle SSH confiee a GitHub, aucun port ouvert
# sur la Freebox, aucun agent a maintenir. Le depot etant public, c'est aussi
# le seul moyen sur : un runner auto-heberge permettrait a n'importe qui
# d'executer du code sur la machine via une pull request.
#
# Installation :
#   */5 * * * * /var/www/carnet-de-semis/bin/deploiement.sh >> /var/log/carnet-deploiement.log 2>&1
set -euo pipefail

RACINE="${CARNET_RACINE:-/var/www/carnet-de-semis}"
BRANCHE="${CARNET_BRANCHE:-main}"
VERROU="/tmp/carnet-deploiement.lock"

journal() {
    printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$1"
}

# Un deploiement long ne doit pas etre relance par-dessus lui-meme.
exec 9>"$VERROU"
if ! flock -n 9; then
    journal "Deploiement deja en cours, abandon."
    exit 0
fi

cd "$RACINE"

git fetch --quiet origin "$BRANCHE"

LOCAL=$(git rev-parse HEAD)
DISTANT=$(git rev-parse "origin/$BRANCHE")

if [ "$LOCAL" = "$DISTANT" ]; then
    # Silencieux : cette tache tourne toutes les cinq minutes, elle ne doit pas
    # remplir le journal quand il n'y a rien a faire.
    exit 0
fi

journal "Nouveaute detectee : ${LOCAL:0:8} vers ${DISTANT:0:8}"

# La base et les photos sont sauvegardees avant toute migration : une migration
# qui echoue a mi-parcours laisse un schema incoherent.
SAUVEGARDE="/var/backups/carnet/$(date '+%Y%m%d-%H%M%S')"
mkdir -p "$SAUVEGARDE"
if [ -f var/carnet_prod.db ]; then
    sqlite3 var/carnet_prod.db ".backup '$SAUVEGARDE/carnet_prod.db'"
    journal "Base sauvegardee dans $SAUVEGARDE"
fi

git reset --hard "origin/$BRANCHE" --quiet

composer install --no-dev --no-interaction --quiet --optimize-autoloader

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
php bin/console app:species:import
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug

chown -R www-data:www-data var/
chmod -R 750 var/

journal "Deploye en ${DISTANT:0:8}"

# Les sauvegardes de plus de trente jours sont retirees : sans cela, une tache
# qui tourne toutes les cinq minutes finit par remplir le disque.
find /var/backups/carnet -maxdepth 1 -type d -mtime +30 -exec rm -rf {} + 2>/dev/null || true
