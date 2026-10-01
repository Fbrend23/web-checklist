# 04 - Sécuriser l'application

## Pourquoi
On ne sécurise pas à la fin. Chaque entrée utilisateur est hostile, chaque sortie doit être échappée, chaque droit doit être le plus petit possible. Base : OWASP Top 10.

## Règles

### Entrées et sorties
- **Valider côté serveur**, toujours, avec un schéma (zod, VineJS, Ajv). La validation côté client n'est que du confort.
- **Échapper à la sortie** : le moteur de template échappe par défaut, on n'utilise la sortie brute (`{{{ }}}`, `set:html`, `v-html`) que pour du contenu maîtrisé.
- **JSON dans une balise `<script>`** (JSON-LD, données initiales) : échapper `<`, `>`, `&`, U+2028 et U+2029, sinon `</script>` dans une donnée casse la page et ouvre un XSS.
- **URLs venant d'un CMS ou d'un utilisateur** : refuser `javascript:` et protocoles inattendus. Pour une redirection, refuser `//`, `\` et les URL absolues (open redirect).
- **Requêtes SQL paramétrées uniquement** (`$1`), jamais de concaténation. Voir 06.
- **Uploads** : liste blanche d'extensions, contrôle des octets magiques (pas du `Content-Type` envoyé), refuser SVG et exécutables, renommer le fichier, stocker hors du dossier exécutable.
- **Erreurs** : le serveur renvoie des codes stables (`room_not_found`), le client les traduit. Jamais d'objet `error` brut, jamais de stack trace en production (`NODE_ENV=production`).

### Authentification et sessions
- **Mots de passe** : scrypt, bcrypt ou Argon2 avec sel unique, jamais de SHA seul. Comparaison en temps constant (`timingSafeEqual`).
- **Cookies de session** : `httpOnly`, `secure` en production, `sameSite: 'lax'` (ou `strict`), durée limitée, version de jeton pour pouvoir révoquer.
- **Secret de signature** : long (32 caractères minimum), l'appli refuse de démarrer sans lui.
- **Autorisation côté serveur** pour chaque route et chaque ligne (RBAC, RLS). Cacher un bouton n'est pas une protection.
- **Jetons d'API** : stockés hachés (on stocke l'empreinte, pas la valeur), générés avec `randomBytes(32)`.

### Navigateur
- **CSRF** : jeton CSRF ou vérification stricte de `Origin`/`Referer` (correspondance exacte, jamais un suffixe comme `.netlify.app`).
- **CORS** : liste blanche d'origines exactes. Jamais `origin: true` avec `credentials: true`.
- **En-têtes** : voir le tableau ci-dessous.

| En-tête | Valeur conseillée | Rôle |
|---|---|---|
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | Force HTTPS |
| `X-Content-Type-Options` | `nosniff` | Pas de devinette de type MIME |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Limite les fuites d'URL |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=()` | Désactive les API inutiles |
| `X-Frame-Options` / `frame-ancestors` | `DENY` / `'none'` | Anti clickjacking |
| `Content-Security-Policy` | politique stricte, sans `unsafe-inline` pour les scripts | Anti XSS |

- **CSP, trois façons de la poser** selon le contexte :
  - site Astro statique : `security.csp` dans `astro.config.mjs` (hash des scripts en ligne, pas de `unsafe-inline`) ;
  - SPA : balise `<meta>` générée au build par un plugin Vite (attention, `frame-ancestors` est ignoré dans une balise meta : l'ajouter en en-tête) ;
  - hébergeur Netlify : `public/_headers` pour les pages statiques **et** middleware pour le SSR (garder les deux synchronisés).
- Une CSP absente doit être un choix documenté (ex. « casserait GoatCounter et les styles en ligne »), pas un oubli.
- **SSRF** : dès qu'une fonction serveur appelle une URL fournie par l'utilisateur (webhook, aperçu d'image, proxy), refuser les adresses privées et locales (`127.0.0.0/8`, `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, et surtout `169.254.169.254`, les métadonnées cloud). Résoudre le DNS puis contrôler l'IP, et refuser les redirections vers l'interne.
- **Webhooks signés (Stripe, GitHub, Twilio)** : calculer le HMAC sur le **corps brut**. Si un middleware parse le JSON avant (`express.json()`), la signature ne correspond plus.
- **Supprimer un cookie** : les attributs `path`, `domain` et `secure` de l'expiration doivent être identiques à ceux de la création, sinon le cookie reste.

## Checklist
- [ ] Toutes les entrées serveur passent par un schéma de validation.
- [ ] Aucune sortie brute non maîtrisée (`grep -rn "{{{\|set:html\|v-html\|innerHTML" src/`).
- [ ] JSON-LD et données dans `<script>` échappés (`<` en `<`).
- [ ] URLs externes filtrées (`javascript:`), redirections validées.
- [ ] Requêtes paramétrées partout.
- [ ] Uploads : allowlist + octets magiques + pas d'exécution possible dans le dossier.
- [ ] Mots de passe hachés (scrypt, bcrypt, Argon2), comparaison en temps constant.
- [ ] Cookies `httpOnly` + `secure` + `sameSite`.
- [ ] Autorisation vérifiée côté serveur sur chaque route.
- [ ] CSRF protégé, CORS en liste blanche exacte.
- [ ] En-têtes de sécurité en place, CSP présente ou son absence justifiée par écrit.
- [ ] Erreurs en codes, aucune stack trace en production.
- [ ] Test d'en-têtes sur https://securityheaders.com et https://observatory.mozilla.org.
- [ ] Appels serveur vers une URL utilisateur : adresses privées bloquées (SSRF).
- [ ] Webhooks : signature vérifiée sur le corps brut.
- [ ] Déconnexion testée : le cookie de session disparaît vraiment.

## Exemples de mes projets

CSP par hash pour un site statique (`Contact/astro.config.mjs`) : les scripts en ligne sont hachés, donc pas de `unsafe-inline` pour les scripts. Les styles gardent `unsafe-inline` car reCAPTCHA pose des attributs `style=""`.

En-têtes dans `.htaccess` (`Cliffhanger-/public/.htaccess`) :
```apache
Options -Indexes
Header always set X-Content-Type-Options "nosniff"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Permissions-Policy "camera=(), microphone=(), geolocation=()"
```

Anti clickjacking seul, avec la raison écrite (`Portail/public/.htaccess`) :
```apache
# Anti-clickjacking seul : une CSP complète casserait GoatCounter et les styles inline
Header set Content-Security-Policy "frame-ancestors 'none'"
```

Authentification maison solide (`mis.lawmission.net-V2/src/utils/`) :
- `auth.ts` : scrypt, `timingSafeEqual`, sessions signées HMAC, refus de démarrer sans secret.
- `security.ts` : limitation de débit avec blocage, contrôle CSRF par `Origin` exact, validation des octets magiques des fichiers (SVG et exécutables refusés), nettoyage des noms de fichier.
- `safeUrl.ts` : bloque `javascript:` pour les URL venant du CMS, avec une variante lecture et une variante écriture.
- `jsonScript.ts` : JSON sûr pour `<script>`.

Échappement du JSON-LD (`Prodysos/src/lib/jsonLd.ts`) :
```ts
el.textContent = JSON.stringify(data).replace(/</g, '\\u003c')
```

Garde anti open redirect (`Prodysos/src/lib/sanitizeDeeplink.ts`) : rejette `//`, `\`, les URL absolues et `javascript:`, avec les cas de test listés en commentaire.

Échappement centralisé (`matinale-seoul/src/lib/html.js`) : « un échappement qui diverge est une injection qui attend son heure ».

Validation par schéma : zod (`Cliffhanger-/src/content.config.js`), Ajv 2020-12 (`matinale-seoul/scripts/lib/guards.mjs`), VineJS avec règle téléphone E.164 (un projet AdonisJS perso).

Formulaire PHP avec ordre de contrôle clair (`Contact/api/envoyer.php`) : méthode, liste blanche d'origines, honeypot (faux succès), validation des champs (contrôle CR/LF contre l'injection d'en-têtes), vérification reCAPTCHA côté serveur avec délais, puis envoi. Erreurs loguées, message générique pour l'utilisateur.

### Contre-exemples à éviter
| Projet | Défaut | Correction |
|---|---|---|
| Un projet perso, `config/cors.ts` | `origin: true` avec `credentials: true` : reflète n'importe quelle origine | Liste blanche d'origines |
| Un projet perso, `config/shield.ts` | CSP désactivée | Activer une CSP, même progressive (`Report-Only` d'abord) |
| Un projet perso, CSRF | Exemption `url.includes('webhook')` : toute URL contenant le mot passe | Exempter une route exacte, protégée par signature |
| Un projet perso, `api_auth_middleware.ts` | Jetons d'API stockés et cherchés en clair | Stocker un hachage |
| Un projet perso, `path.edge` | `wp.name` interpolé en HTML brut : XSS | Sortie échappée par défaut |
| Un ancien projet PHP | Pages `admin/*.php` sans authentification, upload qui garde l'extension de l'utilisateur, pas de CSRF | Authentification, allowlist d'extensions, jeton CSRF |
| site-photos-v2 `Base.astro:118` | JSON-LD avec `JSON.stringify` seul | Échapper `<` comme dans `jsonLd.ts` |

## Commandes et outils
```bash
npm audit --audit-level=high           # vulnérabilités des dépendances
curl -sI https://mon-site.ch | head -20   # lire les en-têtes de réponse
openssl s_client -connect mon-site.ch:443 -servername mon-site.ch </dev/null | openssl x509 -noout -dates
```
- Tester ses en-têtes : https://securityheaders.com, https://observatory.mozilla.org
- Évaluer sa CSP : https://csp-evaluator.withgoogle.com
- Référence : https://owasp.org/Top10 et https://cheatsheetseries.owasp.org
- S'entraîner légalement : OWASP Juice Shop (https://owasp.org/www-project-juice-shop) et DVNA
- Scanner son propre site : OWASP ZAP (https://www.zaproxy.org), uniquement sur ses propres applications

## Pièges vécus
- **`frame-ancestors` dans une balise meta est ignoré** (Prodysos) : l'ajouter en en-tête.
- **Les en-têtes définis à deux endroits** (MIS : `_headers` et middleware) divergent avec le temps : un commentaire dans les deux fichiers dit de les garder synchronisés.
- **`robots.txt` ne doit pas interdire une page avec `noindex`** (MIS `/portal`) : le robot doit pouvoir lire l'en-tête `noindex`.
- **Une CSP « pour plus tard » ne vient jamais** : Cliffhanger-, Portail, site-photos-v2, Maisallezfieu, LunchPicker n'en ont pas. À traiter.
