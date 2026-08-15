<?php

declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

/**
 * Active le controle des cles etrangeres sur chaque connexion SQLite.
 *
 * SQLite ne les applique pas par defaut : sans ce reglage, les contraintes
 * declarees dans le schema sont inertes. Concretement, supprimer un compte
 * laisserait derriere lui ses emplacements, ses semis et ses observations,
 * qui resteraient en base rattaches a un identifiant disparu.
 *
 * Ce n'est pas qu'une question de proprete : promettre l'effacement de ses
 * donnees a quelqu'un et ne pas le tenir est un manquement.
 */
class SqliteForeignKeysMiddleware implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new class($driver) extends AbstractDriverMiddleware {
            public function connect(
                #[\SensitiveParameter]
                array $params,
            ): Connection {
                $connection = parent::connect($params);

                // Le reglage vaut pour la duree de la connexion uniquement,
                // d'ou sa reapplication systematique ici.
                if (str_contains($params['driver'] ?? '', 'sqlite')) {
                    $connection->exec('PRAGMA foreign_keys = ON');
                }

                return $connection;
            }
        };
    }
}
