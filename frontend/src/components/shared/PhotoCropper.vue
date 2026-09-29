<template>
  <div class="photo-cropper">
    <div ref="stageEl" class="cropper-stage" :style="{ height: `${stageHeight}px` }">
      <canvas
        ref="canvasEl"
        class="cropper-canvas"
        role="img"
        :aria-label="round ? 'Photo inside a round frame' : 'Photo inside a crop frame'"
        @pointerdown="onPointerDown"
        @pointermove="onPointerMove"
        @pointerup="onPointerUp"
        @pointercancel="onPointerUp"
        @wheel.prevent="onWheel"
      ></canvas>
    </div>

    <div class="cropper-zoom">
      <q-btn flat round dense icon="o_zoom_out" class="cropper-zoom-btn" aria-label="Zoom out" :disable="zoom <= 1" @click="stepZoom(-0.25)" />
      <q-slider
        :model-value="zoom"
        :min="1"
        :max="MAX_ZOOM"
        :step="0.01"
        color="primary"
        class="cropper-slider"
        aria-label="Zoom"
        @update:model-value="value => zoomAt(value, frameCenter())"
      />
      <q-btn flat round dense icon="o_zoom_in" class="cropper-zoom-btn" aria-label="Zoom in" :disable="zoom >= MAX_ZOOM" @click="stepZoom(0.25)" />
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  // Object URL or data URL of the photo to crop.
  src: { type: String, default: '' },
  // Width divided by height of the crop frame.
  aspect: { type: Number, default: 1 },
  // Draws the frame as a circle for round avatars; the saved photo stays square.
  round: { type: Boolean, default: false },
  // Longest side of the saved photo in pixels, never upscaled past the source.
  outputWidth: { type: Number, default: 1024 }
})

const emit = defineEmits(['ready'])

const MAX_ZOOM = 4
// Space kept around the frame so the dimmed edges of the photo stay visible.
const PAD = 24
// Room the dialog's header, zoom row, buttons and margins need, so the stage never makes it scroll.
const DIALOG_CHROME = 360

const stageEl = ref(null)
const canvasEl = ref(null)
const stageHeight = ref(240)
// Multiple of the smallest scale at which the photo still covers the frame.
const zoom = ref(1)

let img = null
let width = 0
let height = 0
let frame = { x: 0, y: 0, w: 0, h: 0 }
let baseScale = 1
let offset = { x: 0, y: 0 }
const pointers = new Map()
let lastPan = null
let pinch = null
let observer = null
// Set when a new photo loads, so the next successful layout centres it and reports the cropper ready.
let recentre = false
let drawRequest = 0

const scale = () => baseScale * zoom.value
const frameCenter = () => ({ x: frame.x + frame.w / 2, y: frame.y + frame.h / 2 })

// Sizes the stage to the frame's shape plus padding, capped by the screen height, and centres the frame in it.
function layout() {
  width = stageEl.value?.clientWidth || 0
  const screen = window.visualViewport?.height ?? window.innerHeight
  height = Math.min((width - PAD * 2) / props.aspect + PAD * 2, Math.max(180, screen - DIALOG_CHROME))
  stageHeight.value = height

  let fw = width - PAD * 2
  let fh = fw / props.aspect
  if (fh > height - PAD * 2) {
    fh = height - PAD * 2
    fw = fh * props.aspect
  }
  frame = { x: (width - fw) / 2, y: (height - fh) / 2, w: fw, h: fh }
}

// Keeps the photo covering the whole frame, so the saved crop never contains empty space.
function clamp() {
  const s = scale()
  offset.x = Math.min(frame.x, Math.max(frame.x + frame.w - img.width * s, offset.x))
  offset.y = Math.min(frame.y, Math.max(frame.y + frame.h - img.height * s, offset.y))
}

// Lays out the stage for the current width, centring a newly loaded photo or else keeping the same point under the frame's centre.
function fit() {
  if (!img || !stageEl.value?.clientWidth) return
  const before = frameCenter()
  const point = recentre ? null : { x: (before.x - offset.x) / scale(), y: (before.y - offset.y) / scale() }
  layout()
  baseScale = Math.max(frame.w / img.width, frame.h / img.height)
  const c = frameCenter()
  const p = point || { x: img.width / 2, y: img.height / 2 }
  offset = { x: c.x - p.x * scale(), y: c.y - p.y * scale() }
  clamp()
  draw()
  if (recentre) {
    recentre = false
    emit('ready')
  }
}

function framePath(ctx) {
  if (props.round) {
    const c = frameCenter()
    ctx.moveTo(c.x + frame.w / 2, c.y)
    ctx.arc(c.x, c.y, frame.w / 2, 0, Math.PI * 2)
  } else {
    ctx.rect(frame.x, frame.y, frame.w, frame.h)
  }
}

// Draws the photo, dims everything outside the frame, and outlines the frame.
function draw() {
  const canvas = canvasEl.value
  if (!canvas || !img) return
  const dpr = window.devicePixelRatio || 1
  const bw = Math.round(width * dpr)
  const bh = Math.round(height * dpr)
  if (canvas.width !== bw || canvas.height !== bh) {
    canvas.width = bw
    canvas.height = bh
  }
  const ctx = canvas.getContext('2d')
  ctx.setTransform(dpr, 0, 0, dpr, 0, 0)
  ctx.clearRect(0, 0, width, height)

  const s = scale()
  ctx.drawImage(img, offset.x, offset.y, img.width * s, img.height * s)

  ctx.fillStyle = 'rgba(17, 17, 17, 0.6)'
  ctx.beginPath()
  ctx.rect(0, 0, width, height)
  framePath(ctx)
  ctx.fill('evenodd')

  ctx.strokeStyle = '#ffffff'
  ctx.lineWidth = 2
  ctx.beginPath()
  framePath(ctx)
  ctx.stroke()
}

// Batches redraws to one per screen refresh, so dragging a large camera photo stays smooth on slower phones.
function requestDraw() {
  if (drawRequest) return
  drawRequest = requestAnimationFrame(() => {
    drawRequest = 0
    draw()
  })
}

// Zooms to the given level while keeping the photo point under the given stage point still.
function zoomAt(value, at) {
  if (!img) return
  const next = Math.min(MAX_ZOOM, Math.max(1, value))
  const s = scale()
  const px = (at.x - offset.x) / s
  const py = (at.y - offset.y) / s
  zoom.value = next
  offset = { x: at.x - px * scale(), y: at.y - py * scale() }
  clamp()
  requestDraw()
}

const stepZoom = (delta) => zoomAt(zoom.value + delta, frameCenter())

const stagePoint = (e) => {
  const r = canvasEl.value.getBoundingClientRect()
  return { x: e.clientX - r.left, y: e.clientY - r.top }
}

function onWheel(e) {
  zoomAt(zoom.value * (e.deltaY < 0 ? 1.1 : 1 / 1.1), stagePoint(e))
}

function onPointerDown(e) {
  if (!img) return
  canvasEl.value.setPointerCapture(e.pointerId)
  pointers.set(e.pointerId, stagePoint(e))
  if (pointers.size === 1) {
    lastPan = stagePoint(e)
  } else if (pointers.size === 2) {
    const [a, b] = [...pointers.values()]
    pinch = { dist: Math.hypot(a.x - b.x, a.y - b.y) || 1, zoom: zoom.value }
    lastPan = null
  }
}

function onPointerMove(e) {
  if (!pointers.has(e.pointerId)) return
  pointers.set(e.pointerId, stagePoint(e))

  if (pointers.size === 1 && lastPan) {
    const p = stagePoint(e)
    offset.x += p.x - lastPan.x
    offset.y += p.y - lastPan.y
    lastPan = p
    clamp()
    requestDraw()
  } else if (pointers.size === 2 && pinch) {
    const [a, b] = [...pointers.values()]
    const dist = Math.hypot(a.x - b.x, a.y - b.y)
    zoomAt(pinch.zoom * (dist / pinch.dist), { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 })
  }
}

function onPointerUp(e) {
  pointers.delete(e.pointerId)
  pinch = null
  // Lifting one finger of a pinch carries on as a pan from where the other finger is.
  lastPan = pointers.size === 1 ? [...pointers.values()][0] : null
}

// Cuts the framed area out of the original photo, at up to outputWidth pixels wide.
function toBlob(type = 'image/jpeg', quality = 0.9) {
  if (!img || !frame.w || !frame.h) return Promise.resolve(null)
  const s = scale()
  const sx = (frame.x - offset.x) / s
  const sy = (frame.y - offset.y) / s
  const sw = frame.w / s
  const sh = frame.h / s
  const outW = Math.max(1, Math.round(Math.min(props.outputWidth, sw)))
  const outH = Math.max(1, Math.round(outW / props.aspect))
  const out = document.createElement('canvas')
  out.width = outW
  out.height = outH
  out.getContext('2d').drawImage(img, sx, sy, sw, sh, 0, 0, outW, outH)
  return new Promise(resolve => out.toBlob(resolve, type, quality))
}

defineExpose({ toBlob })

watch(() => props.src, (src) => {
  img = null
  zoom.value = 1
  if (!src) return
  const next = new Image()
  next.onload = () => {
    if (props.src !== src) return
    img = next
    recentre = true
    fit()
  }
  next.src = src
}, { immediate: true })

const onResize = () => fit()

onMounted(() => {
  observer = new ResizeObserver(onResize)
  observer.observe(stageEl.value)
  window.addEventListener('resize', onResize)
})

onBeforeUnmount(() => {
  observer?.disconnect()
  window.removeEventListener('resize', onResize)
  cancelAnimationFrame(drawRequest)
})
</script>

<style scoped>
.photo-cropper {
  width: 100%;
}

.cropper-stage {
  position: relative;

  width: 100%;

  overflow: hidden;

  border-radius: var(--r-md);

  background: #1f2125;
}

/* touch-action: none stops a drag on the photo from scrolling the page instead. */
.cropper-canvas {
  display: block;

  width: 100%;
  height: 100%;

  cursor: grab;
  touch-action: none;
}

.cropper-canvas:active {
  cursor: grabbing;
}

.cropper-zoom {
  display: flex;
  align-items: center;

  gap: 8px;

  margin-top: 12px;
}

.cropper-slider {
  flex: 1;
}

.cropper-zoom-btn {
  color: var(--c-text-3);
}

/* After a mouse click Quasar's grey focus tint would make the button look stuck, so it only shows for keyboard focus or hover. */
.cropper-zoom-btn:focus:not(:focus-visible):not(:hover) :deep(.q-focus-helper) {
  opacity: 0;
}

/* Touch screens keep a tapped button in its hover state, which would leave the same grey tint on after zooming. */
@media (hover: none) {
  .cropper-zoom-btn :deep(.q-focus-helper) {
    opacity: 0 !important;
  }
}
</style>
