# 06 - Base de données

## Pourquoi
Les données sont la seule chose qu'on ne peut pas redéployer. Une mauvaise migration, un droit trop large ou une requête concaténée se paient très cher.

## Règles

### Accès et droits
- **Requêtes paramétrées uniquement** (`$1`, `?`), ou un ORM. Jamais de concaténation de chaînes.
- **Moindre privilège** : un compte applicatif qui n'a que les `GRANT` nécessaires (pas de DDL, pas de `DROP`). Pas de compte admin en production.
- **Vues et procédures stockées** comme couche de contrôle d'accès quand on doit limiter ce qu'un rôle voit (`SQL SECURITY DEFINER` à bon escient).
- **RLS (Row Level Security)** activée sur chaque table exposée, avec des politiques explicites. Aucune politique pour le rôle `public`, aucun `GRANT` à `anon` par défaut.
- **Pas d'accès direct du navigateur à la base** quand il n'y a pas d'authentification : passer par un endpoint serveur filtré (une RLS sans auth ne serait qu'un `using (true)`).
- **Fonctions `SECURITY DEFINER`** : toujours `SET search_path = public` et les appeler avec le préfixe de schéma.
- **Journal d'audit** (table + trigger) pour les actions sensibles.

### Migrations
- **Versionnées dans git**, jamais de modification à la main en production.
- **Idempotentes** (`IF NOT EXISTS`, `CREATE OR REPLACE`) pour pouvoir les rejouer.
- **Ordre sans coupure** pour une nouvelle colonne obligatoire : `ADD COLUMN` (nullable) puis `SET DEFAULT`, puis `UPDATE` des lignes existantes, puis `SET NOT NULL`. Sur une grosse table, attention aux verrous.
- **Essai à blanc** : `BEGIN; ...; ROLLBACK;` avant de valider.
- **Migrer la base avant** de déployer le code qui en dépend.
- **GRANT obligatoires par table** sur Supabase (changement de plateforme) : les inclure dans chaque migration de création de table.

### Concurrence
- **Mise à jour atomique** : compare-and-swap (`update ... where status = $from returning id`), `count(*)` plutôt que lire puis incrémenter, `pg_advisory_xact_lock` pour les invariants sensibles aux courses.
- **Pas de tâches planifiées qui empêchent la base de dormir** (Neon) : purge par cron HTTP plutôt que `pg_cron`.

### Sauvegardes
- **Sauvegarde automatique** et **restauration testée** : une sauvegarde jamais restaurée n'existe pas.
- **Les fichiers téléversés** vivent hors du dossier de déploiement (sinon un déploiement les écrase).
- **UUIDv7 plutôt qu'UUIDv4** pour les clés non séquentielles : l'horodatage en tête garde l'ordre chronologique et évite la fragmentation des index B-Tree (natif dans Postgres 18 : `uuidv7()`).
- **Suppression et RGPD / nLPD** : avec un `deleted_at` (suppression logique), la RLS doit le filtrer partout, et une purge définitive automatique doit exister (droit à l'effacement).

## Checklist
- [ ] Aucune requête construite par concaténation (`grep -rn "query(\`\|execute(\`" src/ server/`).
- [ ] Compte applicatif avec droits minimaux, aucun compte admin en production.
- [ ] RLS activée sur toutes les tables exposées, aucune politique `public`.
- [ ] `GRANT` explicites dans chaque migration de table.
- [ ] Migrations idempotentes, versionnées, rejouées sur une base vide en CI.
- [ ] Nouvelles colonnes obligatoires introduites en 4 étapes.
- [ ] Essai `BEGIN ... ROLLBACK` avant toute migration risquée.
- [ ] Tests de base (pgTAP) pour les invariants de sécurité.
- [ ] Sauvegardes automatiques et restauration testée.
- [ ] Uploads hors du dossier de déploiement.
- [ ] Clés primaires en UUIDv7 (ou `bigint` identity) plutôt qu'UUIDv4.
- [ ] Suppression logique : filtrée par la RLS, purge définitive planifiée.

## Exemples de mes projets

Test pgTAP d'invariants de sécurité (`Prodysos/supabase/tests/07_rls_anon_hardening.test.sql`) : vérifie dans le catalogue qu'aucune politique ne s'applique au rôle `public` et que `anon` n'a aucun `GRANT` sur les tables.

Lancer les tests de base (`Prodysos/package.json`) : `npm run test:db`, avec un workflow dédié (`db-tests.yml`) filtré sur `supabase/migrations/**` et `supabase/tests/**`.

Compare-and-swap (`LunchPicker/server/utils/db.ts`) :
```sql
update rooms set status = $to where id = $1 and status = $from returning id
```
Code de salle avec `crypto.randomInt` (`server/utils/roomCode.ts`). Base en mémoire PGlite pour tester le vrai SQL (`test/unit/db.spec.ts`).

Règles de migration (fichier de contexte de `Prodysos/`) : DDL idempotent, ordre sans coupure, avertissement de verrou sur grosses tables, liste de contrôle en 3 endroits pour ajouter une permission, essai `BEGIN ... ROLLBACK`.

Cours 183, audit de base (`183-SecuriteApplication/exercices/db-audit/`) : vues comme couche d'accès, table d'audit `t_audit_access_absences` avec triggers, `GRANT` par utilisateur.
```sql
CREATE USER 'lecteur'@'%' IDENTIFIED BY '...';
GRANT SELECT ON ecole.v_absences_publiques TO 'lecteur'@'%';
SHOW GRANTS FOR 'lecteur'@'%';
```

Isolation entre clients (`platform-cms/scripts/lib/permissions.mjs`) : trois politiques par client, le jeton de build ne lit que `status: published`, les fichiers sont filtrés par dossier du client.

## Commandes et outils
```bash
supabase db reset                      # base locale vide + toutes les migrations
supabase migration new nom_migration   # nouvelle migration
supabase test db                       # tests pgTAP
supabase db diff --schema public       # voir ce qui diffère
```
- pgTAP : https://pgtap.org
- Bonnes pratiques Postgres (Supabase) : https://supabase.com/docs/guides/database/postgres/row-level-security
- Explorer un plan de requête : https://explain.dalibo.com

## Pièges vécus
- **Directus applique `validation` et non `permissions` à la création** (platform-cms) : la règle de dossier doit aussi être dans `validation`, sinon les fichiers téléversés à la racine deviennent orphelins (trois déploiements cassés, 3 septembre 2026).
- **Vérifier la RAM et la version de MariaDB avant d'installer** un CMS sur un petit hébergement.
- **Pool de connexions** : `max` à 3 et délai d'inactivité inférieur au `wait_timeout` de MariaDB.
- **`.env` d'un labo de cours versionné** : voir 03.
