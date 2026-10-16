<script setup lang="ts">
// Info-bulle de la charte (q-tooltip) : fond encre, texte papier 12 px, au-dessus de l'élément,
// après un court délai. Complète un libellé ou une icône déjà nommés pour les lecteurs d'écran
// (aria-label, texte masqué) : elle ne porte jamais seule une information indispensable, car
// elle ne s'affiche pas sur un écran tactile.
withDefaults(defineProps<{ position?: 'top' | 'bottom' }>(), { position: 'top' });
</script>

<template>
  <q-tooltip
    :anchor="position === 'top' ? 'top middle' : 'bottom middle'"
    :self="position === 'top' ? 'bottom middle' : 'top middle'"
    :offset="[0, 6]"
    :delay="400"
    class="app-tooltip"
  >
    <slot />
  </q-tooltip>
</template>
