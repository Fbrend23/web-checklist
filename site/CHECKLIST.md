# Checklist condensée

Copier ce fichier dans `doc/CHECKLIST.md` du projet et cocher au fur et à mesure, ou utiliser la page interactive (`index.html`, voir le README). Le détail et les exemples sont dans le fichier indiqué entre parenthèses.

Projet : ________________  Date : ____________

## Quelles sections pour quel projet

| Section | Statique | SPA + BaaS | SSR / API |
|---|---|---|---|
| 01 Techno, 02 Git, 03 Secrets | oui | oui | oui |
| 04 Sécurité de l'app | en-têtes, CSP, échappement | tout | tout |
| 05 Attaques extérieures | formulaire, bots, dépendances | tout | tout |
| 06 Base de données | non (sauf CMS) | oui | oui |
| 07 Tests, 08 CI/CD | oui | oui | oui |
| 09 Performance, 10 Accessibilité, 11 SEO | oui | oui | oui |
| 12 Mise en production | oui | oui | oui |
| 13 Clean code | oui | oui | oui |

Les commentaires HTML `<!-- ... -->` ci-dessous servent de filtre à la page interactive : sans commentaire, la case vaut pour tous les types.

## 1. Choix techno (01)
<!-- types: statique,spa,ssr -->
- [ ] Besoin écrit en 3 lignes, statique envisagé en premier
- [ ] Hébergeur choisi et ses contraintes lues
- [ ] `.nvmrc` + `.node-version` + `engines`, un seul gestionnaire de paquets, lockfile commité
- [ ] Pooler de connexions (mode transaction) si fonctions serverless + Postgres <!-- spa,ssr -->
- [ ] Chaque dépendance justifiée
- [ ] Choix et raisons notés dans le README

## 2. Git et conventions (02)
<!-- types: statique,spa,ssr -->
- [ ] `.gitignore` : `.env`, `node_modules`, `dist`
- [ ] Branches `dev` / `main` (ou gitflow), `main` protégée si équipe
- [ ] Commits conventionnels, corps = pourquoi
- [ ] `.editorconfig`, Prettier, ESLint, scripts `lint` et `format`
- [ ] Fichier de contexte IA (modèle : `templates/AGENTS.md`)

## 3. Config et secrets (03)
<!-- types: statique,spa,ssr -->
- [ ] `.env.example` commenté et commité, `.env` ignoré
- [ ] Validation des variables au démarrage ou au build
- [ ] Aucun secret dans le bundle client
- [ ] Jetons séparés, moindre privilège, aucun jeton admin dans la CI
- [ ] Gitleaks dans le hook de pré-commit
- [ ] Secrets de CI dans GitHub, plan de rotation noté

## 4. Sécurité de l'app (04)
<!-- types: statique,spa,ssr -->
- [ ] Validation serveur par schéma sur toutes les entrées <!-- spa,ssr -->
- [ ] Aucune sortie HTML brute non maîtrisée, JSON-LD et `<script>` échappés
- [ ] URLs externes et redirections filtrées
- [ ] Mots de passe hachés (scrypt, bcrypt, Argon2), comparaison en temps constant <!-- spa,ssr -->
- [ ] Cookies `httpOnly` + `secure` + `sameSite`, suppression testée <!-- spa,ssr -->
- [ ] Autorisation côté serveur sur chaque route <!-- spa,ssr -->
- [ ] CSRF, CORS en liste blanche exacte <!-- spa,ssr -->
- [ ] Appels serveur vers une URL utilisateur : adresses privées bloquées (SSRF) <!-- spa,ssr -->
- [ ] Webhooks : signature vérifiée sur le corps brut <!-- spa,ssr -->
- [ ] En-têtes de sécurité, CSP présente ou absence justifiée par écrit
- [ ] Uploads : allowlist + octets magiques <!-- spa,ssr -->
- [ ] Erreurs en codes, aucune stack trace en production <!-- spa,ssr -->
- [ ] securityheaders.com et observatory.mozilla.org sans alerte

## 5. Attaques extérieures (05)
<!-- types: statique,spa,ssr -->
- [ ] Limitation de débit (login, contact, réinitialisation), IP lue correctement
- [ ] Taille maximale des corps de requête
- [ ] Formulaires : honeypot (faux succès) + captcha vérifié côté serveur
- [ ] `Options -Indexes`, dotfiles et `/api/` fermés
- [ ] Routes cron protégées par secret <!-- spa,ssr -->
- [ ] `robots.txt` réfléchi (moteurs, robots IA)
- [ ] `security.txt` publié sur `/.well-known/`
- [ ] `npm audit` en CI, Dependabot configuré
- [ ] Actions épinglées par SHA, `permissions` minimales
- [ ] gitleaks ou trufflehog passé sur tout l'historique
- [ ] Scan ZAP et injections manuelles passés

## 6. Base de données (06)
<!-- types: spa,ssr -->
- [ ] Requêtes paramétrées partout
- [ ] Compte applicatif à droits minimaux
- [ ] RLS activée, `GRANT` explicites, aucune politique `public`
- [ ] Clés primaires en UUIDv7 (ou `bigint` identity), pas UUIDv4
- [ ] Suppression logique : filtrée par la RLS, purge définitive planifiée
- [ ] Migrations versionnées, idempotentes, essai `BEGIN ... ROLLBACK`
- [ ] Sauvegarde automatique, restauration testée

## 7. Tests (07)
<!-- types: statique,spa,ssr -->
- [ ] `npm test` fonctionne sur un clone neuf
- [ ] Unitaires sur logique métier et fonctions de sécurité
- [ ] E2E Playwright sur les parcours critiques
- [ ] Captures Playwright (`toHaveScreenshot`) sur accueil et formulaire
- [ ] Test du `dist/` construit (SEO, JSON-LD, liens)
- [ ] Dates testées en `TZ=UTC` et en local
- [ ] Aucun test ne dépend du réseau (faux serveur ou MSW)
- [ ] Tests de typage en CI (`astro check`, `vue-tsc`)
- [ ] Un bug corrigé = un test ajouté

## 8. CI/CD (08)
<!-- types: statique,spa,ssr -->
- [ ] `verifier` puis `deployer`, `permissions`, `concurrency`, `timeout-minutes`
- [ ] `dry_run`, contrôle des variables requises en première étape
- [ ] Build refusé sans source de contenu
- [ ] Marqueur de déploiement, mot de passe masqué, `.htaccess` à part, `build.json` en dernier
- [ ] Actions et CLI épinglées, actionlint
- [ ] Retour arrière testé

## 9. Performance (09)
<!-- types: statique,spa,ssr -->
- [ ] Images AVIF/WebP, dimensions explicites, image principale préchargée
- [ ] `dist/` sans originaux en trop, SVG passés par `svgo`
- [ ] Polices auto-hébergées, préchargées, police de secours ajustée (`size-adjust`)
- [ ] `Cache-Control` par type de ressource, compression
- [ ] Pas de JS inutile, `console.*` retirés
- [ ] Lighthouse mobile au vert, Core Web Vitals corrects

## 10. Accessibilité (10)
<!-- types: statique,spa,ssr -->
- [ ] `lang`, un seul `<h1>`, lien d'évitement
- [ ] Tout au clavier, focus visible, piège de focus et `Échap` dans les fenêtres
- [ ] `aria-expanded` / `aria-current` / `aria-pressed` / `aria-live` bien posés
- [ ] `prefers-reduced-motion` géré (CSS et JS)
- [ ] Fonctionne sans JavaScript
- [ ] Contrastes, textes alternatifs, labels de formulaire
- [ ] Cibles cliquables d'au moins 24x24 px (44x44 sur mobile)
- [ ] Zoom non bloqué, `color-scheme` si mode sombre
- [ ] Test à 390, 820 et 1440 px
- [ ] axe ou Lighthouse sans erreur grave, test lecteur d'écran

## 11. SEO (11)
<!-- types: statique,spa,ssr -->
- [ ] Contenu présent dans le HTML sans JavaScript
- [ ] `title` et `description` uniques, un seul `<h1>`
- [ ] Canonical, hreflang + `x-default`
- [ ] Open Graph et Twitter complets
- [ ] JSON-LD valide, échappé, testé
- [ ] `sitemap.xml` et `robots.txt` (généré depuis `SITE_URL`)
- [ ] 404 et pages utilitaires en `noindex`, anciennes URL en 301 côté serveur
- [ ] Un seul domaine canonique en HTTPS
- [ ] Pack de favicons complet (ico, svg, apple-touch-icon, manifeste)
- [ ] Search Console vérifié, sitemap envoyé
- [ ] Lighthouse SEO à 100

## 12. Mise en production et suivi (12)
<!-- types: statique,spa,ssr -->
- [ ] Domaine, DNS, HTTPS valides, TTL DNS baissé 48 h avant la bascule
- [ ] Secrets de production distincts, renouvelés
- [ ] Plan de retour arrière, sauvegardes restaurables <!-- spa,ssr -->
- [ ] Mentions légales et politique de confidentialité, cookies conformes ou absents
- [ ] Sentry actif, moniteur de disponibilité (alerte d'expiration SSL), statistiques sans cookies
- [ ] Parcours critiques testés sur la production
- [ ] `.env`, `.git`, `/api/` en 404 sur la production
- [ ] Dependabot actif, `npm audit` mensuel
- [ ] README d'exploitation à jour (déployer, secrets, contacts)

## 13. Clean code (13)
<!-- types: statique,spa,ssr -->
- [ ] Noms explicites, 3 paramètres au plus
- [ ] Fonctions courtes à une seule responsabilité
- [ ] Retours anticipés, 3 niveaux d'imbrication au maximum
- [ ] Aucun nombre ou texte magique, constantes nommées
- [ ] Commentaires = pourquoi, aucun code mort ni code commenté (knip)
- [ ] Aucune erreur avalée, messages d'erreur avec contexte
- [ ] DRY à la troisième répétition (jscpd), sans abstraction prématurée
- [ ] YAGNI : rien écrit pour plus tard, le plus simple qui marche
- [ ] Règles ESLint de complexité actives, appliquées par la CI
