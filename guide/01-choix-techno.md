# 01 - Choix de la technologie

## Pourquoi
Le mauvais choix de départ coûte plus cher que n'importe quel bug. On choisit la solution la plus simple qui répond au besoin réel, pas la plus à la mode.

## Règles
- **Commencer par le besoin** : le contenu change-t-il ? Y a-t-il des comptes utilisateurs ? Des données écrites par les visiteurs ? Du temps réel ?
- **Statique d'abord** : si le contenu change rarement (ou via un CMS qui déclenche un build), un site statique est plus rapide, moins cher et presque impossible à pirater.
- **Un seul gestionnaire de paquets par projet** (npm ou pnpm, pas les deux) et un lockfile commité. `npm ci` en CI, jamais `npm install`.
- **Version de Node fixée** dans `.nvmrc` et dans `engines` du `package.json`. La CI lit `.nvmrc` (`node-version-file: .nvmrc`).
- **Moins de dépendances = moins de failles.** Avant d'ajouter une lib : le navigateur, le langage ou une dépendance déjà installée le fait-il ?
- **Choisir l'hébergement avant de coder** : il impose des contraintes (Apache et `.htaccess` chez Infomaniak, fonctions serverless chez Netlify et Vercel, limite de connexions FTP).
- **Noter le choix et la raison** dans le README ou la doc du projet (une phrase par choix).

## Matrice de décision

| Besoin | Choix | Pourquoi | Exemple dans mes projets |
|---|---|---|---|
| Site vitrine / portfolio, contenu peu changeant | Astro statique | Zéro JS par défaut, SEO excellent, build simple | Cliffhanger-, site-photos-v2, Maisallezfieu, matinale-seoul, Contact |
| Site vitrine très interactif, une page | Vue 3 + vite-ssg (prérendu) | Interactivité + HTML lisible sans JS | Portail |
| Application connectée, comptes, données | Vue 3 + Vite + Supabase (Postgres, RLS, Auth, Edge Functions) | Backend sans serveur à maintenir, sécurité au niveau des lignes | Prodysos |
| App avec rendu serveur et API | Nuxt 3 (Nitro) + Neon | Un seul projet front et API | LunchPicker |
| App métier classique MVC | AdonisJS 6 + Lucid | Auth, validation, ORM inclus | Un projet perso |
| Contenu édité par un client | CMS headless (Directus, Sanity) | Le client édite, le site se reconstruit | platform-cms (Directus), mis.lawmission.net-V2 (Sanity) |
| Formulaire de contact | Endpoint minimal (PHP + PHPMailer, Edge Function) | Une seule responsabilité, facile à auditer | Contact, Prodysos `send-contact-message` |

## Hébergement

| Hébergeur | Quand | Particularités vécues |
|---|---|---|
| Infomaniak (Apache, FTPS) | Sites statiques suisses, budget faible | `.htaccess` pour redirections, en-têtes, cache. Limite de connexions FTP : préférer un déploiement par manifeste. Node.js possible pour un CMS |
| Netlify | Statique + quelques fonctions SSR | `_headers` pour les en-têtes, Netlify Forms, Blobs |
| Vercel | Nuxt / Next | `vercel.json` (région proche de la base, cron, `no-store` sur `/api`) |
| Supabase | Backend de Prodysos | Migrations versionnées, Edge Functions en Deno |
- **`.node-version` en plus de `.nvmrc`** : mise, fnm, Cloudflare Pages, Vercel et Netlify lisent l'un ou l'autre, avoir les deux évite les surprises.
- **Serverless + Postgres (Neon, Supabase) = pooler obligatoire** : passer par le pooler en mode transaction (Supabase : port `6543`, pas le `5432` de session) pour ne pas épuiser les connexions lors des pics.

## Checklist
- [ ] Le besoin est écrit en 3 lignes (qui, quoi, combien de trafic, qui édite).
- [ ] Statique envisagé en premier, et écarté pour une raison écrite si on prend du dynamique.
- [ ] Hébergeur choisi et ses contraintes lues (cache, headers, limites).
- [ ] `.nvmrc` + `engines` dans `package.json`.
- [ ] Un seul gestionnaire de paquets, lockfile commité.
- [ ] Chaque dépendance ajoutée est justifiée (pas de lib pour 10 lignes).
- [ ] Domaine, DNS et certificat HTTPS prévus.
- [ ] Choix de techno et raisons notés dans le README.
- [ ] Pooler de connexions utilisé si fonctions serverless + Postgres (port transactionnel).

## Commandes et outils
```bash
node -v > .nvmrc                       # fixer la version de Node
npm create astro@latest                # nouveau site Astro
npm create vue@latest                  # nouveau projet Vue (TypeScript, Vitest, Playwright, ESLint)
npm ci                                 # installation reproductible (CI)
npm ls --depth=0                       # lister les dépendances directes
npx depcheck                           # trouver les dépendances inutilisées
```
- Comparer les tailles de paquets : https://bundlephobia.com
- Vérifier la santé d'une lib (maintenance, vulnérabilités) : https://snyk.io/advisor et https://npmgraph.js.org
- Choisir un nom de domaine : https://www.infomaniak.com/fr/domaines

## Pièges vécus
- **Deux lockfiles** (`package-lock.json` et `pnpm-lock.yaml`) dans un de mes projets : on ne sait plus lequel fait foi. Un seul.
- **Dockerfile en `node:18` alors que le projet vise Node 24** (un projet perso) : la version doit venir d'une seule source.
- **Dépendance native lourde en CI** (platform-cms, `isolated-vm`) : on n'installe pas tout pour vérifier la syntaxe, on lance seulement `npm test`.
- **Un projet sans test ni lint** (mis.lawmission.net-V2) devient risqué à chaque changement : prévoir au minimum un build en CI.
