# Carnet de semis

Un carnet pour noter ce qu'on sème et voir ce qui lève vraiment. Jardinière,
carré potager, pleine terre ou sous serre.

Noter une date de semis est facile. Se souvenir six mois plus tard que les radis
de la jardinière du muret n'ont levé qu'à 40 %, parce que le substrat était de la
terre de jardin tassée, l'est beaucoup moins.

L'application enregistre chaque semis, chaque observation et chaque photo, puis
compare le **taux de levée** et le **délai réel** aux délais théoriques de
l'espèce — par emplacement, par substrat et par saison. Au bout de deux saisons,
ce sont vos chiffres qui décident, plus les tables imprimées au dos des sachets.

## Ce qu'elle fait

- Un journal par semis : levée, arrosage, éclaircissage, maladie, récolte
- Des photos prises au téléphone, réduites et converties automatiquement
- Un catalogue de 40 espèces avec profondeur de semis, espacement et délais
- Le suivi des sachets de graines et de leur date de péremption
- Des prévisions de levée et de récolte, et une alerte quand la levée tarde

## Sobre par construction

- **Moins de 8 ko par page**, photos exclues
- **Aucune dépendance JavaScript.** Un seul script d'une trentaine de lignes,
  pour replier le menu. Tout fonctionne script désactivé
- **Aucune requête vers un tiers** : pas de police distante, pas d'icône chargée
  ailleurs, pas de traceur
- **Aucun outil de compilation** : ni Node, ni bundler, ni étape de build
- **SQLite** : un fichier, aucun serveur de base de données à installer
- Les photos sont réduites à 1200 px et converties en WebP. Une photo de 384 ko
  occupe 27 ko sur le disque

Il faut PHP 8.4 et rien d'autre. L'application tourne sur un hébergement
mutualisé comme sur un Raspberry Pi.

## Installation

```bash
git clone https://github.com/alexandre-verbreugh/carnet-de-semis.git
cd carnet-de-semis && composer install --no-dev
php bin/console doctrine:migrations:migrate --no-interaction && php bin/console app:species:import
```

Puis créer un compte :

```bash
php bin/console app:user:create <votre-identifiant>
```

Le guide complet, y compris la configuration PHP nécessaire aux photos, est dans
[`docs/installation.md`](docs/installation.md).

## Documentation

| Fichier | Contenu |
|---|---|
| [`docs/installation.md`](docs/installation.md) | Installation, mise à jour, sauvegarde |
| [`docs/photos.md`](docs/photos.md) | Traitement des photos, configuration PHP |
| [`docs/sources-especes.md`](docs/sources-especes.md) | Origine des données du catalogue, et comment le compléter |
| [`docs/feuille-de-route.md`](docs/feuille-de-route.md) | Ce qui vient, et ce qui est volontairement écarté |
| [`docs/contraintes-versions.md`](docs/contraintes-versions.md) | Dépendances volontairement figées, et pourquoi |

## Contribuer

**Pour corriger ou ajouter une espèce, aucune connaissance de PHP n'est
nécessaire.** Le catalogue est un simple fichier CSV, ouvrable dans un tableur :
[`data/especes.csv`](data/especes.csv). Les valeurs livrées sont des ordres de
grandeur de la littérature potagère courante, pas des mesures — les corrections
issues d'observations réelles sont particulièrement bienvenues.

Avant de proposer du code, lire [`docs/contraintes-versions.md`](docs/contraintes-versions.md) :
plusieurs choix apparemment étranges ont une raison documentée.

Toute contribution doit respecter les deux règles qui font l'identité du projet :
rester utilisable sans JavaScript, et ne pas alourdir les pages.

## Licence

[AGPL-3.0](LICENSE). Toute personne hébergeant une version modifiée doit en
publier le code.
