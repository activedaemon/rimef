// Icônes Tabler (police @tabler/icons-webfont) branchées sur Quasar.
// - <q-icon name="search" />  → <i class="ti ti-search" />
// - "ti ti-xxx" explicite      → conservé tel quel
// - Les composants Quasar utilisent en interne des noms Material
//   (arrow_drop_down, close…) : ils sont traduits vers leur équivalent Tabler.

const QUASAR_INTERNAL_TO_TABLER: Record<string, string> = {
  arrow_drop_down: 'chevron-down',
  arrow_drop_up: 'chevron-up',
  arrow_back: 'arrow-left',
  arrow_forward: 'arrow-right',
  arrow_upward: 'arrow-up',
  arrow_downward: 'arrow-down',
  cancel: 'x',
  close: 'x',
  clear: 'x',
  check: 'check',
  done: 'check',
  check_circle: 'circle-check',
  add: 'plus',
  remove: 'minus',
  keyboard_arrow_left: 'chevron-left',
  keyboard_arrow_right: 'chevron-right',
  keyboard_arrow_up: 'chevron-up',
  keyboard_arrow_down: 'chevron-down',
  expand_more: 'chevron-down',
  expand_less: 'chevron-up',
  chevron_left: 'chevron-left',
  chevron_right: 'chevron-right',
  first_page: 'chevrons-left',
  last_page: 'chevrons-right',
  menu: 'menu-2',
  search: 'search',
  info: 'info-circle',
  warning: 'alert-triangle',
  error: 'alert-octagon',
  notifications: 'bell',
  radio_button_unchecked: 'circle',
  radio_button_checked: 'circle-dot',
  check_box: 'square-check',
  check_box_outline_blank: 'square',
  indeterminate_check_box: 'square-minus',
};

export function tablerIconMapFn(iconName: string): { cls: string } {
  if (iconName.startsWith('ti ti-') || iconName.startsWith('ti-')) {
    return { cls: iconName.startsWith('ti ') ? iconName : `ti ${iconName}` };
  }
  const tablerName = QUASAR_INTERNAL_TO_TABLER[iconName] ?? iconName;
  return { cls: `ti ti-${tablerName}` };
}
