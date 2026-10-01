# 03 - Configuration et secrets

## Pourquoi
La plupart des fuites viennent d'un secret mal rangé, pas d'une faille de code. Un secret commité doit être considéré comme public pour toujours.

## Règles
- **Un secret ne vit que côté serveur.** Jamais dans le code, jamais dans un fichier commité, jamais dans le bundle du navigateur.
- **`.env.example` commité, `.env` ignoré.** L'exemple documente chaque variable avec un commentaire (à quoi elle sert, où la trouver, quel danger).
- **Valider les variables au démarrage** : le build ou le serveur échoue immédiatement si une variable manque ou si un secret est référencé côté client.
- **Moindre privilège** : un jeton par usage. Un jeton de build en lecture seule, un jeton d'écriture séparé, jamais le jeton admin en local.
- **Ce qui est « public » doit l'être volontairement** : une clé publiable (Supabase anon, clé reCAPTCHA site) peut aller côté client, un jeton de service jamais.
- **Rotation** : pouvoir révoquer un jeton sans casser le reste. Noter qui l'utilise.
- **Secrets de CI** dans les secrets du dépôt, pas dans le YAML. Penser que GitHub ne masque pas les variantes encodées (mot de passe encodé en URL).
- **Après l'installation**, supprimer les mots de passe d'amorçage du `.env` (ex. `ADMIN_PASSWORD`) et `chmod 600 .env` sur le serveur.
- **Citer les valeurs avec des guillemets** : un `#` non protégé tronque un mot de passe.
- **Gitleaks en pre-commit** : `gitleaks git --pre-commit --staged` dans Husky bloque le secret avant le commit, donc pas d'historique à réécrire (l'ancien `gitleaks protect --staged` est déprécié).
- **Valider `process.env` / `import.meta.env` hors Astro** avec `@t3-oss/env-core` ou un petit schéma zod exécuté au démarrage ou au build.

## Checklist
- [ ] `.env` est dans `.gitignore`, `.env.example` est commité avec des valeurs factices.
- [ ] Chaque variable de `.env.example` a un commentaire.
- [ ] Validation au démarrage (schéma de variables) : un secret manquant fait échouer le build.
- [ ] Aucun secret dans le code client (vérifier le bundle : `grep -r "sk_\|secret" dist/`).
- [ ] Jetons séparés : lecture seule pour le build, écriture pour l'import, aucun jeton admin dans les workflows.
- [ ] Secrets de CI dans les secrets GitHub, pas dans le dépôt.
- [ ] Scan de secrets sur l'historique (gitleaks ou trufflehog), voir 05.
- [ ] Mots de passe d'amorçage supprimés, droits `600` sur le `.env` serveur.
- [ ] Plan de rotation noté (où changer le secret, qui est impacté).
- [ ] Gitleaks branché dans le hook de pré-commit.
- [ ] Schéma de variables validé au build hors Astro (zod ou `@t3-oss/env-core`).

## Exemples de mes projets
Échec du build si un secret fuit côté client (`matinale-seoul/astro.config.mjs`, `Cliffhanger-/astro.config.mjs`) :
```js
env: {
  schema: {
    DIRECTUS_TOKEN: envField.string({ context: 'server', access: 'secret' }),
    KAKAO_MAPS_APP_KEY: envField.string({ context: 'client', access: 'public', optional: true }),
  },
},
```

Secrets côté serveur uniquement dans Nuxt (`LunchPicker/nuxt.config.ts`) : les secrets sont dans `runtimeConfig` à la racine, jamais sous `runtimeConfig.public`.

Deux jetons Directus à droits différents (`matinale-seoul/.env.example`) : un jeton d'ingestion (écriture, pas de suppression) et un jeton de build (lecture seule, contenu publié). Le commentaire explique pourquoi le jeton admin ne doit jamais servir en local.

`.env.example` commenté (`platform-cms/.env.example`) :
```ini
# À ne mettre QUE derrière un proxy de confiance : ailleurs, l'en-tête
# X-Forwarded-For est forgeable et le limiteur devient contournable.
IP_TRUST_PROXY=true
ACCESS_TOKEN_TTL=15m
REFRESH_TOKEN_COOKIE_SECURE=true
```

Validation au démarrage côté serveur : `start/env.ts` (un projet AdonisJS perso) et `mis.lawmission.net-V2/src/utils/auth.ts` (refuse de démarrer sans `PORTAL_JWT_SECRET`, avertit si moins de 32 caractères).

Secrets d'un formulaire PHP gardés dans l'environnement de l'hébergeur et non dans GitHub (`Contact/api/envoyer.php`).

## Commandes et outils
```bash
cp .env.example .env                   # première configuration locale
git check-ignore -v .env               # vérifier que .env est bien ignoré
git ls-files | grep -i "\.env"         # aucun .env ne doit apparaître (sauf .env.example)
openssl rand -base64 48                # générer un secret fort
gh secret set DIRECTUS_TOKEN           # ajouter un secret de dépôt GitHub
chmod 600 .env                         # sur le serveur
```
- Détection de secrets : https://github.com/gitleaks/gitleaks et https://github.com/trufflesecurity/trufflehog
- Vérifier si un mot de passe ou une adresse a fuité : https://haveibeenpwned.com

## Pièges vécus
- Un `.env` d'exercice se trouvait dans l'arborescence d'un dossier de cours (183-SecuriteApplication) : toujours `git ls-files` avant de pousser.
- **Mot de passe encodé en URL dans la sortie de lftp** : GitHub ne le masque pas. Les workflows de déploiement le filtrent avec `sed`.
- **La licence Directus se lie à `PUBLIC_URL` au premier usage** : ne pas la gaspiller sur une mauvaise URL.
- **Ne jamais définir `PORT`** sur l'hébergement Node d'Infomaniak (platform-cms) : l'hébergeur le gère.
- Un `#` dans un mot de passe non cité tronque la valeur sans erreur.
