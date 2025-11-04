<template>
  <AppLayout>
    <template #header>{{ t('weekly_menu') }}</template>

    <div class="p-6">
      <h2 class="text-lg font-semibold mb-4">{{ t('weekly_menu') }}</h2>

      <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full border border-gray-300 table-auto">
          <thead class="bg-gray-100 text-sm font-semibold">
            <tr>
              <th class="border px-2 py-2 w-36 text-center">{{ t('serving_time') }}</th>
              <th v-for="date in weekDays" :key="date" class="border px-2 py-2 text-center">
                {{ formatDateShort(date) }} <span class="text-xs text-gray-500">({{ weekdayJP(date) }})</span>
              </th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="time in servingTimes" :key="time" class="odd:bg-white even:bg-gray-50">
              <td class="border px-2 py-2 text-center font-medium bg-gray-50">{{ formatTime(time) }}</td>

              <td v-for="date in weekDays" :key="date + '-' + time" class="border px-2 py-2 align-top">
                <div v-if="menuData[date] && menuData[date][time]">
                    <div v-for="menu in menuData[date][time]" :key="menu.id" class="mb-1">
                    <Link
                        :href="route('menus.edit', menu.id)"
                        class="text-blue-600 hover:underline block"
                        :title="menu.dish_name"
                    >
                        {{ truncate(menu.dish_name, 20) }}
                    </Link>
                    </div>
                </div>
                <div v-else class="text-gray-400 text-center">-</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import dayjs from 'dayjs'
import 'dayjs/locale/ja'

dayjs.locale('ja')

const { t } = useI18n()

const props = defineProps({
  menuData: Object,        // { '2025-11-03': { '08:00': [ {id:1,name:'...'} ] } }
  servingTimes: Array,     // ['08:00', '12:00', '18:00']
  weekStart: String        // '2025-11-03'
})

// 週の日付リスト（weekStart から7日分）
const weekDays = computed(() => {
  const start = dayjs(props.weekStart)
  const arr = []
  for (let i = 0; i < 7; i++) {
    arr.push(start.add(i, 'day').format('YYYY-MM-DD'))
  }
  return arr
})

// 表示ヘルパー
const formatDateShort = (dateStr) => dayjs(dateStr).format('MM/DD')
const weekdayJP = (dateStr) => {
  // dayjs().format('dd') -> '月' とは異なるため map
  const d = dayjs(dateStr).day() // 0 Sun ... 6 Sat
  return ['日','月','火','水','木','金','土'][d]
}

const formatTime = (timeStr) => {
  return timeStr ? timeStr.slice(0, 5) : ''
}

const truncate = (text = '', max = 20) => {
  const s = String(text)
  return s.length > max ? s.slice(0, max) + '…' : s
}

</script>

<style scoped>
/* 必要なら微調整 */
</style>




