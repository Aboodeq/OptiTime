<template>
  <div
    class="management-reports-chart-wrap"
    :style="wrapStyle"
    role="img"
    :aria-label="ariaLabel || undefined"
  >
    <canvas ref="canvasEl" />
  </div>
</template>

<script setup>
import {
  ArcElement,
  BarController,
  BarElement,
  CategoryScale,
  Chart,
  DoughnutController,
  Legend,
  LinearScale,
  Tooltip,
} from 'chart.js'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

Chart.register(
  ArcElement,
  BarController,
  BarElement,
  CategoryScale,
  DoughnutController,
  Legend,
  LinearScale,
  Tooltip,
)

const props = defineProps({
  /** 'bar' | 'horizontalBar' | 'doughnut' */
  variant: {
    type: String,
    default: 'bar',
    validator: (v) => ['bar', 'horizontalBar', 'doughnut'].includes(v),
  },
  labels: {
    type: Array,
    default: () => [],
  },
  values: {
    type: Array,
    default: () => [],
  },
  datasetLabel: {
    type: String,
    default: '',
  },
  /** Doughnut segment colors (CSS colors) */
  segmentColors: {
    type: Array,
    default: () => [],
  },
  /** Single bar fill or per-bar colors for bar charts */
  barColor: {
    type: String,
    default: '',
  },
  barColors: {
    type: Array,
    default: () => [],
  },
  rtl: {
    type: Boolean,
    default: false,
  },
  ariaLabel: {
    type: String,
    default: '',
  },
  heightPx: {
    type: Number,
    default: 260,
  },
})

const canvasEl = ref(null)
let chartInstance = null

const wrapStyle = computed(() => ({
  height: `${props.heightPx}px`,
  position: 'relative',
}))

function getBarBackgrounds(count) {
  if (props.barColors?.length) {
    return props.labels.map((_, i) => props.barColors[i] ?? props.barColors[0] ?? fallbackBarColor(0))
  }
  const base = props.barColor || 'rgba(13, 110, 253, 0.75)'
  return Array.from({ length: count }, () => base)
}

function fallbackBarColor(i) {
  const hues = ['rgba(13, 110, 253, 0.8)', 'rgba(13, 202, 240, 0.85)', 'rgba(25, 135, 84, 0.75)']
  return hues[i % hues.length]
}

function defaultDoughnutColors() {
  return ['rgba(13, 110, 253, 0.85)', 'rgba(108, 117, 125, 0.45)']
}

function buildConfig() {
  const labels = props.labels.map((l) => String(l ?? ''))
  const raw = props.values.map((v) => (typeof v === 'number' ? v : Number(v)) || 0)

  if (props.variant === 'doughnut') {
    const bg =
      props.segmentColors?.length >= raw.length
        ? props.segmentColors
        : defaultDoughnutColors().slice(0, Math.max(2, raw.length))
    return {
      type: 'doughnut',
      data: {
        labels,
        datasets: [
          {
            data: raw,
            backgroundColor: bg,
            borderWidth: 1,
            borderColor: 'rgba(255,255,255,0.85)',
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        rtl: props.rtl,
        plugins: {
          legend: {
            position: 'bottom',
            rtl: props.rtl,
            labels: { boxWidth: 12 },
          },
        },
      },
    }
  }

  const horizontal = props.variant === 'horizontalBar'
  const backgrounds = getBarBackgrounds(raw.length)

  return {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: props.datasetLabel || '',
          data: raw,
          backgroundColor: backgrounds,
          borderRadius: 4,
          borderSkipped: false,
        },
      ],
    },
    options: {
      indexAxis: horizontal ? 'y' : 'x',
      responsive: true,
      maintainAspectRatio: false,
      rtl: props.rtl,
      plugins: {
        legend: {
          display: Boolean(props.datasetLabel),
          rtl: props.rtl,
        },
      },
      scales: horizontal
        ? {
            x: {
              beginAtZero: true,
              grid: { color: 'rgba(0, 0, 0, 0.06)' },
            },
            y: {
              grid: { display: false },
              ticks: { autoSkip: true },
            },
          }
        : {
            x: {
              grid: { display: false },
              ticks: {
                maxRotation: 45,
                minRotation: 0,
                autoSkip: true,
              },
            },
            y: {
              beginAtZero: true,
              grid: { color: 'rgba(0, 0, 0, 0.06)' },
            },
          },
    },
  }
}

function destroyChart() {
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
}

function renderChart() {
  destroyChart()
  const canvas = canvasEl.value
  if (!canvas) return
  const labels = props.labels ?? []
  const values = props.values ?? []
  if (!labels.length || !values.length) return

  const ctx = canvas.getContext('2d')
  if (!ctx) return

  chartInstance = new Chart(ctx, buildConfig())
}

watch(
  () => [props.variant, props.labels, props.values, props.rtl, props.datasetLabel, props.segmentColors, props.barColor, props.barColors],
  () => {
    nextTick(() => renderChart())
  },
  { deep: true },
)

onMounted(() => {
  nextTick(() => renderChart())
})

onBeforeUnmount(() => {
  destroyChart()
})
</script>

<style scoped>
.management-reports-chart-wrap {
  width: 100%;
}
</style>
