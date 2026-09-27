# 📱 Mobile first & performance

Les deux exigences prioritaires de RIMeF : **l'application charge vite** et
**elle est pleinement utilisable sur téléphone et tablette**.

## Mobile first
- Concevoir chaque écran **d'abord pour le téléphone** (≤ 600 px), puis
  l'enrichir pour la tablette (≤ 1024 px) et l'ordinateur.
- S'appuyer sur la grille et les breakpoints Quasar (`col-12 col-md-6`,
  `$q.screen.lt.md`) plutôt que sur des media queries ad hoc.
- Zones tactiles d'au moins **44 × 44 px** ; pas d'action accessible
  uniquement au survol (`hover`).
- Aucun défilement horizontal de page ; les tableaux larges passent en
  cartes ou en `q-table` avec `grid` sur mobile.
- Vérifier chaque écran aux trois tailles avant de le considérer terminé.

## Performance
- **Routes chargées à la demande** : `component: () => import('...')`.
- Composants Quasar importés à la demande (comportement par défaut du
  plugin Vite) ; pas d'import global de bibliothèques lourdes.
- Images : format **WebP/SVG**, dimensions adaptées, `loading="lazy"` hors
  premier écran ; jamais d'image de plusieurs Mo.
- Polices : limiter les graisses chargées, `font-display: swap`.
- Côté serveur : compression gzip/brotli et cache long sur les fichiers
  versionnés du build.
- Réponses API paginées ; pas de chargement de listes complètes.

## Accessibilité
- Contrastes conformes WCAG AA, libellés explicites (`aria-label` sur les
  boutons icône), navigation clavier possible.
