<!-- Modèle de fichier de contexte IA. Copier à la racine du projet (renommer selon l'outil, ex. CLAUDE.md). Rester sous 200 lignes : une règle par ligne. -->
# Règles du projet

## Sources de vérité
- Cahier des charges : `<à remplir>`
- Doc technique : `<à remplir>`
- Checklist : `doc/CHECKLIST.md`

## Commandes
- Installer : `npm ci`
- Tester : `npm test`
- Vérifier : `npm run lint` puis `npm run check`
- Construire : `npm run build`

## Règles à ne jamais casser
- Aucun secret dans le code ni dans le bundle client, seulement dans `.env` (ignoré) et les secrets de CI.
- Valider toute entrée côté serveur avec un schéma.
- SQL paramétré uniquement, jamais de concaténation.
- Pas de sortie HTML brute (`v-html`, `set:html`, `innerHTML`) sans échappement.
- JSON dans `<script>` (JSON-LD) : échapper `<` en `<`.
- Autorisation vérifiée côté serveur, jamais seulement dans l'interface.
- Moindre privilège : un jeton, un compte, un droit par usage.
- Aucun `console.log` ni stack trace en production.
- Un bug corrigé reçoit un test.
- Rien ne doit réussir en silence : une variable ou une source manquante fait échouer le build.
- Code lisible : noms explicites, fonctions courtes, pas de code mort, commentaire = pourquoi (guide 13).

## Conventions git
- Commits conventionnels en français : `type: phrase`, le corps explique pourquoi.
- Un sujet par commit.
- Aucune ligne d'attribution, aucun tiret long dans les textes visibles.
- Travail sur `dev`, fusion dans `main` en fast-forward, `main` déploie.

## Avant de dire « terminé »
- Lint, types et tests passent.
- Le build fonctionne.
- Les cases concernées de `doc/CHECKLIST.md` sont cochées.
