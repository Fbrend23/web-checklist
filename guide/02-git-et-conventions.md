# 02 - Git et conventions de code

## Pourquoi
Un historique lisible et un style de code uniforme permettent de reprendre un projet six mois plus tard, ou de le passer à quelqu'un d'autre, sans rien deviner.

## Règles
- **Deux modèles de branches, selon la taille** :
  - petit projet seul : `dev` pour travailler, `main` déployé, fusion en fast-forward (`git merge --ff-only dev`). Pousser `dev` lance les vérifications, pousser `main` déploie.
  - projet d'équipe : gitflow (`main`, `develop`, `feature/issue-X-slug`), PR obligatoires, branche tampon pour un chantier qui s'étale sur plusieurs PR.
- **Commits** : Conventional Commits, un sujet par commit, mode impératif, en français si le projet est en français.
  - format : `type: phrase` (`feat`, `fix`, `chore`, `ci`, `docs`, `refactor`, `test`) ou `type(portée) description`.
  - le corps explique **pourquoi**, pas ce que le diff montre déjà.
- **Jamais de secret commité** : `.env` dans `.gitignore`, `.env.example` commité avec des valeurs factices.
- **Un fichier de contexte pour l'assistant IA** à la racine (sources de vérité, règles à ne jamais casser, commandes de test, conventions git). C'est aussi une excellente doc pour un humain. Modèle prêt à copier : [`templates/AGENTS.md`](templates/AGENTS.md). Le garder court (moins de 200 lignes, une règle par ligne).
- **Formatage automatique** : `.editorconfig`, Prettier, ESLint (flat config). La CI **signale**, elle ne réécrit pas (`eslint .` sans `--fix`).
- **Hooks de pré-commit** (Husky + lint-staged) pour les projets d'équipe : le lint tourne avant que l'erreur n'arrive en CI.
- **Protéger `main`** : CI verte obligatoire avant fusion, pas de push direct (sauf projet solo assumé).
- **Releases** : tags versionnés (SemVer), changelog (release-please ou `CHANGELOG.md`).

## Checklist
- [ ] `.gitignore` contient `.env`, `node_modules`, `dist`, dossiers de l'éditeur.
- [ ] `.env.example` commité et à jour.
- [ ] Convention de commits écrite dans le fichier de contexte du projet.
- [ ] Branches `dev` / `main` (ou gitflow) en place, `main` protégée si équipe.
- [ ] `.editorconfig` + Prettier + ESLint configurés, scripts `lint` et `format` dans `package.json`.
- [ ] La CI lance le lint sans le corriger.
- [ ] (Équipe) Husky + lint-staged installés.
- [ ] Fichier de contexte IA avec : sources de vérité, règles à ne jamais casser, commandes, conventions git.
- [ ] Historique git vérifié : aucun secret jamais commité (voir 05).

## Exemples de mes projets
`Wiki-ETML/.prettierrc.json` :
```json
{ "semi": false, "singleQuote": true, "printWidth": 100 }
```
`Wiki-ETML/.editorconfig` : 2 espaces, fins de ligne LF, UTF-8, ligne finale, 100 colonnes max.

`Portail/.github/workflows/deploy.yml` : la CI lance `npx eslint .` sans `--fix`, avec le commentaire « la CI signale, elle ne réécrit pas ».

Messages de commit qui expliquent le pourquoi :
```
fix: Envoyer .htaccess à part après comparaison

Un miroir parallèle avait écrit le contenu de 404.html dans .htaccess
et fait tomber tout le site en erreur 500.
```

Structure d'un bon fichier de contexte (Cliffhanger-) : sources de vérité, « Règles à ne jamais casser », architecture, comment tester (avec de vraies données, sans jamais soumettre un formulaire qui crée une vraie demande), conventions git.

## Commandes et outils
```bash
git switch -c dev                      # créer la branche de travail
git merge --ff-only dev                # fusion propre dans main (depuis main)
git log --oneline --graph -20          # relire l'historique
git config core.autocrlf true          # Windows : évite les warnings LF/CRLF
npx husky init                         # hooks de pré-commit
npm i -D lint-staged prettier eslint
npx prettier --check .                 # vérifier le format sans rien changer
```
- Spécification des commits : https://www.conventionalcommits.org/fr
- Versionnage sémantique : https://semver.org/lang/fr
- Release automatique : https://github.com/googleapis/release-please

## Pièges vécus
- Les avertissements « LF will be replaced by CRLF » sont normaux sous Windows, on ne les « corrige » pas à la main.
- Un `.env` suivi par git dans un dossier d'exercice (183-SecuriteApplication) : même des identifiants de labo ne se commitent pas.
- Un commit qui mélange plusieurs sujets rend le retour arrière impossible : un sujet par commit.
