# 08 - CI/CD et déploiement

## Pourquoi
La CI refuse de déployer du code cassé, le déploiement est reproductible et ne peut pas détruire un site par erreur. Un déploiement doit être ennuyeux.

## Règles

### Structure du pipeline
- **Deux jobs** : `verifier` (lint, types, tests, build, e2e) puis `deployer` avec `needs: verifier`.
- **`permissions: contents: read`** au niveau du workflow, on n'élargit que là où c'est nécessaire.
- **`concurrency`** : un seul déploiement à la fois. `cancel-in-progress: true` pour les vérifications, `false` pour un déploiement (on ne coupe pas un envoi en cours).
- **`timeout-minutes`** sur chaque job (un FTP bloqué ne doit pas tourner 6 heures).
- **`dry_run`** en entrée manuelle pour simuler le déploiement.
- **Échouer vite** : première étape qui vérifie que toutes les variables et secrets requis existent.
- **Construire sans sources = refuser** : si le build retombe silencieusement sur des données locales à cause d'un secret manquant, on publie un faux site. Mieux vaut échouer.
- **Épingler** les versions d'outils (Supabase CLI `2.105.0`, pas `latest`) et les actions par SHA.
- **Lire Node depuis `.nvmrc`**, `npm ci` et cache npm.
- **Linter les workflows eux-mêmes** : actionlint, shellcheck.

### Déploiement FTP sans risque
- **Marqueur de déploiement** : un fichier vide, nommé d'après le site, doit exister à la racine FTP. Sinon le job s'arrête avant `mirror --delete` (protège contre un mauvais dossier cible).
- **Masquer le mot de passe** dans la sortie de lftp avec `sed` (le masquage de GitHub ne reconnaît pas la version encodée en URL).
- **`.htaccess` envoyé à part**, après comparaison (`cmp`). Un miroir parallèle a un jour écrit le contenu de `404.html` dans `.htaccess` et fait tomber le site en erreur 500.
- **Ordre** : d'abord les fichiers à hash (`_astro/`), puis le reste, suppressions à la fin. Une page ne référence jamais un fichier absent.
- **Manifeste** au lieu de miroir complet quand l'hébergeur limite les connexions (seulement les fichiers qui changent).
- **`build.json` envoyé seul et en dernier** : le signal « publication terminée ».
- **Commit d'archive avec `[skip ci]`** pour ne pas relancer la CI.
- **Déploiement planifié** (cron de nuit) quand le contenu dépend de la date (spectacles passés qui quittent « à venir »).

### Backend et base
- **Migrer la base d'abord**, puis les fonctions, puis le frontend.
- **Staging avant production** quand c'est possible (manuel au début, puis automatique).

### Mises à jour
- **Dependabot** mensuel avec regroupement. Voir 05.

## Checklist
- [ ] Job `verifier` (lint, types, tests, build) avant le job `deployer`.
- [ ] `permissions: contents: read`, `concurrency`, `timeout-minutes` en place.
- [ ] Entrée manuelle `dry_run`.
- [ ] Contrôle des variables requises en première étape.
- [ ] Le build échoue s'il manque une source de contenu.
- [ ] Marqueur de déploiement présent sur le serveur et vérifié.
- [ ] Mot de passe masqué dans les journaux (`sed`).
- [ ] `.htaccess` envoyé séparément après comparaison.
- [ ] `build.json` envoyé en dernier.
- [ ] Actions et CLI épinglées, Node lu depuis `.nvmrc`.
- [ ] Workflows validés par actionlint.
- [ ] Déploiement de nuit si le contenu dépend de la date.
- [ ] Test de retour arrière : savoir comment revenir à la version précédente.

## Exemples de mes projets

Workflow complet de vérification puis déploiement (`Portail/.github/workflows/deploy.yml`) : job `verifier` (ESLint, typecheck, Vitest, build, Playwright), job `deployer` qui en dépend, `concurrency` sans annulation, `timeout-minutes`, entrée `dry_run`, garde par marqueur (variable de dépôt `DEPLOY_MARKER`).

Pipeline de contrôle (`matinale-seoul/.github/workflows/controle.yml`) : tests deux fois (`TZ` Séoul puis UTC), `astro check`, rendu contre un faux CMS, actionlint (`docker://rhysd/actionlint:latest`) et `shellcheck bin/brief-du-jour.sh`.

Publication déclenchée par dépôt de fichier (`matinale-seoul/.github/workflows/publication.yml`) : `paths: ['inbox/**']`, noms de fichiers imposés par `^brief-\d{4}-\d{2}-\d{2}\.json$`, `.htaccess` à part, `build.json` en dernier, commit d'archive `[skip ci]`.

Déploiement par manifeste (`Cliffhanger-/scripts/deployer.mjs`) : `_astro/` d'abord, suppressions en dernier, parce que l'hébergeur limitait le nombre de connexions. Déclencheurs : push, bouton « Mettre en ligne » du CMS, cron de nuit. Après envoi, la date `last_published` est écrite dans le CMS, sans faire échouer le run si le CMS refuse.

Étape de refus de build sans sources (`Maisallezfieu/.github/workflows/deploy.yml`).

Pipeline base de données (`Prodysos/.github/workflows/db-tests.yml`) : filtré par chemins, CLI Supabase épinglée à `2.105.0` (commentaire : `latest` atteint la limite de l'API GitHub), valeurs OAuth factices pour que GoTrue démarre.

Étapes et réglages du pipeline Node simple (`Wiki-ETML/.github/workflows/deploy.yaml`) : `actions/setup-node` avec cache npm, `actions/cache` clé sur `hashFiles(...)`, `peter-evans/create-pull-request` pour déposer les fichiers générés sans pousser sur une branche protégée, `SamKirkland/FTP-Deploy-Action` avec `protocol: ftps`.

Analyse statique (`pm2etml-apps/.github/workflows/build.yml`) :
```yaml
- uses: actions/checkout@v4
  with:
    fetch-depth: 0
- uses: SonarSource/sonarqube-scan-action@v6
  env:
    SONAR_TOKEN: ${{ secrets.SONAR_TOKEN }}
```

## Commandes et outils
```bash
gh workflow run deploy.yml -f dry_run=true     # lancer à la main en simulation
gh run watch                                   # suivre un run
gh run list --limit 5
actionlint                                     # valider les workflows (https://github.com/rhysd/actionlint)
shellcheck bin/*.sh                            # valider les scripts shell
lftp -e "mirror -R --delete --dry-run dist/ /" ftps://utilisateur@hote   # simuler un miroir
```
- Documentation GitHub Actions : https://docs.github.com/fr/actions
- Cache et artefacts : https://github.com/actions/cache

## Pièges vécus
- **Un miroir parallèle a écrit `404.html` dans `.htaccess`** (23 septembre 2026) : le site entier est tombé en 500. D'où l'envoi séparé avec `cmp`.
- **`supabase version: latest` dans les workflows de déploiement de Prodysos** alors que le test de base est épinglé : incohérent, à épingler partout.
- **`lint` avec `continue-on-error: true`** (un projet perso) : ne bloque jamais, finit ignoré.
- **SonarQube** : activer « analyse automatique » dans les réglages du projet, première analyse via une PR.
- **Pas de bloc `permissions`** dans le déploiement de MIS : ajouter `contents: read`.
- **Pas de `public/.htaccess` dans Prodysos** (il écraserait le bloc de protection de l'hébergeur en préproduction) : lire `docs/deploy/htaccess.md`.
