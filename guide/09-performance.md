# 09 - Performance

## Pourquoi
Un site lent perd ses visiteurs et son classement. Les gains les plus gros viennent des images, du cache et de ce qu'on évite d'envoyer au navigateur.

## Règles

### Images
- **AVIF avec repli WebP**, `loading="lazy"` sauf pour l'image principale, `width` et `height` explicites (évite le décalage de mise en page).
- **Image héros** : `<link rel="preload" as="image">` avec `imagesrcset` et `fetchpriority="high"`.
- **Un seul endroit décide des tailles** (`src/lib/images.js`), pas de réglages dispersés dans les gabarits.
- **Toujours passer `width` avec `widths`** dans `<Image>` d'Astro, sinon les originaux sont copiés dans `dist/` (38 Mo d'originaux d'un coup).
- **Ne jamais lire `.width` d'une `ImageMetadata` dans un gabarit** : cela émet l'image d'origine dans `dist/`.
- **Aperçu flou** (32 px) pendant le chargement, file de chargement en arrière-plan qui respecte `saveData`.
- **Cache d'images en CI** (`actions/cache`, clé sur `run_id` avec `restore-keys`) : on ne recalcule pas tout à chaque build.

### Polices
- **Auto-hébergées** (Fontsource), en `woff2`, préchargées. Pas de feuille CSS externe qui bloque le rendu.
- Polices variables pour limiter le nombre de fichiers.

### Cache HTTP
| Ressource | `Cache-Control` | Raison |
|---|---|---|
| Fichiers à hash (`/_astro/`, bundles) | `public, max-age=31536000, immutable` | Le nom change quand le contenu change |
| HTML | `no-cache` ou 5 minutes | Toujours à jour |
| Fichiers sans hash (loader Pagefind) | 1 heure | Le nom ne change pas |
| `build.json`, `/api/` | `no-store` | Jamais en cache |
| Images publiques | 1 jour à 7 jours | Compromis |
- **Compression** (`mod_deflate` ou équivalent).
- **`trailingSlash: 'always'`** avec sortie en dossiers : évite une redirection de plus.
- **Redirections d'anciennes URL côté serveur** (301 dans Apache) plutôt que dans le framework : un seul saut.

### JavaScript
- **Zéro JS par défaut** (Astro) : n'envoyer que ce qui sert (timeline, recherche).
- **Précharger seulement le nécessaire** (`prefetchAll: false`, seulement les liens de menu).
- **Retirer les `console.*`** du build (esbuild `pure`) et les interdire au lint.
- **PWA** : précache du service worker, `version.json` par build pour détecter une mise à jour. Le service worker ne met jamais `/api/` en cache.
- **Cartes sources** : générées en mode caché, envoyées à l'outil de suivi d'erreurs puis supprimées de `dist/`.

### Backend et base
- Pool de connexions limité, mémoire Node plafonnée (`--max-old-space-size=384` sur petit hébergement), région de l'hébergeur proche de la base (`sin1` pour Neon), cache des transformations d'images (`ASSETS_CACHE_TTL=30d`).
- **Speculation Rules API** (`<script type="speculationrules">`) pour précharger ou prérendre les pages probables, à la place d'un script tiers de préchargement au survol.
- **SVG nettoyés avec `svgo`** (les exports Illustrator et Figma sont pleins de métadonnées inutiles).
- **CLS des polices** : `size-adjust`, `ascent-override` et `descent-override` dans `@font-face` pour que la police de secours prenne la même place (souvent déjà géré par Fontsource).

## Checklist
- [ ] Images en AVIF/WebP, dimensions explicites, `lazy` sauf image principale.
- [ ] Image principale préchargée avec `fetchpriority="high"`.
- [ ] `dist/` vérifié : aucune image d'origine en trop (`du -sh dist; find dist -size +500k`).
- [ ] Polices auto-hébergées et préchargées.
- [ ] Stratégie `Cache-Control` en place (hash = immutable, HTML = no-cache).
- [ ] Compression active.
- [ ] Pas de JS inutile envoyé, bundle analysé.
- [ ] `console.*` retirés du build.
- [ ] Lighthouse (mobile) : Performance, Accessibilité, SEO et Bonnes pratiques au vert.
- [ ] Core Web Vitals vérifiés (LCP < 2,5 s, CLS < 0,1, INP < 200 ms).
- [ ] Service worker : jamais de cache sur `/api/`.
- [ ] SVG passés par `svgo`.
- [ ] Police de secours ajustée (`size-adjust`) pour éviter le décalage.

## Exemples de mes projets

Image principale (`Cliffhanger-/src/lib/images.js` et `Base.astro`) : AVIF qualité 60 avec repli WebP, découpe portrait 9:16 autour du point focal, aperçu flou 32 px, préchargement avec `imagesrcset` et `fetchpriority="high"`.

Police auto-hébergée (`Portail`) : `@fontsource-variable/sora`, image héros préchargée, WebP avec `loading="lazy"` et dimensions explicites.

Cache HTTP (`matinale-seoul/public/.htaccess`) : hash en `immutable` 1 an, fichiers Pagefind 1 heure (noms sans hash), `build.json` en `no-store`, HTML 5 minutes, compression.

Hébergeur Netlify (`mis.lawmission.net-V2/public/_headers`) : `/_astro`, `/fonts`, `/images` en `immutable` 1 an, Pagefind 1 jour. Le site public n'envoie aucun JS sauf timeline et recherche.

PWA (`Prodysos`) : `vite-plugin-pwa` en `injectManifest`, `src/sw.ts`, compression d'images côté client (`src/lib/imageCompression.ts`), `version.json` par build.

Région proche de la base (`LunchPicker/vercel.json`) : région `sin1`, `no-store` sur `/api/(.*)`.

Configuration légère du CMS (`platform-cms/.env.example`) : `CACHE_TTL=5m`, `ASSETS_CACHE_TTL=30d`, `ASSETS_TRANSFORM_MAX_CONCURRENT=1`, pool de base à 3, `NODE_OPTIONS=--max-old-space-size=384`.

## Commandes et outils
```bash
npx lighthouse https://mon-site.ch --view --preset=desktop
npx lighthouse https://mon-site.ch --view --form-factor=mobile
du -sh dist && find dist -type f -size +500k      # fichiers lourds dans le build
npx astro build && npx vite-bundle-visualizer     # analyse du bundle (Vite)
curl -sI -H "Accept-Encoding: br,gzip" https://mon-site.ch | grep -i "content-encoding\|cache-control"
```
- Mesurer : https://pagespeed.web.dev, https://www.webpagetest.org, https://web.dev/measure
- Comprendre les Core Web Vitals : https://web.dev/vitals
- Optimiser des images à la main : https://squoosh.app
- Vérifier le cache et les en-têtes : https://securityheaders.com (en-têtes), onglet Réseau des outils de développement

## Pièges vécus
- **38 Mo d'originaux dans `dist/`** (site-photos-v2) : `widths` sans `width`.
- **Lire `.width` d'une `ImageMetadata`** a le même effet (Cliffhanger-).
- **Fichiers Pagefind sans hash** mis en `immutable` : les visiteurs gardaient une ancienne recherche. Durée courte pour eux.
- **Limite de connexions FTP** : un miroir complet échouait, d'où le déploiement par manifeste (voir 08).
- **Prérendu indispensable** (Portail) : sans lui, le contenu n'existe pas pour les robots sans JS (voir 11).
