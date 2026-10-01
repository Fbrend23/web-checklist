# 12 - Mise en production et suivi

## Pourquoi
La mise en ligne n'est pas la fin : il faut savoir que tout marche, être prévenu quand ça casse, et pouvoir revenir en arrière. Principe directeur : **rien ne doit réussir en silence**. Une panne doit faire du bruit.

## Règles

### Avant le jour J
- **Parcourir tous les fichiers de ce dépôt** (01 à 11) et cocher `CHECKLIST.md`.
- **Domaine, DNS, HTTPS** : certificat valide, redirection HTTP vers HTTPS, un seul domaine canonique (avec ou sans `www`).
- **Variables de production** différentes de celles de développement, secrets de production renouvelés.
- **Environnement de préproduction** quand c'est possible (protégé par mot de passe ou `noindex`).
- **Plan de retour arrière** : comment revenir à la version précédente en moins de 10 minutes.
- **Sauvegardes** : fichiers et base, restauration testée.
- **Contenus légaux** : mentions légales, politique de confidentialité, bannière de cookies seulement si on dépose des cookies (nLPD en Suisse, RGPD si visiteurs européens). Préférer des statistiques sans cookies.

### Le jour J
- Déployer en dehors des heures de pointe, avec un `dry_run` d'abord (voir 08).
- Vérifier les parcours critiques à la main : accueil, formulaire de contact (une vraie soumission, prévenir le destinataire), connexion.
- Vérifier les en-têtes (04), le SEO (11), Lighthouse (09) sur l'URL de production.
- Vérifier que `.env`, `.git` et `/api/` répondent en 404.

### Après
- **Suivi d'erreurs** : Sentry côté navigateur et fonctions, cartes sources envoyées à Sentry puis supprimées de `dist/`. Un tunnel limité si on veut contourner les bloqueurs de publicité.
- **Statistiques sans cookies** : GoatCounter ou analytics maison.
- **Disponibilité** : un moniteur externe (UptimeRobot, Better Stack) sur l'accueil et l'endpoint du formulaire.
- **Page d'état de publication** (`/publication/`) alimentée par `build.json` : on sait si le dernier déploiement est complet.
- **Chaque garde automatique échoue bruyamment** : build refusé sans sources, contrôle des liens morts, validation de schéma.
- **Mises à jour** : Dependabot, lecture du journal des modifications avant une version majeure, `npm audit` mensuel.
- **Journal de ce qui a été appris** : chaque incident ajoute une règle dans le dépôt de checklist et dans le fichier de contexte du projet.
- **Baisser le TTL DNS 48 h avant** une bascule ou un changement d'hébergeur (300 s), pour pouvoir revenir en arrière vite. Le remonter après.
- **Alerte d'expiration du certificat SSL** (J-15) dans l'outil de suivi (UptimeRobot, Better Stack).

## Checklist
- [ ] `CHECKLIST.md` entièrement cochée pour ce projet.
- [ ] Domaine, DNS et HTTPS valides, redirection vers le domaine canonique.
- [ ] Secrets et variables de production distincts de ceux de développement.
- [ ] Plan de retour arrière écrit et testé.
- [ ] Sauvegarde automatique et restauration testée.
- [ ] Mentions légales et politique de confidentialité en ligne.
- [ ] Pas de cookies de suivi, ou bannière de consentement conforme.
- [ ] Suivi d'erreurs actif (Sentry) et alerte reçue lors d'un test volontaire.
- [ ] Moniteur de disponibilité configuré.
- [ ] Statistiques sans cookies actives.
- [ ] Parcours critiques testés sur la production.
- [ ] Search Console vérifié, sitemap envoyé.
- [ ] Dependabot actif, rappel mensuel `npm audit`.
- [ ] Doc d'exploitation à jour (README : comment déployer, où sont les secrets, qui contacter).
- [ ] TTL DNS baissé 48 h avant la bascule.
- [ ] Alerte d'expiration SSL active.

## Exemples de mes projets

Les cinq gardes de la Matinale (`matinale-seoul/README.md`, section « Ce qui ne doit jamais réussir en silence ») : validation du schéma, liens HTTPS et non morts (404 et 403 traités différemment), liste blanche de sources, doublons, cohérence. Aucune relecture humaine : les gardes la remplacent.

Page d'état (`Maisallezfieu`) : `build.json` envoyé seul et en dernier, la page `/publication/` n'affiche « en ligne » qu'une fois tous les fichiers présents. Le README liste les « Points à ne pas casser » (fuseaux, minuteur de sécurité du rideau, scripts module différés).

Suivi d'erreurs (`Prodysos`) : Sentry, cartes sources en mode `hidden` envoyées puis supprimées, tunnel `sentry-tunnel` verrouillé (voir 05).

Statistiques sans cookies : GoatCounter (`Portail`, `site-photos-v2`), analytics maison (`mis.lawmission.net-V2`, `AnalyticsTracker.astro`).

Procédure d'exploitation (`platform-cms/README.md`) : vérifier RAM et version de MariaDB avant d'installer, guillemets autour des mots de passe, ne pas définir `PORT`, uploads hors du dossier de déploiement, licence liée à `PUBLIC_URL`.

Publication sur le serveur (`matinale-seoul/docs/parution-sur-serveur.md`) : réglage du minuteur systemd.

Déploiement en deux environnements (`Prodysos`) : préproduction manuelle puis production sur release, base migrée avant les fonctions et le frontend.

Application Android (`Prodysos`) : publiée sur le Play Store en TWA, la PWA doit donc rester propre (manifeste, service worker, `version.json`).

## Commandes et outils
```bash
curl -sI https://mon-site.ch | head -10                       # état, en-têtes
curl -s -o /dev/null -w "%{http_code} %{time_total}s\n" https://mon-site.ch
for u in .env .git/config api/; do curl -s -o /dev/null -w "$u %{http_code}\n" https://mon-site.ch/$u; done
npm outdated                                                   # dépendances en retard
npm audit --audit-level=high
```
- Erreurs : https://sentry.io
- Disponibilité : https://uptimerobot.com, https://betterstack.com
- Statistiques sans cookies : https://www.goatcounter.com
- Vérifier le DNS et le HTTPS : https://www.ssllabs.com/ssltest et https://dnschecker.org
- Mentions légales et protection des données (Suisse) : https://www.edoeb.admin.ch
- Générer une politique de confidentialité : https://www.datenschutz-generator.ch

## Pièges vécus
- **Un déploiement « vert » mais faux** : le build retombait en silence sur des données locales faute de secret. D'où le refus de build sans sources.
- **Site tombé en 500 par un `.htaccess` écrasé** (23 septembre 2026) : toujours vérifier la page d'accueil après un déploiement.
- **Une licence liée à la mauvaise URL** (Directus) : vérifier `PUBLIC_URL` avant le premier démarrage.
- **Tester le formulaire de contact en vrai** crée une vraie demande : prévenir le destinataire ou utiliser un environnement de test.
- **Oublier de renouveler les secrets de développement** avant la production : ne jamais réutiliser.
