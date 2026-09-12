<template>
  <q-dialog ref="dialogRef" persistent @hide="onDialogHide">
    <q-card class="logout-dialog">
      <q-card-section class="logout-content">
        <div class="logout-title">Log out?</div>

        <p class="logout-message">Are you sure you want to log out of your account?</p>
      </q-card-section>

      <q-card-actions class="logout-actions">
        <q-btn outline no-caps label="Cancel" class="logout-cancel" @click="onDialogCancel" />
        <q-btn unelevated no-caps label="Log out" class="logout-btn" @click="onDialogOK" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { useDialogPluginComponent } from 'quasar'

// Opened through $q.dialog({ component }), so OK and Cancel resolve the caller's onOk and onCancel.
defineEmits([...useDialogPluginComponent.emits])

const { dialogRef, onDialogHide, onDialogOK, onDialogCancel } = useDialogPluginComponent()
</script>

<style scoped>
/* Same layout as the status dialogs on the login page. */
.logout-dialog {
  width: 400px;
  max-width: 90vw;

  border-radius: var(--r-xl);

  font-family: 'Roboto', Arial, sans-serif;
}

.logout-content {
  text-align: center;

  padding: 28px 28px 0;
}

.logout-title {
  font-size: 19px;
  font-weight: 700;

  color: var(--c-text);

  margin-bottom: 10px;
}

.logout-message {
  font-size: var(--fs-sm);
  line-height: 1.6;

  color: var(--c-text-3);

  margin: 0;
}

/* Cancel and Log out share the row equally, the same pairing as the buttons on the vendor rejected page. */
.logout-actions {
  flex-wrap: nowrap;

  gap: 12px;

  padding: 20px 28px 28px;
}

/* Quasar spaces neighbouring card buttons with its own margin, which would double up with the gap. */
.logout-actions .q-btn {
  flex: 1;

  height: 48px;
  margin: 0;

  border-radius: var(--r-sm);

  font-family: 'Roboto', Arial, sans-serif;
  font-size: var(--fs-sm);
  font-weight: 600;
}

.logout-btn {
  background: var(--c-brand);
  color: #ffffff;

  box-shadow: var(--sh-brand);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.logout-btn:hover {
  background: var(--c-brand-hover);

  box-shadow: var(--sh-brand-hover);

  transform: translateY(-1px);
}

.logout-btn:active {
  background: var(--c-brand-active);

  transform: translateY(0);
}

.logout-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

/* The red outline of the profile's Edit pill, with Quasar drawing the border in the text colour. */
.logout-cancel {
  color: var(--c-brand);
}

.logout-cancel:hover {
  background: var(--c-brand-tint);
}

/* The vendor and admin dark theme darkens every dialog, so the text lightens with it. */
:global(body.body--dark) .logout-dialog {
  background: #0f172a;
}

:global(body.body--dark) .logout-title {
  color: #f8fafc;
}

:global(body.body--dark) .logout-message,
:global(body.body--dark) .logout-cancel {
  color: #cbd5e1;
}
</style>
