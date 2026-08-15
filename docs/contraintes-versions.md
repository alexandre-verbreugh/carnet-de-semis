# Contraintes de versions

Deux dépendances sont volontairement figées en dessous de leur dernière version.
Ce fichier explique pourquoi, pour qu'on ne « corrige » pas ça par erreur.

## doctrine/orm figé en 3.6.7

**Symptôme si on remonte en 3.6.8** — toute génération de migration échoue :

```
The setSchema() method requires the DBAL Schema::edit() API which is not
available in the current DBAL version. This feature requires doctrine/dbal ^4.5.
```

**Cause** — ORM 3.6.8 introduit `GenerateSchemaEventArgs::setSchema()`, mais cette
méthode lève une exception tant que DBAL est en dessous de 4.5. En face, les
listeners de `symfony/doctrine-bridge` 8.0 se contentent de vérifier
`method_exists($event, 'setSchema')` avant de l'appeler : la garde passe, l'appel
explose. Et DBAL 4.5 n'est pas encore publiée (dernière version : 4.4.4).

**Quand lever la contrainte** — dès que `doctrine/dbal` 4.5 sort :

```bash
composer require "doctrine/dbal:^4.5" "doctrine/orm:^3.6.8" --with-all-dependencies
```

## doctrine/migrations figé en 3.9.6

Figé au moment du même diagnostic. La cause réelle était l'ORM ci-dessus, donc
cette contrainte est probablement inutile — à retester en même temps que DBAL 4.5.

## make:migration inutilisable

Pour la même raison, `php bin/console make:migration` échoue. Utiliser la commande
native, qui produit exactement le même résultat :

```bash
php bin/console doctrine:migrations:diff
```

## Messenger en transport synchrone

`config/packages/messenger.yaml` a été réduit à un seul transport en `sync://`, et
le `failure_transport` supprimé. L'application n'a aucun traitement asynchrone ;
le transport doctrine par défaut ajoutait une table `messenger_messages` au schéma
pour rien.

Si un besoin asynchrone apparaît (envoi de mails en file, par exemple), il faudra
rétablir un transport doctrine et régénérer une migration pour cette table.
