<script setup lang="ts">
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'
import type { StatisticsResponse } from '@/types/api'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend)

const props = defineProps<{ statistics: StatisticsResponse | null }>()

const chartData = computed(() => {
  const series = props.statistics?.series ?? []
  return {
    labels: series.map((s) => s.day),
    datasets: [
      {
        label: 'Avg buy',
        data: series.map((s) => s.avg_buy),
        borderColor: '#2563eb',
        backgroundColor: 'rgba(37, 99, 235, 0.1)',
        tension: 0.2,
      },
      {
        label: 'Avg sell',
        data: series.map((s) => s.avg_sell),
        borderColor: '#dc2626',
        backgroundColor: 'rgba(220, 38, 38, 0.1)',
        tension: 0.2,
      },
    ],
  }
})

const options = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top' as const,
      labels: { color: '#1e293b', font: { size: 13 } },
    },
    title: {
      display: true,
      text: 'Daily average rates',
      color: '#0f172a',
      font: { size: 14, weight: 'bold' as const },
    },
  },
  scales: {
    x: {
      ticks: { color: '#475569' },
      grid: { color: '#e2e8f0' },
    },
    y: {
      ticks: { color: '#475569' },
      grid: { color: '#e2e8f0' },
    },
  },
}
</script>

<template>
  <div v-if="!statistics || statistics.series.length === 0" class="muted empty">
    No data for this period.
  </div>
  <div v-else class="chart-box">
    <Line :data="chartData" :options="options" />
  </div>
</template>

<style scoped>
.chart-box {
  height: 320px;
}
.empty {
  padding: 2rem;
  text-align: center;
}
</style>
