# 05 - Se protéger des attaques extérieures

## Pourquoi
Dès qu'un site est en ligne, des robots le testent : formulaires spammés, mots de passe devinés, dépendances piégées, scrapers, bots d'IA. On ajoute des barrières à chaque point d'entrée et on surveille la chaîne d'approvisionnement.

## Règles

### Limiter le débit
- **Limiter** les endpoints sensibles : login, formulaire de contact, réinitialisation, création de ressources.
- **Une limite en mémoire est « au mieux »** : en serverless chaque instance a la sienne. Pour une vraie protection, utiliser le limiteur de l'hébergeur ou un stockage partagé.
- **IP du client** : derrière un proxy, l'en-tête `X-Forwarded-For` est forgeable. Prendre le **dernier** saut (celui ajouté par le proxy de confiance), pas le premier. Activer `IP_TRUST_PROXY` seulement derrière un proxy de confiance.
- **Blocage temporaire** après N échecs de connexion, messages d'erreur identiques pour « utilisateur inconnu » et « mauvais mot de passe ».

### Formulaires publics
- **Honeypot** : un champ caché que seuls les robots remplissent. Répondre par un **faux succès** (200), pas une erreur, pour ne rien apprendre au robot.
- **Captcha vérifié côté serveur** (Turnstile, reCAPTCHA) : le jeton envoyé par le navigateur est contrôlé via `siteverify` avec un délai maximal. Un captcha non vérifié côté serveur ne protège rien.
- **Liste blanche d'origines** pour l'endpoint, longueur maximale des champs, contrôle des retours à la ligne (injection d'en-têtes d'e-mail).
- **Repli sans JavaScript** : redirection 303 vers une page de résultat, rien de saisi ne finit dans l'URL.

### Bots, scrapers, IA
- **`robots.txt`** : autoriser les moteurs de recherche, refuser par nom les robots d'entraînement d'IA (`GPTBot`, `CCBot`, `Google-Extended`, etc.) tout en laissant les robots de citation si on le souhaite.
- **En-têtes de réserve** : `TDM-Reservation: 1` et `X-Robots-Tag: noai, noimageai` pour un portfolio.
- **Hotlinking** : refuser les images appelées depuis un autre domaine, en acceptant le référent vide et les moteurs, avec une ancre `(/|$)` pour que `domaine.ch.evil.com` ne passe pas.
- **Fichiers cachés** : `RedirectMatch 404 "/\.(?!well-known)"` (rien de `.git`, `.env`, mais `.well-known` reste accessible).
- **Pas de listing de dossier** : `Options -Indexes`.
- **Endpoints internes fermés** : tout `/api/` en 404 sauf l'endpoint public.
- **Routes planifiées** (cron) protégées par un secret `Bearer`.

### Chaîne d'approvisionnement
- **`npm audit`** en CI, `--audit-level=high`.
- **Dependabot** avec regroupement (Astro et TypeScript ensemble) et ignores **documentés** avec la condition de levée.
- **Actions GitHub épinglées par SHA** de commit (une balise comme `@v4` peut être déplacée). Les épingler à la main puis laisser Dependabot les mettre à jour.
- **Permissions minimales** dans les workflows : `permissions: contents: read`.
- **Scan de secrets** sur tout l'historique (gitleaks, trufflehog).
- **Lockfile commité** et `npm ci`.
- **Peu de dépendances**, surtout pour ce qui touche à l'authentification ou au HTML.

### Tester soi-même (uniquement ses propres applications)
- Passer un scanner (ZAP) avant la mise en production.
- Tester les cas classiques à la main : `' OR '1'='1` dans les champs de login, `<script>` dans les champs libres, accès direct à une URL protégée sans session.
- **`/.well-known/security.txt`** (RFC 9116) : un fichier texte avec un contact (e-mail), `Expires` et si besoin une clé PGP, pour qu'on puisse signaler une faille sans le faire publiquement. Générateur : https://securitytxt.org.
- **Taille maximale des requêtes** : environ `100kb` pour les API JSON, `5mb` pour les uploads, sinon déni de service par mémoire (`MAX_PAYLOAD_SIZE`, `bodyLimit`).

## Checklist
- [ ] Limitation de débit sur login, contact et réinitialisation (ou limiteur de l'hébergeur).
- [ ] IP du client lue correctement derrière le proxy (dernier saut).
- [ ] Formulaires : honeypot avec faux succès + captcha vérifié côté serveur.
- [ ] Liste blanche d'origines et longueurs maximales sur les endpoints.
- [ ] `robots.txt` réfléchi (moteurs oui, entraînement IA non si voulu).
- [ ] `Options -Indexes`, fichiers cachés en 404, `/api/` fermé sauf l'endpoint public.
- [ ] Routes cron protégées par secret.
- [ ] `npm audit` en CI.
- [ ] Dependabot configuré (regroupé, ignores documentés).
- [ ] Actions GitHub épinglées par SHA, `permissions` minimales.
- [ ] Scan de secrets passé sur l'historique complet.
- [ ] Scan ZAP et tests manuels d'injection passés avant la mise en ligne.
- [ ] `security.txt` publié sur `/.well-known/`.
- [ ] Limite de taille des corps de requête configurée.

## Exemples de mes projets

Limitation et proxy de confiance (`platform-cms/.env.example`) :
```ini
RATE_LIMITER_ENABLED=true
RATE_LIMITER_POINTS=25
RATE_LIMITER_DURATION=1
MAX_PAYLOAD_SIZE=1mb
```

IP de confiance (`Prodysos/supabase/functions/send-contact-message/index.ts`) :
```ts
const ip = xff.at(-1) || 'unknown'   // le premier saut est forgeable
```
Cette fonction combine honeypot `website` (faux 200), limite de 5 envois par 10 minutes et par IP, liste blanche CORS et vérification Turnstile côté serveur.

Tunnel Sentry public mais verrouillé (`Prodysos/supabase/functions/sentry-tunnel`) : DSN contrôlé, taille limitée à 1 Mo, délai amont de 10 s, donc pas de relais ouvert.

Blocage des accès directs (`Contact/.htaccess`) :
```apache
RewriteRule ^api/(?!envoyer\.php$) - [R=404,L]
```

Dotfiles cachés (`Maisallezfieu/public/.htaccess`) :
```apache
RedirectMatch 404 "/\.(?!well-known)"
```

Route cron protégée (`LunchPicker/server/api/cron/purge.get.ts`) : en-tête `Authorization: Bearer ${CRON_SECRET}` obligatoire.

Dependabot (`matinale-seoul/.github/dependabot.yml`) : mises à jour mensuelles, Astro et TypeScript groupés, majeures de TypeScript ignorées avec une condition « À LEVER » écrite.

Actions épinglées par SHA : `Prodysos/.github/workflows/ci.yml` et `mis.lawmission.net-V2/.github/workflows/deploy.yml`.

Comparatif d'outils d'analyse statique (projet P_183-CICD sur pm2etml-apps) :

| Outil | Résultat | Remarque |
|---|---|---|
| TruffleHog | rien trouvé | cassé quand le dépôt est devenu privé |
| GitHub Code Security | 26 alertes | intégré, gratuit sur dépôt public |
| Snyk | 8 alertes | dépendances et code |
| DeepSource | 595 alertes | très bruyant, beaucoup de faux positifs |
| SonarQube Cloud | | analyse automatique à activer, première analyse via une PR |
| Dependabot | | ignorer les dépendances de dev (faux positifs) |

## Commandes et outils
```bash
npm audit --audit-level=high
gitleaks detect --source . --log-opts="--all"     # tout l'historique
docker run --rm -v "$PWD:/repo" trufflesecurity/trufflehog:latest git file:///repo
npx katana -u http://127.0.0.1:3000               # explorer son propre site en local
curl -s -o /dev/null -w "%{http_code}\n" https://mon-site.ch/.env    # doit afficher 404
curl -s -o /dev/null -w "%{http_code}\n" https://mon-site.ch/api/    # doit afficher 404
```
- Scanner ZAP : https://www.zaproxy.org (mode « baseline » en Docker : `zaproxy/zap-stable zap-baseline.py -t https://mon-site.ch`)
- Cloudflare Turnstile : https://developers.cloudflare.com/turnstile
- Épingler les actions : https://github.com/mheap/pin-github-action
- S'entraîner : https://owasp.org/www-project-juice-shop

## Pièges vécus
- **Limiteur en mémoire** (MIS, Prodysos) : une instance serverless de plus et le compteur repart de zéro. Utile contre les robots simples, pas contre un attaquant déterminé.
- **Contact et LunchPicker n'ont aucune limitation de débit** : à traiter.
- **TruffleHog et SonarQube cassés au passage en privé** (P_183-CICD) : prévoir quels outils fonctionnent sur dépôt privé avant de les choisir.
- **Les actions ne sont épinglées par SHA que dans deux projets sur dix** : à généraliser.
- **Un honeypot qui renvoie une erreur** apprend au robot qu'il a été détecté : renvoyer un faux succès.
