<script setup lang="ts">
// Fenêtre « Ma participation », ouverte par « J'y participe » / « Je participe » (fiche, Agenda,
// accueil) : jours de présence si l'événement dure plusieurs jours (tous cochés par défaut, au
// moins un), « Disponible pour se retrouver sur place », note facultative. Déjà inscrite : mise à
// jour, ou « Ne plus participer ». Sans participation connue (Agenda, accueil), la fiche est
// chargée pour préremplir. Habillage des fenêtres de la messagerie (.message-dialog), feuille en
// bas sur téléphone. Émet `saved` avec la participation qui en résulte.
import { useQuasar } from 'quasar';
import { computed, ref, watch } from 'vue';

import AppButton from '@/components/AppButton.vue';
import { useNotify } from '@/composables/useNotify';
import { formatDateRange } from '@/lib/dates';
import { extractApiError } from '@/lib/http';
import {
  eventDays,
  fetchEvent,
  saveParticipation,
  setParticipation,
  weekdayLabel,
  type AgendaEvent,
  type Attendance,
} from '../services/events';

const NOTE_MAX_LENGTH = 150;

const props = withDefaults(
  defineProps<{
    event: Pick<AgendaEvent, 'id' | 'slug' | 'title' | 'starts_at' | 'ends_at'>;
    participating: boolean;
    /** Participation actuelle ; absente = chargée depuis la fiche si elle participe. */
    attendance?: Attendance | null;
    wantsToMeet?: boolean;
    /** « Proposer de se retrouver sur place » : case déjà cochée à l'ouverture. */
    presetMeet?: boolean;
  }>(),
  { attendance: undefined, wantsToMeet: false, presetMeet: false }
);
const open = defineModel<boolean>({ required: true });
const emit = defineEmits<{ saved: [participating: boolean] }>();

const $q = useQuasar();
const notify = useNotify();

const allDays = computed(() => eventDays(props.event));
const dates = computed(() => formatDateRange(props.event.starts_at, props.event.ends_at));

const days = ref<string[]>([]);
const meet = ref(false);
const note = ref('');
const loading = ref(false);
const saving = ref(false);

function fill(attendance: Attendance | null | undefined, wantsToMeet: boolean): void {
  days.value = attendance?.days?.filter((day) => allDays.value.includes(day)) ?? [...allDays.value];
  if (days.value.length === 0) days.value = [...allDays.value];
  note.value = attendance?.note ?? '';
  meet.value = wantsToMeet || props.presetMeet;
}

// Immédiat : la fenêtre peut être créée déjà ouverte (Agenda, accueil : v-if au premier clic)
watch(
  open,
  async (value) => {
    if (!value) return;
    fill(props.attendance, props.wantsToMeet);
    if (!props.participating || props.attendance !== undefined) return;

    loading.value = true;
    try {
      const event = await fetchEvent(props.event.slug);
      fill(event.attendance, event.wants_to_meet);
    } catch {
      // Préremplissage par défaut : tous les jours, sans note
    } finally {
      loading.value = false;
    }
  },
  { immediate: true }
);

const canSave = computed(() => days.value.length > 0 && !saving.value && !loading.value);

async function save(): Promise<void> {
  if (!canSave.value) return;
  saving.value = true;
  try {
    await saveParticipation(props.event.id, {
      days: days.value.length === allDays.value.length ? null : days.value,
      note: note.value.trim() || null,
      wants_to_meet: meet.value,
    });
    notify.success(
      props.participating
        ? 'Votre participation est mise à jour.'
        : `C’est noté : vous participez à « ${props.event.title} ».`
    );
    open.value = false;
    emit('saved', true);
  } catch (error) {
    notify.error(extractApiError(error, 'Votre participation n’a pas pu être enregistrée.'));
  } finally {
    saving.value = false;
  }
}

async function withdraw(): Promise<void> {
  saving.value = true;
  try {
    await setParticipation(props.event.id, false);
    notify.success(`Votre participation à « ${props.event.title} » est retirée.`);
    open.value = false;
    emit('saved', false);
  } catch (error) {
    notify.error(extractApiError(error, 'Votre participation n’a pas pu être retirée.'));
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <q-dialog v-model="open" :position="$q.screen.lt.sm ? 'bottom' : 'standard'">
    <q-card
      class="message-dialog participation-dialog"
      role="dialog"
      aria-labelledby="participation-title"
    >
      <div class="message-dialog__head">
        <div>
          <h2 id="participation-title">Ma participation</h2>
          <p>{{ event.title }} · {{ dates }}</p>
        </div>
        <q-btn
          v-close-popup
          flat
          round
          icon="x"
          aria-label="Fermer"
          class="message-dialog__close"
        />
      </div>

      <form @submit.prevent="save">
        <fieldset v-if="allDays.length > 1" class="participation-dialog__days">
          <legend class="field-label">Jours de présence</legend>
          <q-checkbox
            v-for="day in allDays"
            :key="day"
            v-model="days"
            :val="day"
            :label="weekdayLabel(day)"
            :disable="loading"
            class="participation-dialog__option"
          />
          <p v-if="days.length === 0" class="participation-dialog__error" role="alert">
            Choisissez au moins un jour de présence.
          </p>
        </fieldset>

        <q-checkbox
          v-model="meet"
          :disable="loading"
          label="Disponible pour se retrouver sur place"
          class="participation-dialog__option participation-dialog__meet"
        />

        <label class="field-label" for="participation-note">Note (facultative)</label>
        <q-input
          v-model="note"
          for="participation-note"
          outlined
          dense
          :maxlength="NOTE_MAX_LENGTH"
          counter
          :disable="loading"
          placeholder="Ex. : disponible en soirée pour échanger"
          class="participation-dialog__note"
        />

        <div class="participation-dialog__foot">
          <q-btn
            v-if="participating"
            flat
            no-caps
            label="Ne plus participer"
            :disable="saving"
            class="link-more participation-dialog__withdraw"
            @click="withdraw"
          />
          <AppButton
            type="submit"
            :label="participating ? 'Enregistrer' : 'Confirmer ma participation'"
            :loading="saving"
            :disable="!canSave"
            class="participation-dialog__save"
          />
        </div>
      </form>
    </q-card>
  </q-dialog>
</template>

<style lang="scss" scoped>
.participation-dialog {
  &__days {
    display: grid;
    margin: 0 0 var(--s-2);
    padding: 0;
    border: 0;

    legend {
      padding: 0;
    }
  }

  // Zone tactile de 44 px par option
  &__option {
    min-height: 44px;
    margin-left: -10px;
    font-size: var(--fs-body);
  }

  &__meet {
    margin-bottom: var(--s-3);
  }

  &__error {
    font-size: var(--fs-xs);
    color: var(--terracotta-ink);
  }

  &__note {
    margin-top: 6px;
  }

  &__foot {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--s-4);
    margin-top: var(--s-5);
  }

  &__withdraw {
    margin-right: auto;
  }
}

@media (max-width: 719px) {
  .participation-dialog__save {
    flex: 1;
  }
}
</style>
