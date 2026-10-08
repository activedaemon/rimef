// Feuille du design system (amande 100 × 40, fiche LeafMotif), seul ornement de la charte.

export const LEAF_PATH = 'M1 20 C 22 -1, 66 -3, 99 20 C 66 43, 22 41, 1 20 Z';

/**
 * Transformation SVG plaçant une feuille de largeur × hauteur dont la base est en (x, y),
 * tournée de `angle` degrés (équivalent du <use href="#leaf"> de la maquette).
 */
export function leafTransform(
  x: number,
  y: number,
  angle: number,
  width: number,
  height: number
): string {
  return `translate(${x} ${y}) rotate(${angle}) translate(0 ${-height / 2}) scale(${width / 100} ${height / 40})`;
}
