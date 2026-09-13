<template>
  <!-- SET ACTIVE / DELETE — the Log out dialog's layout. -->
  <q-dialog v-model="confirmOpen" persistent>
    <q-card class="vp-dialog adm-confirm">
      <div class="adm-confirm-body">
        <span
          class="vp-dialog-icon adm-confirm-icon"
          :class="`adm-confirm-icon--${confirmCopy.tone}`"
          ><q-icon :name="confirmCopy.icon" size="24px"
        /></span>
        <div class="adm-confirm-title">{{ confirmCopy.title }}</div>
        <p class="adm-confirm-text"
          ><strong>{{ target?.name }}</strong> {{ confirmCopy.text }}</p
        >
      </div>
      <q-separator class="adm-sep" />
      <div class="adm-confirm-actions">
        <q-btn
          v-close-popup
          outline
          no-caps
          color="primary"
          label="Cancel"
          class="vp-dialog-btn"
          :disable="busy"
        />
        <q-btn
          unelevated
          no-caps
          color="primary"
          :label="confirmCopy.button"
          class="vp-dialog-btn"
          :loading="busy"
          @click="submit"
        />
      </div>
    </q-card>
  </q-dialog>

  <!-- SET INACTIVE / SUSPEND — a reason the person sees when they next sign in; a suspension needs one. -->
  <q-dialog v-model="reasonOpen" persistent>
    <q-card class="vp-dialog adm-form-dialog">
      <div class="vp-dialog-head">
        <span
          class="vp-dialog-icon"
          :class="{ 'vp-dialog-icon--danger': action === 'suspended' }"
          ><q-icon :name="reasonCopy.icon" size="22px"
        /></span>
        <div>
          <div class="vp-dialog-title">{{ reasonCopy.title }}</div>
          <div class="vp-dialog-text"
            ><strong>{{ target?.name }}</strong> {{ reasonCopy.text }}</div
          >
        </div>
      </div>
      <q-separator class="adm-sep adm-sep--head" />
      <div class="vp-dialog-body">
        <div>
          <label class="vp-field-label" :for="fieldId"
            >Reason<span v-if="action === 'inactive'" class="vp-field-optional">
              (optional)</span
            ></label
          >
          <!-- The server keeps up to 1,000 characters. -->
          <q-input
            v-model="reason"
            :for="fieldId"
            type="textarea"
            outlined
            autogrow
            autofocus
            counter
            maxlength="1000"
            :placeholder="reasonCopy.placeholder"
            class="vp-input adm-reason"
          />
        </div>
      </div>
      <div class="vp-dialog-actions">
        <q-btn
          v-close-popup
          outline
          no-caps
          color="primary"
          label="Cancel"
          class="vp-dialog-btn"
          :disable="busy"
        />
        <q-btn
          unelevated
          no-caps
          color="primary"
          :label="reasonCopy.button"
          class="vp-dialog-btn"
          :disable="action === 'suspended' && !reason.trim()"
          :loading="busy"
          @click="submit"
        />
      </div>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, computed, useId } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'
import '@/css/admin-pages.scss'

const props = defineProps({
  // The list the account belongs to, 'vendors' or 'consumers', which is also the start of the address the change goes to.
  kind: { type: String, required: true },
  noun: { type: String, default: 'account' },
  // What deleting means for this kind of account, after the person's name.
  deleteText: {
    type: String,
    default:
      'will be signed out and moved to Deleted. Their order history is kept.'
  }
})

// Tells the page what changed, so it can update the row without reloading.
const emit = defineEmits(['changed', 'deleted'])
const $q = useQuasar()
const fieldId = useId()

const confirmOpen = ref(false)
const reasonOpen = ref(false)
const target = ref(null)
const action = ref('active')
const reason = ref('')
const busy = ref(false)

const confirmCopy = computed(() =>
  action.value === 'delete'
    ? {
        tone: 'danger',
        icon: 'o_delete',
        title: `Delete this ${props.noun}?`,
        text: props.deleteText,
        button: 'Delete'
      }
    : {
        tone: 'success',
        icon: 'o_check_circle',
        title: `Set this ${props.noun} active?`,
        text: 'will be able to sign in and use Tindahan again.',
        button: 'Set Active'
      }
)

const reasonCopy = computed(() =>
  action.value === 'suspended'
    ? {
        icon: 'o_block',
        title: `Suspend this ${props.noun}?`,
        text: "can't sign in until the account is set active again, and will see this reason.",
        placeholder: "e.g. Selling items that aren't allowed on Tindahan.",
        button: 'Suspend'
      }
    : {
        icon: 'o_pause_circle',
        title: `Set this ${props.noun} inactive?`,
        text: "can't sign in while the account is inactive.",
        placeholder: 'e.g. Requested by the account owner.',
        button: 'Set Inactive'
      }
)

// Set active and delete ask to confirm; set inactive and suspend ask for a reason.
const open = (person, next) => {
  target.value = person
  action.value = next
  reason.value = ''
  if (next === 'active' || next === 'delete') confirmOpen.value = true
  else reasonOpen.value = true
}

const submit = async () => {
  const person = target.value
  const text = reason.value.trim()
  if (!person || (action.value === 'suspended' && !text)) return
  busy.value = true
  try {
    if (action.value === 'delete') {
      await api.delete(`/admin/${props.kind}/${person.id}`)
      confirmOpen.value = false
      $q.notify({ type: 'positive', message: `${person.name} was deleted.` })
      emit('deleted', { userId: person.id })
    } else {
      await api.patch(`/admin/${props.kind}/${person.id}/status`, {
        account_status: action.value,
        ...(text ? { suspension_message: text } : {})
      })
      confirmOpen.value = false
      reasonOpen.value = false
      $q.notify({
        type: 'positive',
        message: `${person.name} is now ${action.value}.`
      })
      emit('changed', { userId: person.id, status: action.value })
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message:
        error.response?.data?.message ||
        'Couldn’t update this account. Please try again.'
    })
  } finally {
    busy.value = false
  }
}

defineExpose({ open })
</script>
