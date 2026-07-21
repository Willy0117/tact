<script setup>
import { onMounted, ref, watch } from 'vue'
import { Chart, DoughnutController, ArcElement, Tooltip } from 'chart.js'

Chart.register(DoughnutController, ArcElement, Tooltip)

const props = defineProps({
  value: Number,
  total: Number,
  size: {
    type: Number,
    default: 256,
  },
})

const canvasRef = ref(null)
let chartInstance = null

const renderChart = () => {
  if (chartInstance) chartInstance.destroy()

  chartInstance = new Chart(canvasRef.value, {
    type: 'doughnut',
    data: {
      datasets: [{
        data: [props.value, props.total - props.value],
        backgroundColor: [
          '#22c55e',
          '#ff8080',
        ],
        borderWidth: 0
      }]
    },
    options: {
      cutout: '70%',
      plugins: {
        tooltip: { enabled: false },
        legend: { display: false }
      }
    },
    plugins: [{
      id: 'centerText',
      beforeDraw(chart) {
        const { width, height, ctx } = chart
        ctx.restore()
        ctx.font = 'bold 22px sans-serif'
        ctx.textBaseline = 'middle'
        ctx.fillStyle = '#333'
        const text = `${props.total}件`
        const textX = Math.round((width - ctx.measureText(text).width) / 2)
        const textY = height / 2
        ctx.fillText(text, textX, textY)
        ctx.save()
      }
    }]
  })
}

onMounted(renderChart)
watch(() => [props.value, props.total], renderChart)
</script>

<template>
  <div :style="{ width: size + 'px', height: size + 'px' }">
    <canvas ref="canvasRef"></canvas>
  </div>
</template>