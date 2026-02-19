<script setup>
import { onMounted, ref, watch } from 'vue'
import { Chart, DoughnutController, ArcElement, Tooltip } from 'chart.js'

Chart.register(DoughnutController, ArcElement, Tooltip)

const props = defineProps({
  value: Number,
  total: Number
})

const canvasRef = ref(null)
let chartInstance = null

const renderChart = () => {
  if (chartInstance) chartInstance.destroy()

  chartInstance = new Chart(canvasRef.value, {
    type: 'doughnut',
    data: {
      datasets: [{
        data: [props.total - props.value,props.value, ],
        backgroundColor: [
          '#49A6B8', // 青
          '#F4A340', // オレンジ
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
        const text = `${props.value}/${props.total}件`
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
  <div class="w-64 h-64">
    <canvas ref="canvasRef"></canvas>
  </div>
</template>
