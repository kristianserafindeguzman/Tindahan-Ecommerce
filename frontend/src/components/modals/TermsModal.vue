<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)">
    <q-card class="legal-dialog">

      <q-card-section class="legal-header">
        <div class="legal-title">Terms and Conditions</div>
        <q-btn
          icon="close"
          flat
          round
          dense
          class="close-btn"
          @click="$emit('update:modelValue', false)"
        />
      </q-card-section>

      <q-separator />

      <q-card-section class="legal-body scroll">
        <p>
          Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec at dignissim purus.
          Sed lobortis eleifend diam, sit amet mollis mi laoreet et. Suspendisse pretium quam in
          finibus dictum. Pellentesque luctus sagittis sem, volutpat tincidunt augue molestie in.
          Nam rutrum lorem eu erat interdum efficitur. Vestibulum iaculis libero ac arcu malesuada,
          sed egestas ipsum rutrum. Etiam malesuada tortor eget odio eleifend aliquam ullamcorper
          vitae diam. Fusce suscipit id purus vitae tempor.
        </p>
        <p>
          Fusce arcu tortor, pharetra nec imperdiet eget, viverra nec velit. Aenean elementum dui
          viverra leo volutpat malesuada quis hendrerit felis. Quisque vel turpis elementum purus
          cursus congue. Pellentesque vel tempus massa. Mauris tristique blandit malesuada. Cras nulla
          nisl, aliquet quis sodales at, dignissim aliquet risus. In convallis et justo eget lobortis.
        </p>
        <p>
          In sit amet urna eu dolor fringilla efficitur vel et purus. Quisque congue porta leo.
          Nam nisl velit, malesuada vel velit vel, ullamcorper pretium nibh. Praesent ac nisl fermentum,
          congue libero id, tristique diam. Proin ac lacinia ante, a fringilla neque. Pellentesque
          posuere eget mi a facilisis. Pellentesque habitant morbi tristique senectus et netus et
          malesuada fames ac turpis egestas. Integer iaculis suscipit urna a lacinia. Nulla id lectus
          ornare, imperdiet libero non, suscipit quam. Nunc convallis laoreet velit vel vehicula.
          Maecenas luctus pellentesque mauris, nec facilisis nunc rutrum sit amet.
        </p>
        <p>
          Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque bibendum tristique nisi,
          ut imperdiet magna tincidunt id. Donec ut sapien egestas, molestie ex et, rutrum nunc.
          Suspendisse eu leo id quam fermentum fringilla a eu nisi. Etiam commodo orci sed pharetra
          viverra. In hac habitasse platea dictumst. Vestibulum hendrerit nulla augue, at tempor magna
          luctus in. Duis mattis augue enim, ut finibus sem ullamcorper eget. In sapien arcu, mattis
          in ultrices nec, rutrum sed felis. Donec varius mattis purus, non euismod quam pellentesque vel.
          Cras egestas lorem ut hendrerit dignissim. Nunc a pulvinar tellus.
        </p>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right" class="legal-actions">
        <q-btn
          label="Close"
          no-caps
          unelevated
          class="close-button"
          @click="$emit('update:modelValue', false)"
        />
      </q-card-actions>

    </q-card>
  </q-dialog>
</template>

<script setup>
defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
})

defineEmits(['update:modelValue'])
</script>

<style scoped>
.legal-dialog {
  width: 520px;
  max-width: 90vw;

  border-radius: 10px;

  font-family: 'Roboto', Arial, sans-serif;
}

.legal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  padding: 16px 20px;
}

.legal-title {
  font-size: 18px;
  font-weight: 700;

  color: #222222;
}

.close-btn {
  color: #888888;
}

.legal-body {
  max-height: 350px;

  padding: 20px 24px;

  font-size: 13px;
  line-height: 1.7;

  color: #555555;
}

.legal-body p {
  margin: 0 0 16px;
}

.legal-body p:last-child {
  margin-bottom: 0;
}

.legal-actions {
  padding: 12px 20px;
}

.close-button {
  height: 38px;

  padding: 0 24px;

  border-radius: 6px;

  background: #bd2427;
  color: #ffffff;

  font-size: 13px;
  font-weight: 500;
}

.close-button:hover {
  background: #a91e21;
}
</style>
