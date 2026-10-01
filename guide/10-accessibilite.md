# 10 - Accessibilité

## Pourquoi
Un site accessible est utilisable au clavier, au lecteur d'écran, sans JavaScript et par des personnes sensibles au mouvement. C'est aussi une obligation légale dans plusieurs cas, et ça aide le SEO.

## Règles
- **HTML sémantique d'abord** : `<nav>`, `<main>`, `<button>` pour une action, `<a>` pour un lien. ARIA ne vient qu'en complément.
- **`lang` sur `<html>`** (et sur les passages dans une autre langue).
- **Lien d'évitement** (« Aller au contenu ») en premier.
- **Clavier** : tout est utilisable sans souris, focus visible, ordre logique.
- **Menus et fenêtres** : le focus est piégé dans la fenêtre ouverte, `Échap` la ferme, le focus revient sur le bouton qui l'a ouverte. Préférer `<dialog>` natif.
- **États ARIA** : `aria-expanded` et `aria-controls` (menu burger), `aria-current` (page ou langue active), `aria-pressed` (boutons de filtre), `aria-live="polite"` (messages de formulaire), `role="alert"` (erreurs).
- **Éléments décoratifs** cachés aux lecteurs d'écran (`aria-hidden="true"`), y compris les pistes dupliquées d'un défilement (marquee).
- **Liens qui s'ouvrent dans un nouvel onglet** : le dire (« s'ouvre dans une nouvelle fenêtre »).
- **Mouvement** : respecter `prefers-reduced-motion` en CSS **et** en JS (particules, timeline, loader).
- **Fonctionner sans JS** (amélioration progressive) : le contenu et le formulaire marchent, le JS améliore.
- **Contrastes** : texte 4,5:1, grand texte 3:1.
- **Images** : `alt` utile (un helper partagé pour générer les textes alternatifs), `alt=""` pour le décoratif.
- **Formulaires** : un `<label>` par champ, `required`, `maxlength`, messages d'erreur liés au champ.
- **Responsive** : tester à 390, 820 et 1440 px.
- **Squelettes de chargement** qui reproduisent la vraie mise en page.
- **Cibles tactiles** : 24x24 px minimum (WCAG 2.2, critère 2.5.8), 44x44 px conseillé sur mobile.
- **Ne jamais bloquer le zoom** : pas de `user-scalable=no` ni `maximum-scale=1` dans la balise viewport.
- **Mode sombre natif** : `color-scheme: light dark` pour que barres de défilement, champs et sélecteurs suivent le thème.

## Checklist
- [ ] `lang` sur `<html>`, un seul `<h1>`, hiérarchie de titres logique.
- [ ] Lien d'évitement présent.
- [ ] Navigation entière faisable au clavier, focus visible.
- [ ] Menus et fenêtres : piège de focus, `Échap`, retour du focus.
- [ ] `aria-expanded`, `aria-current`, `aria-pressed`, `aria-live` posés là où il faut.
- [ ] `prefers-reduced-motion` géré (CSS et JS).
- [ ] Site utilisable avec JavaScript désactivé.
- [ ] Contrastes vérifiés.
- [ ] Textes alternatifs pertinents, décoratif en `alt=""`.
- [ ] Champs avec `<label>`, erreurs annoncées.
- [ ] Test à 390, 820 et 1440 px.
- [ ] Audit automatique (axe ou Lighthouse) sans erreur grave.
- [ ] Test manuel au lecteur d'écran sur le parcours principal.
- [ ] Cibles cliquables d'au moins 24x24 px (44x44 sur mobile).
- [ ] Zoom non bloqué dans la balise viewport.
- [ ] `color-scheme` déclaré si le site a un mode sombre.

## Exemples de mes projets

Test e2e du focus (`Portail/e2e/smoke.spec.ts`) : `Échap` ferme la fenêtre de dialogue et le bouton de départ retrouve le focus.

Mouvement réduit (`Portail`) : `prefers-reduced-motion` géré en JS et en CSS (particules, timeline, loader, marquee). La piste dupliquée du marquee est cachée aux lecteurs d'écran, les SVG décoratifs ont `aria-hidden`.

Menu et galerie (`Cliffhanger-/src/layouts/Base.astro`) : lien d'évitement, menu qui piège le focus et se ferme avec `Échap`, visionneuse de galerie en `<dialog>` natif, variante `sans-js:` qui fonctionne sans JavaScript, mises en page testées à 390, 820 et 1440 px.

Filtres et liens (`matinale-seoul`) : `aria-current` dans la navigation, `aria-expanded`/`aria-controls` sur le burger, `aria-pressed` sur les boutons de filtre, liens externes annoncés « (s'ouvre dans une nouvelle fenêtre) », lien d'évitement dans `BaseLayout.astro`.

Formulaire (`Maisallezfieu/src/components/BookingForm.astro`) : `maxlength`, `required`, honeypot caché, message `role="status" aria-live="polite"`.

Application (`Prodysos`) : `<ErrorState role="alert">`, squelettes qui reproduisent la vraie mise en page, règle « 390 px d'abord », classe `sr-only` dans une quinzaine de fichiers.

Amélioration progressive (`site-photos-v2`) : fonctionne sans JS, retour du focus sur la galerie à la fermeture.

## Commandes et outils
```bash
npx lighthouse https://mon-site.ch --only-categories=accessibility --view
npm i -D @axe-core/playwright          # axe dans les tests e2e
```
Exemple de test axe à ajouter (lacune actuelle de tous mes projets) :
```ts
import AxeBuilder from '@axe-core/playwright'
const resultats = await new AxeBuilder({ page }).analyze()
expect(resultats.violations).toEqual([])
```
- Audit automatique : https://www.deque.com/axe (extension navigateur gratuite), https://wave.webaim.org
- Contrastes : https://webaim.org/resources/contrastchecker
- Référentiel : https://www.w3.org/WAI/WCAG22/quickref
- Lecteurs d'écran : NVDA (Windows, gratuit) https://www.nvaccess.org, VoiceOver (macOS)
- Simuler le mouvement réduit : outils de développement, panneau Rendu, `prefers-reduced-motion`

## Pièges vécus
- **Aucun test d'accessibilité automatique** dans aucun projet : ajouter axe aux tests e2e.
- **LunchPicker** : pas d'`aria-live` ni de gestion du mouvement réduit repérés.
- **Un projet perso** : pas de pratique d'accessibilité notable, seulement un guide UX (`doc/Guidelines UX.md`).
- **Les scripts « module » sont différés** (Maisallezfieu) : `DOMContentLoaded` peut déjà être passé, tester `document.readyState` d'abord.
- **Un minuteur de sécurité** sur les rideaux et animations d'ouverture évite qu'un échec JS bloque le contenu.
