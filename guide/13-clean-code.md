# 13 - Clean code

## Pourquoi
Le code est lu bien plus souvent qu'il n'est écrit. Un code propre se relit, se corrige et se passe à quelqu'un d'autre sans rien deviner. Le but n'est pas l'élégance : c'est de réduire le coût de la prochaine modification.

## Règles
- **Des noms qui disent l'intention** : `delaiExpirationMs` plutôt que `d`, `data`, `tmp`. Si un nom demande un commentaire, le nom est mauvais. Un booléen se lit comme une question (`estExpire`, `aDroitAcces`).
- **Des fonctions courtes, une seule responsabilité** : si on doit écrire « et » pour décrire ce qu'elle fait, la couper en deux. Un seul niveau d'abstraction par fonction.
- **Peu de paramètres** : au-delà de 3, passer un objet nommé.
- **Pas d'effet de bord caché** : une fonction qui s'appelle `lireX` ne modifie rien. Séparer calculer et agir.
- **Retours anticipés** : traiter les cas d'erreur en premier et sortir, plutôt que d'imbriquer `if` sur `if` (3 niveaux maximum).
- **Pas de nombre ou de texte magique** : une constante nommée (`UN_JOUR`, `PAR_PAGE`), au même endroit que sa logique.
- **DRY sans excès** : dédupliquer à la troisième répétition (règle de trois). Deux morceaux qui se ressemblent mais changent pour des raisons différentes ne se fusionnent pas.
- **KISS et YAGNI** : le plus simple qui marche, rien « pour plus tard » (voir le principe « Le plus simple qui marche » du README).
- **Les commentaires disent le pourquoi**, jamais le quoi. Un commentaire qui répète le code ment dès la prochaine modification.
- **Zéro code mort** : ni fonction inutilisée, ni bloc commenté, ni `console.log` oublié. Git garde l'historique.
- **Rien ne réussit en silence** : pas de `catch` vide, pas de valeur par défaut qui cache une panne. Une erreur porte le contexte (quoi, où, avec quoi).
- **Un fichier, une responsabilité** : un fichier qui dépasse 300 lignes ou mélange accès aux données, logique et affichage se découpe.
- **SOLID en version courte** : une raison de changer par module (S), dépendre d'une interface ou d'un paramètre plutôt que d'un import en dur pour pouvoir tester (D). Le reste (O, L, I) quand le besoin se présente.
- **Règle du boy-scout** : laisser le fichier un peu plus propre qu'on l'a trouvé, dans un commit séparé (`refactor:`) de celui qui change le comportement.
- **Refactorer avec des tests verts** : jamais de nettoyage sans filet (voir 07).

## Checklist
- [ ] Noms explicites, aucune abréviation obscure, booléens en question.
- [ ] Fonctions courtes à une seule responsabilité, 3 paramètres au plus.
- [ ] Retours anticipés, pas plus de 3 niveaux d'imbrication.
- [ ] Aucun nombre ou texte magique : constantes nommées.
- [ ] Pas de duplication évidente (jscpd), pas d'abstraction prématurée.
- [ ] Commentaires = pourquoi, aucun bloc de code commenté.
- [ ] Aucun code mort (knip, `no-unused-vars`).
- [ ] Aucune erreur avalée, messages d'erreur avec contexte.
- [ ] Fichiers de moins de 300 lignes ou justifiés, un rôle par fichier.
- [ ] Règles ESLint de complexité actives et la CI les applique.

## Exemples de mes projets
Constantes nommées, au-dessus de leur usage :
`Cliffhanger-/src/lib/dates.js` : `const UN_JOUR = 864e5;`
`Cliffhanger-/src/lib/directus.js` : `const PAR_PAGE = 1000;`
`horaire_2.0/backend/config/timetable.ts` : `SLOT_DURATION_MIN = 45`, `PASSING_MIN = 5`.
`Horaire/src/logic/weatherService.js` : `const CACHE_DURATION = 15 * 60 * 1000 // 15 minutes` (l'unité écrite dans le calcul).

Retour anticipé et erreur qui porte le contexte (`Cliffhanger-/src/lib/directus.js`) :
```js
if (!entrée) throw new Error(`aperçu demandé pour ${id} avant son téléchargement`);
```
Le message dit quoi, pour quel `id`, et dans quel ordre l'appel était attendu : on trouve la cause sans débogueur.

Même idée dans `Cliffhanger-/src/lib/contenu.js` : l'erreur dit quoi faire (« ouvrir Réglages dans le Studio et enregistrer »).

Contre-exemple (anonymisé) :
```js
function traiter(d, f) {   // quoi ? pourquoi ?
  try { return d.map(f) } catch (e) {}   // la panne disparaît
}
```
Nom vide de sens, `catch` vide : un bug ici ne se voit jamais.

## Commandes et outils
```bash
npm i -D knip jscpd
npx knip                      # fichiers, exports et dépendances inutilisés
npx jscpd src                 # blocs dupliqués
```
Règles ESLint utiles (flat config) :
```js
rules: {
  complexity: ['warn', 10],
  'max-depth': ['warn', 3],
  'max-params': ['warn', 3],
  'max-lines-per-function': ['warn', 60],
  'no-magic-numbers': ['warn', { ignore: [0, 1, -1], ignoreArrayIndexes: true }],
  'no-unused-vars': 'error',
  'no-empty': 'error',
  'no-console': 'warn',
}
```
- Livre *Clean Code* (Robert C. Martin) et *Refactoring* (Martin Fowler)
- Catalogue de refactorings : https://refactoring.guru/fr

## Pièges vécus
- Une abstraction écrite « pour plus tard » (classe, fabrique, config) qui n'a jamais eu un deuxième usage : elle coûte à lire et à maintenir pour rien. Attendre le deuxième cas réel.
- Dédupliquer trop tôt : deux blocs qui se ressemblaient ont divergé, et la fonction commune s'est remplie de `if`. Mieux vaut un peu de répétition qu'une mauvaise abstraction.
- Un commentaire laissé après une modification du code qui dit maintenant autre chose : pire que pas de commentaire.
- `no-magic-numbers` strict partout devient du bruit : on le règle (valeurs ignorées) ou on le limite à la logique métier.
- Mélanger un refactor et un changement de comportement dans un même commit rend la relecture et le retour arrière difficiles.
