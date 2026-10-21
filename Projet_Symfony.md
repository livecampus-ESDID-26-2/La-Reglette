# Projet Symfony

contact@ls-a.fr  
https://www.ls-a.fr

## Table des matières

- A. Contexte — p. 1
- B. L'escalade de bloc en quelques mots — p. 1
- C. Les utilisateurs de l'application — p. 1
- D. Fonctionnalités — p. 2
- E. Focus sur les fonctionnalités — p. 2
  - i. Authentification — p. 2
  - ii. Modèle de données — p. 3
  - iii. Blocs — p. 3
  - iv. Ascensions — p. 3
  - v. Requêtes — p. 4
  - vi. Écran de la salle (API JSON) — p. 4
  - vii. Autorisations — p. 4
  - viii. Tâches de fond — p. 4
    - Statistiques asynchrones avec Messenger — p. 4
    - Commande de démontage — p. 5
    - Bonus : Scheduler — p. 5
  - ix. Jeu de données — p. 5
- F. Modalités de réalisation et de rendu — p. 6

## A. Contexte

Vous êtes amené(e) à développer l'application interne de La Réglette, une salle d'escalade de bloc qui ouvre son troisième site. Jusqu'ici, tout était géré sur un tableau blanc à l'accueil et dans un fichier Excel partagé, qui ne suffit plus.

L'application permettra aux pratiquants de suivre leur progression, à l'équipe de gérer les blocs en place sur les murs, et à l'accueil d'afficher des informations sur l'écran de la salle. Votre direction vous demande, pour faciliter la maintenance et l'évolutivité, de réaliser ce projet avec minimum Symfony 7.4 (LTS).

## B. L'escalade de bloc en quelques mots

Voici le vocabulaire que vous croiserez dans le sujet.

- **Bloc** : un court itinéraire d'escalade (3 à 4.5 m de haut). Un bloc est défini par un ensemble de prises d'une même couleur, un point de départ et une prise d'arrivée.
- **Prise** : l'objet vissé au mur sur lequel on pose les mains et les pieds.
- **Secteur** : une zone de la salle, un pan de mur, un dévers... Cela peut aussi être "A", "B" et "C", l'important est de pouvoir visualiser à quel endroit se trouve chaque bloc. Chaque bloc appartient à un secteur.
- **Cotation** : la difficulté d'un bloc, sur l'échelle utilisée en France : de 3 (très facile) à 8A (très difficile), en passant par 4, 5, 5+, 6A, 6A+, 6B… Chaque niveau entre 6A et 8A existe en version simple et « + ».
- **Ouverture** : la création d'un bloc par la pose de ses prises. À l'inverse, le démontage consiste à retirer les prises d'un bloc pour libérer le mur. Un bloc reste en général en place quelques semaines.
- **Réussir un bloc** : le grimper du départ à l'arrivée sans tomber.

## C. Les utilisateurs de l'application

Il y a trois rôles dans l'application :

1. **Grimpeur** : un client de la salle, abonné ou occasionnel.
2. **Ouvreur** : un salarié chargé de concevoir et poser les blocs. C'est aussi un grimpeur.
3. **Admin** : gère les secteurs et les comptes, et peut tout modifier.

## D. Fonctionnalités

L'application devra posséder les fonctionnalités suivantes.

### Authentification

- Créer un compte grimpeur
- Se connecter
- Se déconnecter

### Gestion des blocs (ouvreur et admin)

- Créer un bloc dans un secteur
- Modifier un bloc
- Démonter un bloc
- Gérer les secteurs (admin uniquement)

### Consultation (tout utilisateur connecté)

- Lister les blocs en place, avec des filtres combinables
- Consulter la fiche d'un bloc et ses statistiques

### Ascensions (grimpeur)

- Enregistrer la réussite d'un bloc
- Consulter les blocs en place qu'on n'a pas encore réussis
- Supprimer ses ascensions

### Ecran de la salle

- Exposer les blocs et le nombre de réussites en JSON pour l'écran de l'accueil

### Autorisations

- Changer le rôle d'un utilisateur (admin uniquement)

### Tâches de fond

- Recalculer les statistiques d'un bloc de façon asynchrone
- Lister ou démonter les « vieux » blocs en ligne de commande

Toute autre fonctionnalité supplémentaire, si pertinente, est souhaitée.

## E. Focus sur les fonctionnalités

### i. Authentification

Toute personne qui crée un compte depuis le formulaire d'inscription obtient le rôle Grimpeur. Les ouvreurs et l'admin sont créés par le jeu de données.

Les rôles sont hiérarchiques : un ouvreur peut faire tout ce que fait un grimpeur, et l'admin tout ce que fait un ouvreur. Pour ce projet, il n'est pas demandé de valider l'adresse mail.

Pour vous faciliter la tâche, vous pourriez utiliser ces ressources :

- Les commandes `make:user`, `make:security:form-login` et `make:registration-form` du MakerBundle
- La hiérarchie des rôles : [symfony.com/doc/current/security.html#hierarchical-roles](https://symfony.com/doc/current/security.html#hierarchical-roles)

### ii. Modèle de données

Prenez bien le temps de définir vos entités avant de coder. Le modèle attendu comporte au minimum : utilisateur, secteur, bloc et ascension.

- Un bloc appartient à un secteur et a été ouvert par un ouvreur.
- Une ascension relie un grimpeur à un bloc, et porte ses propres informations : date de réussite, note du bloc de 1 à 5, commentaire (facultatif). On peut réussir plusieurs fois l'ascension sur un même bloc.

L'ascension doit être une entité à part entière et non une simple relation ManyToMany entre utilisateur et bloc.

La cotation se prête bien à un enum PHP. Pensez à la façon dont vous allez la stocker : vous aurez besoin de comparer ces éléments.

### iii. Blocs

Un bloc possède un nom, une couleur de prises, une cotation officielle, une date d'ouverture et une date de démontage (vide tant qu'il est en place).

- Démonter un bloc ne le supprime pas : on renseigne sa date de démontage, et on conserve son historique d'ascensions.
- Un bloc démonté n'apparaît plus dans la liste des blocs en place, mais reste visible pour les grimpeurs qui l'ont réussi.
- La liste des blocs se filtre par secteur et par plage de cotation (par exemple de 5+ à 6B). Les filtres sont facultatifs et combinables.

La fiche d'un bloc affiche ses statistiques : nombre de réussites et note moyenne.

### iv. Ascensions

Un grimpeur enregistre une ascension depuis la fiche d'un bloc, via un formulaire.

- On ne peut pas enregistrer l'ascension d'un bloc démonté.
- La date ne peut être ni dans le futur, ni antérieure à l'ouverture du bloc.

### v. Requêtes

Les requêtes suivantes doivent être écrites dans les Repository, avec le QueryBuilder ou en DQL. Aucune ne doit charger toutes les entités pour filtrer ou compter en PHP.

1. La liste filtrée des blocs en place (filtres de la section Blocs), construite dynamiquement avec le QueryBuilder.
2. Les blocs en place que le grimpeur connecté n'a pas encore réussis.
3. Les blocs en place sans aucune réussite depuis au moins deux semaines.
4. Pour la fiche d'un ouvreur (son profil) : ses blocs, avec leur nombre de réussites et leur note moyenne.

### vi. Écran de la salle (API JSON)

L'écran de l'accueil est piloté par une autre équipe, qui consommera une route JSON. Elle est publique, sans authentification.

`GET /api/blocs` : les blocs en place avec leurs réussites, avec les mêmes filtres que la liste HTML passés en paramètres de requête.

### vii. Autorisations

En plus des rôles, certaines règles dépendent de la ressource concernée :

- Un ouvreur ne peut modifier ou démonter que les blocs qu'il a ouverts. L'admin peut tout modifier.
- Un grimpeur ne peut supprimer que ses propres ascensions.
- Seul l'admin peut gérer les secteurs.

Ces règles doivent être appliquées côté serveur, pas seulement en masquant les boutons dans les templates.

Pour vous faciliter la tâche : [symfony.com/doc/current/security/voters.html](https://symfony.com/doc/current/security/voters.html).

### viii. Tâches de fond

Vous devrez réaliser au moins une des deux propositions ci-dessous.

#### Statistiques asynchrones avec Messenger

Les statistiques d'un bloc (nombre de réussites et note moyenne) sont stockées sur le bloc. Elles ne sont pas recalculées pendant la requête HTTP.

- À chaque création ou suppression d'une ascension, un message est envoyé dans une file d'attente.
- Un handler recalcule les statistiques du bloc concerné.
- Le transport utilisé est Doctrine : la file d'attente est une table de votre base.
- Les messages sont traités par un worker, lancé avec `php bin/console messenger:consume`.

Vérifiez que, worker arrêté, les statistiques ne bougent pas, puis qu'elles se mettent à jour au démarrage du worker. Ressource : [symfony.com/doc/current/messenger.html](https://symfony.com/doc/current/messenger.html).

#### Commande de démontage

L'équipe démonte régulièrement les vieux blocs. Créez une commande `app:blocs:demontage` qui :

- liste les blocs en place depuis plus de N semaines (option `--semaines`, 6 par défaut), dans un tableau ;
- les démonte après confirmation de l'utilisateur ;
- avec l'option `--dry-run`, affiche la liste sans rien modifier.

Ressource : [symfony.com/doc/current/console.html](https://symfony.com/doc/current/console.html)

#### Bonus : Scheduler

Utilisez le composant Scheduler pour générer automatiquement, chaque lundi matin, la liste des blocs à démonter (requête 3 de la section Requêtes), par exemple dans un fichier ou dans les logs. Ressource : [symfony.com/doc/current/scheduler.html](https://symfony.com/doc/current/scheduler.html)

### ix. Jeu de données

Il faut pouvoir tester toutes les fonctionnalités dès l'installation. Vous fournirez donc des fixtures (DoctrineFixturesBundle) qui créent au minimum :

- 1 admin, 3 ouvreurs et 20 grimpeurs, avec des identifiants connus et indiqués dans le README ;
- 4 secteurs ;
- 40 blocs, dont une dizaine déjà démontés, répartis sur toute l'échelle de cotations et sur les deux derniers mois ;
- 300 ascensions environ, de préférence réalistes : les dates respectent les règles de la section Ascensions, et les blocs faciles sont plus souvent réussis que les difficiles.

Le jeu de données doit permettre à chaque requête de la section Requêtes de renvoyer un résultat non vide.

## F. Modalités de réalisation et de rendu

Ce projet doit être réalisé individuellement et rendu sur Yparéo. Attention, le rendu ne doit pas être juste un lien GitHub, il faut le projet en ZIP en entier. N'oubliez pas de supprimer le dossier vendor (ou de faire un "Download as ZIP" sur GitHub) pour éviter des archives trop volumineuses.

Des points bonus pourront être octroyés si votre projet possède des fonctionnalités particulières ou implémente parfaitement les fonctionnalités demandées.

Des malus pourront être appliqués :

- En cas d'une utilisation massive de JavaScript, notamment aux dépens de Twig
- En cas de non-respect manifeste de l'architecture Symfony (MVC, ...)
- En cas de faille manifeste de sécurité
- En cas de retard

Les extensions fournies par composer, npm, autres, ne sont pas autorisées si elles permettent :

- de contourner une contrainte de ce cahier des charges ; ou
- de réaliser automatiquement une partie jugée importante du projet

Exemple : Un bundle permettant de réaliser une application de gestion de salle d'escalade avec Symfony.

Les extensions vues en cours sont évidemment autorisées. Précision de Renaud Berthier : cette phrase sert à lever le doute sur ce qui a été vu en cours. Cette semaine, la liste est courte.

Sont autorisés :

- les commandes `make` ;
- Doctrine ;
- les fonctions liées à `make:user` pour l'authentification, même si elles n'ont pas été vues en cours ;
- celle utilisée dans sakila-website pour gérer les types géolocalisés, même si le temps a manqué pour la voir en cours.
