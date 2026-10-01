# 07 - Tests

## Pourquoi
Un test est la seule preuve qu'une règle tient encore après un changement. On teste ce qui coûterait cher en cas de panne : logique métier, sécurité, rendu final.

## Règles
- **Pyramide** : beaucoup de tests unitaires rapides, quelques tests d'intégration, peu de tests de bout en bout (e2e) sur les parcours critiques.
- **Commencer simple** : `node --test` (zéro dépendance) suffit pour des fonctions pures. Vitest pour du Vue/TypeScript. Playwright pour l'e2e.
- **Tester le résultat construit** : pour un site statique, vérifier le `dist/` (titres uniques, canonical, JSON-LD valide, pas de tiret long, pas de lien cassé).
- **Tests de dates : deux passages de fuseau** (`TZ=Europe/Brussels` puis `TZ=UTC`), car un bug de fuseau n'apparaît qu'ailleurs que sur sa machine.
- **Pas de réseau dans les tests** : faux CMS (serveur local), variables `*_MOCK`, base en mémoire (PGlite). Un test dépendant d'un service externe est un test qui casse sans raison.
- **Tester la sécurité** : pgTAP pour la RLS, tests d'autorisation (accès sans session, avec le mauvais rôle), cas d'injection.
- **Un test qui ne peut pas échouer ne sert à rien** : un test d'intégration qui saute en silence quand la configuration manque donne un faux « tout vert ». Il doit échouer bruyamment.
- **Garder doc, schéma et code cohérents par un test** (prompt, schéma et README comparés) : la doc qui dérive est un bug.
- **La CI lance tout** (voir 08) et un test rouge bloque la fusion.
- **Régression visuelle** : `expect(page).toHaveScreenshot()` de Playwright sur les pages clés. Le meilleur filet pour un site vitrine où un bug CSS ne produit aucune erreur.
- **MSW (Mock Service Worker)** : intercepter les requêtes HTTP de façon déclarative (Vitest, Playwright, navigateur), en complément ou à la place d'un faux CMS.

## Checklist
- [ ] Script `npm test` qui fonctionne sur un clone neuf.
- [ ] Tests unitaires sur la logique métier et les fonctions de sécurité (échappement, validation, dates).
- [ ] Tests e2e sur les parcours critiques (accueil, formulaire, connexion) avec Playwright.
- [ ] Pour un site statique : test sur le `dist/` construit (SEO, JSON-LD, liens).
- [ ] Tests de dates passés en `TZ=UTC` et dans le fuseau local.
- [ ] Aucun test ne dépend d'un réseau externe.
- [ ] Tests de base de données (pgTAP) pour la RLS si Supabase.
- [ ] Tests d'autorisation : accès refusé sans session et avec le mauvais rôle.
- [ ] Test de type (`astro check`, `vue-tsc --noEmit`) lancé en CI.
- [ ] Couverture consultée (`vitest --coverage`), sans obsession du pourcentage.
- [ ] Un bug corrigé = un test ajouté.
- [ ] Captures Playwright (`toHaveScreenshot`) sur accueil et formulaire.
- [ ] Requêtes externes simulées (faux serveur ou MSW).

## Exemples de mes projets

Scripts d'un projet Vue complet (`Portail/package.json`) :
```json
"test": "vitest run",
"test:e2e": "playwright test",
"typecheck": "vue-tsc --noEmit"
```

Test e2e qui vérifie SEO et accessibilité ensemble (`Portail/e2e/smoke.spec.ts`) :
```ts
await expect(page.locator('html')).toHaveAttribute('lang', lang)
await expect(page.locator('link[rel="alternate"][hreflang="fr"]')).toHaveCount(1)
await page.keyboard.press('Escape')
await expect(page.getByRole('dialog')).toBeHidden()
await expect(toggle).toBeFocused()
```
Les tests e2e tournent contre le `dist/` construit via `vite preview`.

Test du site construit (`Cliffhanger-/test/site-construit.mjs`, `npm run test:dist`) : tirets longs dans le texte visible, titres et descriptions en double, canonical, JSON-LD analysable.

Faux CMS pour tester sans réseau : `Cliffhanger-/test/faux-directus/serveur.mjs` et `matinale-seoul/scripts/lib/faux-cms.mjs` (`npm run rendu` construit le site contre ce faux CMS et vérifie que toutes les pages se rendent).

Double passage de fuseau (`matinale-seoul/.github/workflows/controle.yml`) :
```yaml
- run: npm test
  env:
    TZ: Asia/Seoul
- run: npm test
  env:
    TZ: UTC
```

Tests sans framework (`matinale-seoul`) : `node --test tests/*.test.mjs`, environ 20 fichiers plus des jeux de données (`tests/fixtures/brief-*.json`). `tests/prompt.test.mjs` garde cohérents le prompt, le schéma et le README.

Syntaxe de scripts non importables (`platform-cms/test/syntax.test.mjs`) : lance `node --check` sur chaque script, car on ne peut pas les importer sans se connecter à l'instance.

Vraie base en mémoire (`LunchPicker/test/unit/db.spec.ts`) : PGlite exécute le vrai SQL. Le test d'intégration (`vitest.integration.config.ts`) charge `.env` à la main pour qu'une configuration manquante échoue au lieu de passer.

Script de conformité (`LunchPicker/scripts/check-place-persistence.mjs`) : échoue si un champ interdit arrive dans le schéma ou dans une écriture SQL, lancé comme étape de CI.

Tests Prodysos : environ 85 fichiers Vitest (`src/__tests__/`, jsdom, `@vue/test-utils`, couverture v8) et 15 fichiers pgTAP.

## Commandes et outils
```bash
node --test tests/*.test.mjs           # tests sans dépendance
npx vitest run                         # unitaires, une fois
npx vitest --coverage                  # avec couverture
npx playwright install --with-deps     # navigateurs pour l'e2e
npx playwright test --ui               # voir les tests e2e
npx playwright codegen http://localhost:4321   # générer un test en cliquant
npm run check                          # astro check
TZ=UTC npm test                        # tester dans un autre fuseau
```
- Playwright : https://playwright.dev
- Vitest : https://vitest.dev
- Tester la RLS : https://pgtap.org

## Pièges vécus
- **La doc de tests dérive** (un projet perso) : elle parle de Vitest et d'un script `test:feature` qui n'existent pas. Un test ou une relecture à chaque changement.
- **Un projet sans aucun test** (mis.lawmission.net-V2, Contact) n'a qu'un build comme filet : prévoir au moins des tests sur les fonctions de sécurité.
- **Un lint qui ne bloque jamais** (`continue-on-error: true` dans un projet perso) finit par être ignoré.
- **Ne jamais soumettre un vrai formulaire pendant un test manuel** (Cliffhanger- : cela crée une vraie demande dans Prodysos).
- **`astro check` d'environ une heure** sur MIS : vérifier avec `npm run build` à la place.
- **Aucun test d'accessibilité automatique (axe) dans aucun projet** : à ajouter (voir 10).
