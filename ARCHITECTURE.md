# Architecture — La Réglette

Application interne d’une salle d’escalade de bloc. Symfony **7.4 LTS**, PHP **8.2+** (8.3 ou 8.4 recommandés), Twig, Doctrine ORM, Messenger.

Le projet suit le MVC Symfony classique : contrôleur mince, règles dans les entités, le formulaire et les voters, requêtes dans les repositories. L’interface est rendue par Twig. Le JavaScript reste accessoire (le barème sanctionne une interface reconstruite en JS).

---

## 1. Modèle de données

Quatre entités, deux enums. La cotation est un enum PHP **indexé par un entier**, pour pouvoir écrire « de 5+ à 6B » en SQL (`cotation >= :min AND cotation <= :max`).

```mermaid
erDiagram
    UTILISATEUR ||--o{ BLOC : "ouvre"
    UTILISATEUR ||--o{ ASCENSION : "réussit"
    SECTEUR ||--o{ BLOC : "contient"
    BLOC ||--o{ ASCENSION : "reçoit"

    UTILISATEUR {
        int id
        string email
        string password
        json roles
        string prenom
        string nom
    }
    SECTEUR {
        int id
        string nom
    }
    BLOC {
        int id
        string nom
        string couleur
        int cotation
        datetime date_ouverture
        datetime date_demontage
        int nombre_reussites
        float note_moyenne
        int secteur_id
        int ouvreur_id
    }
    ASCENSION {
        int id
        datetime date_reussite
        int note
        text commentaire
        int grimpeur_id
        int bloc_id
    }
```

`Ascension` est une entité à part entière. Un même grimpeur peut réussir plusieurs fois le même bloc : chaque réussite a sa date, sa note (1 à 5) et un commentaire facultatif.

### Tables créées

| Table | Rôle |
| --- | --- |
| `utilisateur` | Comptes. Le nom de table évite le mot réservé SQL `user`. La classe PHP reste `User`. |
| `secteur` | Zones de la salle. |
| `bloc` | Itinéraires, y compris ceux déjà démontés. |
| `ascension` | Réussites. |
| `messenger_messages` | File d’attente Doctrine (pas une entité métier). |
| `doctrine_migration_versions` | Historique des migrations. |

### `User` (`utilisateur`)

| Propriété | Type | Contrainte |
| --- | --- | --- |
| `email` | string | unique, identifiant de connexion |
| `password` | string | hash, jamais le mot de passe en clair |
| `roles` | json | un seul rôle stocké, le plus élevé |
| `prenom`, `nom` | string | affichage |

Relations inverses : `blocsOuverts` (OneToMany vers `Bloc`), `ascensions` (OneToMany vers `Ascension`).

### `Secteur`

| Propriété | Type | Contrainte |
| --- | --- | --- |
| `nom` | string(255) | unique, obligatoire |

Relation inverse : `blocs`.

### `Bloc`

| Propriété | Type | Contrainte |
| --- | --- | --- |
| `nom` | string(255) | obligatoire |
| `couleur` | enum `CouleurPrise` (string) | obligatoire |
| `cotation` | enum `Cotation` (int) | obligatoire, comparable |
| `dateOuverture` | `datetime_immutable` | obligatoire |
| `dateDemontage` | `datetime_immutable` | vide tant que le bloc est en place |
| `nombreReussites` | int | défaut `0`, mis à jour par Messenger |
| `noteMoyenne` | float | nullable, `null` s’il n’y a aucune réussite |
| `secteur` | ManyToOne `Secteur` | obligatoire |
| `ouvreur` | ManyToOne `User` | obligatoire, l’ouvreur qui a posé le bloc |

Un bloc **en place** est un bloc dont `dateDemontage` est `null`.

Démonter renseigne `dateDemontage`. La ligne reste en base, ainsi que ses ascensions.

Index utiles, à poser sur l’entité avant la migration :

- `(date_demontage, cotation)` pour la liste filtrée
- `secteur_id`, `ouvreur_id`

### `Ascension`

| Propriété | Type | Contrainte |
| --- | --- | --- |
| `dateReussite` | `datetime_immutable` | obligatoire |
| `note` | int | 1 à 5 |
| `commentaire` | text | facultatif |
| `grimpeur` | ManyToOne `User` | obligatoire |
| `bloc` | ManyToOne `Bloc` | obligatoire |

Index : `(bloc_id, date_reussite)`, `(grimpeur_id, bloc_id)`.

`orphanRemoval` reste à non : on ne supprime pas un bloc pour le démonter.

### Enum `Cotation` (entier)

À créer à la main dans `src/Enum/Cotation.php`, avant `make:entity`. Le rang entier est la valeur stockée.

| Rang | Label | Rang | Label |
| --- | --- | --- | --- |
| 1 | 3 | 10 | 6C+ |
| 2 | 4 | 11 | 7A |
| 3 | 5 | 12 | 7A+ |
| 4 | 5+ | 13 | 7B |
| 5 | 6A | 14 | 7B+ |
| 6 | 6A+ | 15 | 7C |
| 7 | 6B | 16 | 7C+ |
| 8 | 6B+ | 17 | 8A |
| 9 | 6C | 18 | 8A+ |

Le filtre « de 5+ à 6B » devient `cotation BETWEEN 4 AND 7`.

Maker génère les colonnes enum en `string`. Après génération, la colonne `cotation` doit être forcée en entier, parce que l’enum est `int` :

```php
#[ORM\Column(enumType: Cotation::class)]
private Cotation $cotation;
```

Avec un enum `Cotation: int`, Doctrine enregistre le rang. Vérifier dans la migration que la colonne SQL est un entier, et la corriger si Maker a écrit `VARCHAR`.

Méthode `label(): string` sur l’enum pour l’affichage (`6A`, `5+`…). Les `case` PHP ne peuvent pas commencer par un chiffre : `SixA`, `CinqPlus`, etc.

### Enum `CouleurPrise` (string)

`jaune`, `orange`, `vert`, `bleu`, `rouge`, `rose`, `violet`, `noir`, `blanc`. Valeur stockée = le libellé, donc la colonne string produite par Maker convient.

---

## 2. Règles métier

- L’inscription publique attribue uniquement `ROLE_GRIMPEUR`. Ouvreurs et admin viennent des fixtures.
- Un bloc démonté disparaît de la liste « en place ». Il reste visible dans les ascensions des grimpeurs qui l’ont réussi, et sur la fiche de son ouvreur.
- Une ascension est refusée si le bloc est démonté.
- `dateReussite` est au plus tôt le jour d’ouverture, et au plus tard aujourd’hui.
- La note moyenne et le nombre de réussites affichés viennent des colonnes du bloc. Ils sont recalculés dans le worker, après création ou suppression d’une ascension.
- La suppression d’une ascension ne supprime pas le bloc.

---

## 3. Arborescence cible

```
src/
├── Command/
│   └── DemontageBlocsCommand.php          # app:blocs:demontage
├── Controller/
│   ├── Admin/
│   │   ├── SecteurController.php
│   │   └── UserController.php             # changement de rôle
│   ├── Api/
│   │   └── BlocApiController.php          # GET /api/blocs
│   ├── AscensionController.php
│   ├── BlocController.php
│   ├── OuvreurController.php              # fiche ouvreur
│   ├── RegistrationController.php         # Maker
│   └── SecurityController.php             # Maker
├── DataFixtures/
│   └── AppFixtures.php
├── Entity/
│   ├── Ascension.php
│   ├── Bloc.php
│   ├── Secteur.php
│   └── User.php
├── Enum/
│   ├── Cotation.php
│   └── CouleurPrise.php
├── Form/
│   ├── AscensionType.php
│   ├── BlocFilterType.php
│   ├── BlocType.php
│   ├── SecteurType.php
│   └── UserRoleType.php
├── Message/
│   └── RecalculerStatistiquesBloc.php     # contient l'id du bloc
├── MessageHandler/
│   └── RecalculerStatistiquesBlocHandler.php
├── Model/
│   └── BlocFiltre.php                     # DTO, pas une table
├── Repository/
│   ├── AscensionRepository.php
│   ├── BlocRepository.php
│   ├── SecteurRepository.php
│   └── UserRepository.php
├── Scheduler/
│   └── MainSchedule.php                   # bonus, lundi matin
└── Security/
    └── Voter/
        ├── AscensionVoter.php
        └── BlocVoter.php
```

`BlocFiltre` porte les trois filtres facultatifs (`secteur`, `cotationMin`, `cotationMax`). La liste HTML et `GET /api/blocs` construisent le même objet et appellent la même méthode de repository.

---

## 4. Sécurité

Hiérarchie, du plus large au plus précis. On ne stocke que le rôle le plus élevé : `ROLE_ADMIN` donne déjà `ROLE_OUVREUR` et `ROLE_GRIMPEUR`.

```yaml
# config/packages/security.yaml
role_hierarchy:
    ROLE_OUVREUR: ROLE_GRIMPEUR
    ROLE_ADMIN: ROLE_OUVREUR

access_control:
    - { path: ^/api, roles: PUBLIC_ACCESS }
    - { path: ^/$, roles: PUBLIC_ACCESS }
    - { path: ^/login, roles: PUBLIC_ACCESS }
    - { path: ^/register, roles: PUBLIC_ACCESS }
    - { path: ^/admin, roles: ROLE_ADMIN }
    - { path: ^/, roles: ROLE_GRIMPEUR }
```

`/login`, `/register` et `/api` restent publics. Le reste exige un compte. `/admin` exige l’admin (secteurs et changement de rôle).

### Voters

Le masquage des boutons dans Twig ne suffit pas. Chaque action sensible appelle `denyAccessUnlessGranted()` (ou `isGranted` dans le template **et** dans le contrôleur).

| Voter | Attribut | Qui passe |
| --- | --- | --- |
| `BlocVoter` | `BLOC_EDIT`, `BLOC_DEMONTER` | l’admin, ou l’ouvreur propriétaire (`bloc.ouvreur === user`) |
| `AscensionVoter` | `ASCENSION_DELETE` | le grimpeur de cette ascension |

Créer ou modifier un bloc exige `ROLE_OUVREUR` (donc aussi l’admin, par hiérarchie). Gérer les secteurs et les rôles exige `ROLE_ADMIN`, déjà couvert par `access_control`.

À l’inscription, forcer `$user->setRoles(['ROLE_GRIMPEUR'])`.

---

## 5. Routes

| Méthode et chemin | Rôle | Action |
| --- | --- | --- |
| `GET/POST /register` | public | créer un grimpeur |
| `GET/POST /login`, `POST /logout` | public / connecté | session |
| `GET /blocs` | grimpeur | liste des blocs en place, filtres combinables |
| `GET /blocs/a-reussir` | grimpeur | blocs en place jamais réussis par le grimpeur connecté |
| `GET /blocs/nouveau` | ouvreur | formulaire de création |
| `POST /blocs` | ouvreur | enregistrement |
| `GET /blocs/{id}` | grimpeur | fiche, stats, formulaire d’ascension si le bloc est en place |
| `GET/POST /blocs/{id}/modifier` | voter | modification |
| `POST /blocs/{id}/demonter` | voter | renseigne `dateDemontage` |
| `POST /blocs/{id}/ascensions` | grimpeur | nouvelle réussite |
| `GET /mes-ascensions` | grimpeur | ses réussites, y compris sur blocs démontés |
| `POST /ascensions/{id}/supprimer` | voter | supprime sa réussite |
| `GET /ouvreurs/{id}` | grimpeur | fiche ouvreur : ses blocs, réussites, note moyenne |
| `GET/POST /admin/secteurs` | admin | liste et création |
| `GET/POST /admin/secteurs/{id}/modifier` | admin | modification |
| `GET/POST /admin/utilisateurs` | admin | liste et changement de rôle |
| `GET /api/blocs` | public | JSON des blocs en place |

Les suppressions et le démontage passent par un formulaire POST (jeton CSRF), jamais par un lien GET.

### JSON `GET /api/blocs`

Mêmes filtres que la liste HTML, en query string :

`/api/blocs?secteur=2&cotationMin=5+&cotationMax=6B`

La route est publique : elle expose le nombre de réussites et la note moyenne, pas les commentaires ni les noms des grimpeurs.

```json
[
  {
    "id": 12,
    "nom": "Arête jaune",
    "couleur": "jaune",
    "cotation": "6A",
    "secteur": "Dévers",
    "dateOuverture": "2026-09-12",
    "nombreReussites": 14,
    "noteMoyenne": 4.2
  }
]
```

---

## 6. Requêtes (dans les repositories)

Aucune de ces requêtes ne charge toute la table pour filtrer ensuite en PHP. QueryBuilder ou DQL.

### 1. `BlocRepository::findEnPlace(BlocFiltre)`

Blocs avec `dateDemontage IS NULL`. Chaque filtre présent ajoute un `andWhere` :

- secteur : `b.secteur = :secteur`
- cotation min : `b.cotation >= :min`
- cotation max : `b.cotation <= :max`

Les paramètres de cotation sont les cases de l’enum, pas le libellé.

### 2. `BlocRepository::findEnPlaceNonReussisPar(User)`

Blocs en place pour lesquels il n’existe aucune ascension de ce grimpeur (`NOT EXISTS` sur `Ascension`).

### 3. `BlocRepository::findEnPlaceSansReussiteRecente(\DateTimeImmutable $depuis)`

Blocs en place, ouverts depuis au moins cette date, sans ascension dont `dateReussite >= $depuis`. Pour « deux semaines », `$depuis = aujourd’hui - 14 jours`. C’est cette requête que le scheduler du lundi matin appelle.

### 4. `BlocRepository::findByOuvreur(User)`

Les blocs de cet ouvreur (en place et démontés), triés par date d’ouverture. La fiche affiche `nombreReussites` et `noteMoyenne` déjà stockés sur le bloc.

### Calcul, réservé au worker

`AscensionRepository::agregerPourBloc(Bloc)` :

```sql
SELECT COUNT(a.id), AVG(a.note) WHERE a.bloc = :bloc
```

Le handler écrit le résultat sur le bloc. S’il n’y a aucune ligne, `nombreReussites = 0` et `noteMoyenne = null`. La fiche HTTP lit les colonnes : elle ne relance pas cet agrégat.

---

## 7. Messenger

```mermaid
sequenceDiagram
    participant C as Contrôleur
    participant DB as Base
    participant Q as messenger_messages
    participant W as Worker
    C->>DB: INSERT ou DELETE ascension, flush
    C->>Q: RecalculerStatistiquesBloc(blocId)
    Note over C,Q: Worker arrêté : les colonnes du bloc ne bougent pas
    W->>Q: réservé le message
    W->>DB: COUNT / AVG
    W->>DB: UPDATE bloc.nombre_reussites, note_moyenne
```

Le message transporte **l’id** du bloc, pas l’entité. Le handler recharge le bloc.

Envoi **après** le `flush()` de l’ascension, dans le contrôleur de création et dans celui de suppression. Le worker voit alors la ligne déjà commitée. Recalculer depuis zéro rend un message en double sans effet.

Transport : Doctrine. DSN : `doctrine://default`. La file est la table `messenger_messages`.

```bash
php bin/console messenger:consume async -vv
```

Worker arrêté : créer une ascension, la fiche garde les anciens chiffres, et une ligne apparaît dans `messenger_messages`. Worker lancé : la ligne part, les chiffres se mettent à jour.

---

## 8. Commande `app:blocs:demontage`

Critère différent de la requête 3 : ici on parle de l’âge du bloc, pas de l’absence de réussite.

- Blocs en place dont `dateOuverture` date de plus de N semaines.
- Option `--semaines`, défaut `6`.
- Affichage dans un tableau (nom, secteur, cotation, date d’ouverture).
- Confirmation SymfonyStyle, puis renseignement de `dateDemontage` et `flush`.
- `--dry-run` : le tableau seulement, aucun `flush`.

---

## 9. Bonus scheduler

Chaque lundi à 08:00 (`0 8 * * 1`, fuseau `Europe/Paris`), un message appelle la requête 3 et écrit la liste dans les logs ou dans `var/blocs-a-demonter.log`.

Le worker doit alors écouter aussi le planning :

```bash
php bin/console messenger:consume async scheduler_default -vv
```

---

## 10. Fixtures

Un seul `AppFixtures`, dans cet ordre : utilisateurs, secteurs, blocs, ascensions. Mots de passe hashés avec `UserPasswordHasherInterface`.

| Compte | Email | Mot de passe | Rôle stocké |
| --- | --- | --- | --- |
| 1 admin | `admin@lareglette.test` | `Admin1234!` | `ROLE_ADMIN` |
| 3 ouvreurs | `ouvreur1@lareglette.test` … `ouvreur3@` | `Ouvreur1234!` | `ROLE_OUVREUR` |
| 20 grimpeurs | `grimpeur01@lareglette.test` … `grimpeur20@` | `Grimpeur1234!` | `ROLE_GRIMPEUR` |

Jeu minimum :

- 4 secteurs
- 40 blocs sur les deux derniers mois, toutes les cotations représentées, une dizaine déjà démontés (`dateDemontage` renseignée, postérieure à l’ouverture)
- environ 300 ascensions
- dates comprises entre l’ouverture du bloc et le minimum entre aujourd’hui et la date de démontage
- les cotations basses réussies plus souvent que les hautes
- au moins un bloc en place ouvert depuis plus de 14 jours sans réussite récente (requête 3 non vide)
- chaque ouvreur a des blocs, et il reste des blocs en place qu’un grimpeur donné n’a pas faits

Ces identifiants sont recopiés dans le README.

---

## 11. Commandes, dans l’ordre

Toutes les commandes `php bin/console` se lancent à la racine du projet Symfony, une fois le squelette créé. Le dossier `La-Reglette` contient déjà ce fichier : le CLI refuse un dossier non vide, d’où l’étape 0.

### 0. Créer le projet Symfony 7.4

Avec le CLI Symfony :

Depuis le dossier parent du projet :

```bash
symfony new --webapp --version="7.4.*" la-reglette-symfony
rsync -a la-reglette-symfony/ La-Reglette/
rm -rf la-reglette-symfony
cd La-Reglette
php -v
symfony check:requirements
```

Sans le CLI :

Depuis le dossier parent du projet :

```bash
composer create-project symfony/skeleton:"7.4.*" la-reglette-symfony
cd la-reglette-symfony
composer require webapp
cd ..
rsync -a la-reglette-symfony/ La-Reglette/
rm -rf la-reglette-symfony
cd La-Reglette
```

`rsync` dans `La-Reglette/` conserve `Projet_Symfony.md` et `ARCHITECTURE.md`.

### 1. Paquets

Le pack `webapp` apporte Twig, formulaires, validateur et sécurité. Doctrine, Messenger, le scheduler et les fixtures s’ajoutent ensuite.

```bash
composer require symfony/orm-pack
composer require symfony/messenger
composer require symfony/doctrine-messenger
composer require symfony/scheduler
composer require --dev symfony/maker-bundle
composer require --dev doctrine/doctrine-fixtures-bundle
```

Vérifier les commandes Maker réellement installées :

```bash
php bin/console list make
```

La vérification d’adresse email est hors sujet : ne pas installer `symfonycasts/verify-email-bundle`.

### 2. Base de données

Dans `.env`, une base MySQL (le transport Doctrine de Messenger y est plus simple qu’avec SQLite) :

```dotenv
DATABASE_URL="mysql://root:root@127.0.0.1:3306/lareglette?serverVersion=8.0&charset=utf8mb4"
```

Adapter utilisateur, mot de passe et version du serveur. Puis créer la base vide. Les tables viendront de la migration, plus bas, une fois les entités terminées.

```bash
php bin/console doctrine:database:create
```

### 3. Utilisateur et authentification

```bash
php bin/console make:user
```

Réponses :

- classe : `User`
- stocker en base : `yes`
- identifiant affiché : `email`
- hasher le mot de passe : `yes`

Puis les champs d’affichage :

```bash
php bin/console make:entity User
```

- `prenom`, `string`, `255`, nullable `no`
- `nom`, `string`, `255`, nullable `no`
- Entrée vide pour terminer

Sur la classe `User`, ajouter le nom de table :

```php
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'utilisateur')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
```

Formulaire de connexion, puis inscription :

```bash
php bin/console make:security:form-login
php bin/console make:registration-form
```

Pour l’inscription : classe `User`, champ `email`, **refuser** l’email de vérification, accepter `UniqueEntity` sur l’email. Après génération, dans `RegistrationController`, poser le rôle :

```php
$user->setRoles(['ROLE_GRIMPEUR']);
```

Compléter `config/packages/security.yaml` avec la hiérarchie et l’`access_control` de la section 4. Le firewall `main` doit avoir `form_login`, `logout`, et `lazy: true` (ou l’équivalent généré).

Contrôle manuel du hash si besoin :

```bash
php bin/console security:hash-password
```

### 4. Enums (pas des tables)

Créer à la main, avant les entités :

- `src/Enum/Cotation.php` — `enum Cotation: int`, rangs 1 à 18, méthode `label()`
- `src/Enum/CouleurPrise.php` — `enum CouleurPrise: string`

### 5. Entité `Secteur`

```bash
php bin/console make:entity Secteur
```

- `nom`, `string`, `255`, nullable `no`
- Entrée vide pour terminer

Puis, sur la propriété, ajouter l’unicité :

```php
#[ORM\Column(length: 255, unique: true)]
private ?string $nom = null;
```

### 6. Entité `Bloc` — champs

```bash
php bin/console make:entity Bloc
```

| Propriété | Type répondu à Maker | Nullable |
| --- | --- | --- |
| `nom` | `string`, longueur 255 | no |
| `couleur` | `enum`, classe `App\Enum\CouleurPrise`, plusieurs valeurs : no | no |
| `cotation` | `enum`, classe `App\Enum\Cotation`, plusieurs valeurs : no | no |
| `dateOuverture` | `datetime_immutable` | no |
| `dateDemontage` | `datetime_immutable` | yes |
| `nombreReussites` | `integer` | no |
| `noteMoyenne` | `float` | yes |

Entrée vide pour terminer. Dans l’entité, défaut PHP :

```php
private int $nombreReussites = 0;
```

Et la colonne :

```php
#[ORM\Column(options: ['default' => 0])]
private int $nombreReussites = 0;
```

Contrôler le mapping de `cotation` (section 1). S’il est en `string`, le passer sur la forme entier de l’enum.

### 7. Relations de `Bloc`

Les deux entités cibles existent déjà.

```bash
php bin/console make:entity Bloc
```

Propriété `secteur` :

- type : `ManyToOne` (ou `relation`, puis `ManyToOne`)
- classe : `Secteur`
- nullable : `no`
- propriété inverse sur `Secteur` : `yes`
- nom du champ inverse : `blocs`
- `orphanRemoval` : `no`

Propriété `ouvreur` :

- type : `ManyToOne`
- classe : `User`
- nullable : `no`
- propriété inverse : `yes`
- nom du champ inverse : `blocsOuverts`
- `orphanRemoval` : `no`

Entrée vide pour terminer.

Ajouter les index sur la classe `Bloc` :

```php
#[ORM\Index(columns: ['date_demontage', 'cotation'])]
#[ORM\Index(columns: ['secteur_id'])]
#[ORM\Index(columns: ['ouvreur_id'])]
```

(`secteur_id` et `ouvreur_id` sont les noms de colonnes par défaut, stratégie underscore.)

### 8. Entité `Ascension`

```bash
php bin/console make:entity Ascension
```

| Propriété | Type | Nullable |
| --- | --- | --- |
| `dateReussite` | `datetime_immutable` | no |
| `note` | `integer` | no |
| `commentaire` | `text` | yes |

Puis les relations, dans la même commande ou une seconde :

Propriété `grimpeur` :

- `ManyToOne` vers `User`, nullable `no`
- inverse : `yes`, champ `ascensions`
- `orphanRemoval` : `no`

Propriété `bloc` :

- `ManyToOne` vers `Bloc`, nullable `no`
- inverse : `yes`, champ `ascensions`
- `orphanRemoval` : `no`

Index :

```php
#[ORM\Index(columns: ['bloc_id', 'date_reussite'])]
#[ORM\Index(columns: ['grimpeur_id', 'bloc_id'])]
```

Contraintes de validation sur l’entité (ou dans le formulaire) :

```php
#[Assert\Range(min: 1, max: 5)]
private ?int $note = null;
```

La date (pas dans le futur, pas avant l’ouverture) et le refus d’un bloc démonté se vérifient dans le contrôleur ou dans un callback du formulaire `AscensionType`, qui reçoit le bloc en option. Maker peut générer le squelette de contrainte :

```bash
php bin/console make:validator DateAscensionValide
```

### 9. Migration : création des tables métier

À lancer **une fois** que `User`, `Secteur`, `Bloc` et `Ascension` et leurs relations sont en place. Une seule migration crée tout le schéma.

```bash
php bin/console make:migration
```

Ouvrir le fichier généré dans `migrations/` et vérifier :

- tables `utilisateur`, `secteur`, `bloc`, `ascension`
- `utilisateur.email` unique
- `secteur.nom` unique
- `bloc.cotation` entier
- `bloc.date_demontage` et `bloc.note_moyenne` nullable
- `bloc.nombre_reussites` défaut 0
- clés étrangères `secteur_id`, `ouvreur_id`, `grimpeur_id`, `bloc_id`

Puis appliquer :

```bash
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
```

`schema:validate` doit répondre que le mapping et la base sont synchronisés. En cas d’écart : corriger l’entité, `make:migration`, puis `doctrine:migrations:migrate` à nouveau.

État des migrations :

```bash
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:list
```

Revenir en arrière (développement seulement) :

```bash
php bin/console doctrine:migrations:migrate prev
```

### 10. Table de la file Messenger

Le recipe a normalement posé dans `.env` :

```dotenv
MESSENGER_TRANSPORT_DSN=doctrine://default
```

Si la valeur est `amqp://` ou `redis://`, la remplacer par `doctrine://default`.

Créer explicitement `messenger_messages` :

```bash
php bin/console messenger:setup-transports
```

Avec `auto_setup: true` (défaut du transport Doctrine), la table serait aussi créée au premier message. La commande ci-dessus la crée tout de suite, ce qui permet de la voir avant le premier essai.

Vérifier le routage après `make:message` (étape 14) :

```bash
php bin/console debug:messenger
```

`App\Message\RecalculerStatistiquesBloc` doit pointer vers le transport `async`.

### 11. Voters, formulaires, contrôleurs

```bash
php bin/console make:voter BlocVoter
php bin/console make:voter AscensionVoter

php bin/console make:form BlocType
php bin/console make:form AscensionType
php bin/console make:form SecteurType
php bin/console make:form UserRoleType
php bin/console make:form BlocFilterType
```

Pour chaque `make:form`, lier le type à l’entité quand Maker le demande (`Bloc`, `Ascension`, `Secteur`, `User`). `BlocFilterType` et `UserRoleType` peuvent rester sans entité Doctrine : le premier mappe `BlocFiltre`, le second un choix de rôle.

`BlocType` : nom, couleur, cotation, secteur, date d’ouverture. L’ouvreur connecté est posé dans le contrôleur, pas choisi dans le formulaire. `dateDemontage`, `nombreReussites` et `noteMoyenne` sont absents de ce formulaire.

```bash
php bin/console make:controller BlocController
php bin/console make:controller AscensionController
php bin/console make:controller OuvreurController
php bin/console make:controller Admin/SecteurController
php bin/console make:controller Admin/UserController
php bin/console make:controller Api/BlocApiController
```

Maker crée aussi un template par contrôleur. Les remplacer par les vrais écrans Twig (`bloc/index.html.twig`, `bloc/show.html.twig`, `bloc/form.html.twig`, etc.).

### 12. Message et handler

```bash
php bin/console make:message RecalculerStatistiquesBloc
```

À la question du transport, choisir `async`.

Fichiers produits :

- `src/Message/RecalculerStatistiquesBloc.php`
- `src/MessageHandler/RecalculerStatistiquesBlocHandler.php`
- routage dans `config/packages/messenger.yaml`

Le message expose `getBlocId(): int`. Le handler charge le bloc, appelle `AscensionRepository::agregerPourBloc()`, met à jour les deux colonnes, puis `flush`.

### 13. Commande de démontage

```bash
php bin/console make:command app:blocs:demontage
```

Si Maker demande les options, déclarer :

- `--semaines`, mode `OPTION_OPTIONAL` ou `VALUE_REQUIRED`, défaut applicatif `6`
- `--dry-run`, mode `VALUE_NONE` (flag)

Le nom de classe généré est en général `DemontageBlocsCommand`.

```bash
php bin/console app:blocs:demontage
php bin/console app:blocs:demontage --semaines=8
php bin/console app:blocs:demontage --dry-run
php bin/console list app
```

### 14. Scheduler (bonus)

```bash
php bin/console make:schedule
```

Dans le provider généré, un message récurrent le lundi à 8 h :

```php
RecurringMessage::cron('0 8 * * 1', new ListerBlocsADemonter(), new \DateTimeZone('Europe/Paris'))
```

`ListerBlocsADemonter` est un second message (et son handler), créé avec :

```bash
php bin/console make:message ListerBlocsADemonter
```

Pour celui-ci, transport : aucun transport async. Le scheduler le remet lui-même au bus. Le handler appelle `findEnPlaceSansReussiteRecente()` et écrit le résultat dans le logger.

```bash
php bin/console debug:scheduler
```

### 15. Fixtures

```bash
php bin/console make:fixtures AppFixtures
```

Puis charger (la commande vide la base métier avant d’insérer : à utiliser en développement) :

```bash
php bin/console doctrine:fixtures:load
```

Sans la question de confirmation :

```bash
php bin/console doctrine:fixtures:load --no-interaction
```

### 16. Lancer l’application et le worker

```bash
symfony server:start -d
# ou, sans le CLI :
php -S localhost:8000 -t public

php bin/console messenger:consume async scheduler_default -vv
```

Arrêter le worker : Ctrl+C dans son terminal. Le site continue de répondre ; les statistiques restent alors figées jusqu’au prochain `messenger:consume`.

---

## 12. Commandes utiles ensuite

```bash
php bin/console debug:router
php bin/console debug:container --env-var=DATABASE_URL
php bin/console debug:messenger
php bin/console debug:firewall
php bin/console lint:container
php bin/console lint:twig templates
php bin/console lint:yaml config
php bin/console doctrine:schema:validate
php bin/console cache:clear
```

Régénérer getters et setters après un champ ajouté à la main :

```bash
php bin/console make:entity --regenerate App\\Entity\\Bloc
```

Pour écraser aussi les méthodes déjà présentes :

```bash
php bin/console make:entity --regenerate --overwrite App\\Entity\\Bloc
```

Nouvelle modification de schéma, plus tard :

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

Voir le SQL sans l’appliquer :

```bash
php bin/console doctrine:schema:update --dump-sql
```

`doctrine:schema:update --force` est à éviter : les migrations restent la source du schéma rendu avec le projet.

---

## 13. Ordre de réalisation conseillé

1. Squelette, base, utilisateur, login, inscription, hiérarchie des rôles.
2. Enums, entités, **une** migration, `schema:validate`.
3. Fixtures minimales (1 admin, 1 ouvreur, 1 grimpeur, 1 secteur, 2 blocs) pour développer.
4. Liste filtrée et fiche bloc.
5. CRUD bloc et secteurs, avec voters.
6. Ascensions (création, liste « à réussir », « mes ascensions », suppression).
7. Messenger et colonnes de statistiques. Vérifier worker arrêté, puis worker lancé.
8. `GET /api/blocs`.
9. Changement de rôle admin.
10. Commande `app:blocs:demontage`.
11. Fixtures complètes (20 grimpeurs, 40 blocs, ~300 ascensions) et contrôle que les 4 requêtes renvoient quelque chose.
12. Scheduler du lundi, si le temps le permet.
13. README : identifiants, `composer install`, migration, fixtures, lancement du worker.

Le ZIP de rendu exclut `vendor/` (et en pratique `.env.local`, `var/`). Le correcteur refait `composer install`, puis les commandes des étapes 2, 9 et 15.
