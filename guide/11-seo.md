# 11 - SEO

## Pourquoi
Un site que personne ne trouve n'existe pas. Le SEO technique se prépare dès la structure du site et se vérifie à la fin avec des outils gratuits.

## Règles

### Contenu lisible par les robots
- **Du HTML complet sans JavaScript** : site statique ou prérendu (vite-ssg). Les robots de réseaux sociaux n'exécutent pas le JS.
- **Un `<title>` et une `meta description` uniques par page**, un seul `<h1>`.
- **URL propres, stables et lisibles**. Les anciennes URL redirigent en **301 côté serveur** (pas de meta refresh).

### Balises de tête
- **Canonical** sur chaque page indexable.
- **hreflang** pour chaque langue, plus `x-default`. Le sitemap contient aussi les `xhtml:link` alternatifs.
- **Open Graph et Twitter** : `og:title`, `og:description`, `og:image` (avec dimensions), `og:locale` et `og:locale:alternate`.
- **`theme-color`** par schéma de couleurs.
- **`robots`** : `max-image-preview:large` pour les sites visuels.

### Données structurées (JSON-LD)
- Un **graphe lié par `@id`** : `WebSite`, `Organization` ou `TheaterGroup`, `Person`, `Event`, `ImageObject`, `BreadcrumbList`, `NewsArticle`.
- **Échapper `<`** dans le JSON-LD (voir 04).
- Pour un contenu produit automatiquement, le dire (`creator`).
- Valider avec le test de résultats enrichis.

### Fichiers à la racine
- **`sitemap.xml`** généré (intégration Astro) ou à la main si le site est minuscule.
- **`robots.txt` généré depuis `SITE_URL`** pour avoir la vraie URL du sitemap. Autoriser les moteurs, refuser par nom les robots d'entraînement d'IA si voulu.
- **`noindex`** sur les pages utilitaires (404, page « merci », portail privé). Une page `noindex` ne figure pas dans le sitemap (signaux cohérents).
- **Ne pas interdire dans `robots.txt` une page `noindex`** : le robot doit pouvoir lire la directive.
- **Flux RSS** pour les contenus datés.

### Après la mise en ligne
- **Search Console** : vérifier la propriété (balise ou fichier, à ne pas perdre), envoyer le sitemap.
- **Statistiques sans cookies** (GoatCounter, ou analytics maison).
- **Suivre** la couverture d'indexation et les Core Web Vitals (voir 09).
- **`/llms.txt`** (optionnel, standard encore jeune et pas lu par tous les moteurs) : description du site en Markdown pour les assistants IA, en complément de `robots.txt`.
- **Pack de favicons minimal** : `favicon.ico`, `favicon.svg` (avec `prefers-color-scheme` pour le mode sombre), `apple-touch-icon.png` en 180x180 et `manifest.webmanifest`.

## Checklist
- [ ] Le contenu est présent dans le HTML sans JavaScript (`curl` de la page, affichage de la source).
- [ ] `title` et `description` uniques par page.
- [ ] Un seul `<h1>`, hiérarchie de titres cohérente.
- [ ] Canonical sur chaque page, hreflang + `x-default` si plusieurs langues.
- [ ] Open Graph et Twitter complets, image 1200x630 par page importante.
- [ ] JSON-LD valide et échappé, testé avec le test de résultats enrichis.
- [ ] `sitemap.xml` à jour, référencé dans `robots.txt`.
- [ ] `robots.txt` volontaire (moteurs, robots IA).
- [ ] 404 en `noindex`, sans canonical.
- [ ] Anciennes URL en 301 côté serveur.
- [ ] HTTPS partout, redirection vers le domaine canonique (avec ou sans `www`, un seul).
- [ ] Search Console vérifié et sitemap envoyé.
- [ ] Tests SEO automatisés sur le `dist/` (titres uniques, canonical, JSON-LD analysable).
- [ ] Lighthouse SEO à 100.
- [ ] Pack de favicons complet (ico, svg, apple-touch-icon, manifeste).
- [ ] (Optionnel) `llms.txt` publié.

## Exemples de mes projets

Graphe JSON-LD complet (`Cliffhanger-/src/lib/seo.js`) : `WebSite`, `TheaterGroup` (avec `member`), `TheaterEvent` (avec `Offer`, `PostalAddress`, `endDate`), `Person` et `BreadcrumbList`, liés par `@id`. Description unique par page imposée, 404 en `noindex` sans canonical.

Robots.txt généré depuis `SITE_URL` (`Cliffhanger-/src/pages/robots.txt.js`) : moteurs et robots de citation autorisés, robots d'entraînement refusés par nom (commentaire qui explique la différence). Même principe dans `matinale-seoul/src/pages/robots.txt.js` et `site-photos-v2/public/robots.txt`.

Images sous licence (`site-photos-v2/src/lib/seo.js`) : `ImageObject` avec `license` et `acquireLicensePage`, helper `texteAlt()` partagé, sitemap avec alternatives i18n, flux RSS, 301 des anciennes URL côté Apache (un seul saut).

Page personnelle bilingue (`Portail/src/pages/HomePage.vue`) : `useHead` par langue avec canonical, hreflang fr/en/x-default, `og:*` dont `og:locale:alternate`, JSON-LD `Person` (`hasCredential`, `alumniOf`, `sameAs`). `public/sitemap.xml` avec `xhtml:link`. Prérendu avec vite-ssg.

Article produit automatiquement (`matinale-seoul/src/pages/briefs/[slug].astro`) :
```js
isAccessibleForFree: true,
creator: { '@type': 'Organization', name: 'La Matinale de Séoul (production automatisée)' },
```

Aperçu de réseaux sociaux (`Prodysos/scripts/prerender-shows.mjs`) : un fichier HTML par spectacle public avec OG intégré, car les robots sociaux n'exécutent pas le JS. `scripts/generate-sitemap.mjs` lancé en `postbuild`.

Images OG générées au build (`mis.lawmission.net-V2/src/pages/og/`, satori + resvg), hreflang ko/en/x-default (`BaseLayout.astro`), `sitemap.xml.ts` dynamique.

Cohérence des signaux (`Maisallezfieu`) : propriété `noindex` sur `/merci/` et exclusion du sitemap, vrais 301 pour les anciennes URL, vérification Search Console conservée dans `src/data/site.json` (le README avertit de ne pas la perdre).

Tests SEO sur le site construit (`Cliffhanger-/test/site-construit.mjs`) : titres et descriptions uniques, canonical, JSON-LD analysable.

Application privée (`Contact`) : `noindex` volontaire.

## Commandes et outils
```bash
curl -s https://mon-site.ch | head -50           # le contenu est-il dans le HTML ?
curl -sI http://mon-site.ch | head -5             # redirection HTTP vers HTTPS ?
curl -s https://mon-site.ch/robots.txt
curl -s https://mon-site.ch/sitemap-index.xml | head
npx lighthouse https://mon-site.ch --only-categories=seo --view
```
- Search Console : https://search.google.com/search-console
- Test des résultats enrichis : https://search.google.com/test/rich-results
- Validateur schema.org : https://validator.schema.org
- Aperçu des partages : https://opengraph.xyz et https://www.opengraph.xyz
- Vérifier hreflang : https://technicalseo.com/tools/hreflang
- Recherche du site indexé : `site:mon-site.ch` dans le moteur
- Statistiques sans cookies : https://www.goatcounter.com

## Pièges vécus
- **JSON-LD non échappé** (`site-photos-v2/src/layouts/Base.astro:118`) : à corriger, voir 04.
- **Meta refresh à la place d'un 301** : ne transmet pas le classement.
- **`/merci/` indexée mais exclue du sitemap** : signaux contradictoires.
- **`robots.txt` qui interdit `/portal` alors que la page porte `noindex`** : le robot ne lit jamais la directive (MIS).
- **Vérification Search Console perdue** lors d'une refonte : la garder dans un fichier de données versionné.
- **Contenu servi seulement par JS** (SPA sans prérendu) : invisible pour les robots sociaux et risqué pour l'indexation.
