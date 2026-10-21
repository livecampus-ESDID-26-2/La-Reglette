# La Réglette

Application interne d'une salle d'escalade de bloc, en Symfony 7.4. Les grimpeurs suivent leurs réussites, les ouvreurs gèrent les blocs, l'admin gère la salle.

## Docker

Depuis la racine du projet :

```bash
docker compose up -d --build
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
```

Le site est sur http://localhost:8000.

`doctrine:fixtures:load` vide la base puis recrée les comptes, 4 secteurs, 40 blocs et leurs réussites.

## Prettier

Prettier réécrit le code pour qu'il ait toujours la même forme : indentation, guillemets, retours à la ligne. Ici il couvre le PHP, le CSS et les templates Twig. Le CSS est géré par Prettier directement. Le PHP et le Twig passent par deux plugins, `@prettier/plugin-php` et `@zackad/prettier-plugin-twig`.

Le style est dans `.prettierrc.json` : 2 espaces, apostrophes simples, lignes jusqu'à 120 caractères. En PHP, les accolades des classes et des méthodes passent à la ligne, et la syntaxe visée est PHP 8.2.

```bash
npm install
npm run format
```

`npm run format:check` vérifie sans modifier les fichiers. Dans Cursor, avec l'extension Prettier, l'enregistrement formate le PHP, le CSS et le Twig.

Le plugin Twig ne lit pas une balise `{% %}` placée dans un attribut HTML. Pour une classe dynamique, on prépare la valeur avant la balise, comme pour `body_class` dans `templates/base.html.twig`.

## Comptes

Tous les comptes ont le même mot de passe : `Reglette2026!`

| Rôle | Email |
| --- | --- |
| Admin | admin@lareglette.test |
| Ouvreurs | ouvreur1@lareglette.test à ouvreur3@lareglette.test |
| Grimpeurs | grimpeur01@lareglette.test à grimpeur20@lareglette.test |
