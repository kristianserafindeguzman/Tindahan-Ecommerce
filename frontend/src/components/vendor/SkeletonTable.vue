<template>
  <!-- Placeholder rows shaped like the real table or phone list, shown while the first answer loads. -->
  <div class="sk" aria-hidden="true">
    <table v-if="!list" class="vp-table sk-table">
      <thead>
        <tr>
          <th v-for="(col, i) in columns" :key="i" :style="col.width ? { width: col.width } : null">
            <q-skeleton v-if="!['icon', 'actions'].includes(col.type)" type="text" width="56px" height="12px" class="sk-head" :class="{ 'sk-right': col.align === 'right' }" />
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row">
          <td v-for="(col, i) in columns" :key="i">
            <div class="sk-cell" :class="{ 'sk-cell--right': col.align === 'right' }">
              <template v-if="col.type === 'avatar' || col.type === 'thumb'">
                <q-skeleton v-if="col.type === 'avatar'" type="QAvatar" size="32px" />
                <q-skeleton v-else type="rect" width="44px" height="44px" class="sk-thumb" />
                <div class="sk-lines">
                  <q-skeleton type="text" :width="varied(row, i, 50, 85)" />
                  <q-skeleton v-if="col.lines === 2" type="text" height="12px" :width="varied(row, i + 3, 35, 65)" />
                </div>
              </template>
              <q-skeleton v-else-if="col.type === 'pill'" type="rect" height="22px" :width="`${col.size || 72}px`" class="sk-pill" />
              <q-skeleton v-else-if="col.type === 'icon'" type="circle" size="26px" />
              <template v-else-if="col.type === 'actions'">
                <q-skeleton type="circle" size="28px" />
                <q-skeleton type="circle" size="28px" />
              </template>
              <q-skeleton v-else type="text" :width="varied(row, i, 45, 80)" />
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-else class="sk-list">
      <div v-for="row in rows" :key="row" class="sk-item">
        <template v-if="lead">
          <q-skeleton v-if="thumb" type="rect" width="52px" height="52px" class="sk-thumb" />
          <q-skeleton v-else type="QAvatar" size="40px" />
        </template>
        <div class="sk-lines">
          <q-skeleton type="text" :width="varied(row, 1, 45, 75)" />
          <q-skeleton type="text" height="12px" :width="varied(row, 2, 30, 55)" />
        </div>
        <div class="sk-side">
          <q-skeleton type="text" width="56px" />
          <q-skeleton v-if="pill" type="rect" width="64px" height="20px" class="sk-pill" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  // Each column: { width, type: 'text' | 'pill' | 'avatar' | 'thumb' | 'icon' | 'actions', align, size, lines }.
  columns: { type: Array, default: () => [] },
  rows: { type: Number, default: 6 },
  list: { type: Boolean, default: false },
  thumb: { type: Boolean, default: false },
  // Phone rows only: the avatar or photo on the left, and the status pill under the amount.
  lead: { type: Boolean, default: true },
  pill: { type: Boolean, default: true }
})

// Slightly different widths from row to row, so the placeholder reads as real rows rather than a grid of equal bars.
const varied = (row, col, min, max) => `${min + ((row * 37 + col * 23) % (max - min + 1))}%`
</script>

<style scoped>
.sk-table td {
  vertical-align: middle;
}

.sk-head {
  border-radius: 4px;
}

.sk-right {
  margin-left: auto;
}

.sk-cell {
  display: flex;
  align-items: center;

  gap: 10px;
  min-height: 28px;
}

.sk-cell--right {
  justify-content: flex-end;
}

.sk-lines {
  display: flex;
  flex-direction: column;
  flex: 1;

  gap: 4px;
  min-width: 0;
}

.sk-thumb {
  flex-shrink: 0;

  border-radius: var(--r-control);
}

.sk-pill {
  border-radius: var(--r-pill);
}

.sk-list {
  display: flex;
  flex-direction: column;

  padding: 4px 16px;
}

.sk-item {
  display: flex;
  align-items: center;

  gap: 12px;
  padding: 12px 0;

  border-bottom: 1px solid var(--c-hairline);
}

.sk-item:last-child {
  border-bottom: none;
}

.sk-side {
  display: flex;
  flex-direction: column;
  align-items: flex-end;

  gap: 6px;
}
</style>
