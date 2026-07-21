<template>
  <AppLayout>
    <template #header>{{ t('weekly_menu') }}</template>

    <div class="p-6 space-y-4">

      <!-- ツールバー -->
      <div class="flex flex-wrap items-center justify-between gap-3">
        <Button size="sm" as-child>
          <Link :href="route('menus.create', { redirect_to, tenant_id: form.tenant_id })">
            <Plus class="w-3.5 h-3.5 mr-1" />{{ t('add_menu') }}
          </Link>
        </Button>

        <div class="flex items-center gap-3">
          <div v-if="isSuperAdmin" class="flex items-center gap-2">
            <Label class="text-sm whitespace-nowrap">{{ t('tenant') }}</Label>
            <Select v-model="form.tenant_id" @update:modelValue="changeWeek(0)">
              <SelectTrigger class="h-8 w-40">
                <SelectValue :placeholder="t('select_tenant')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">
                  {{ tenant.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="inline-flex border rounded-md overflow-hidden">
            <Button variant="ghost" size="icon" class="h-8 w-8 rounded-none border-r" @click="changeWeek(-1)">
              <ChevronLeft class="w-4 h-4" />
            </Button>

            <div class="px-4 h-8 flex items-center bg-white border-r text-sm">
              <Calendar class="w-4 h-4 mr-1.5" />
              <span>{{ t('week') }}</span>
            </div>

            <Button variant="ghost" size="icon" class="h-8 w-8 rounded-none" @click="changeWeek(1)">
              <ChevronRight class="w-4 h-4" />
            </Button>
          </div>
        </div>
      </div>

      <!-- 週間表 -->
      <div class="overflow-x-auto border rounded-lg">
        <table class="w-full border-collapse table-auto text-sm">
          <thead class="bg-muted">
            <tr>
              <th class="border-b px-2 py-2.5 w-36 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {{ t('serving_time') }}
              </th>
              <th
                v-for="date in weekDays"
                :key="date"
                class="border-b px-2 py-2.5 text-center text-xs font-semibold uppercase tracking-wide"
                :class="{
                  'text-red-500': dayjs(date).day() === 0,
                  'text-blue-500': dayjs(date).day() === 6,
                  'text-muted-foreground': dayjs(date).day() !== 0 && dayjs(date).day() !== 6,
                }"
              >
                {{ formatDateShort(date) }} <span class="text-xs">({{ weekdayJP(date) }})</span>
              </th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="time in state.servingTimes" :key="time" class="odd:bg-white even:bg-muted/30">
              <td class="border-b px-2 py-2.5 text-center font-medium bg-muted/50">{{ formatTime(time) }}</td>

              <td v-for="date in weekDays" :key="date + '-' + time" class="border-b px-2 py-2.5 align-top">
                <div v-if="menuData[date] && menuData[date][time]">
                  <div v-for="menu in menuData[date][time]" :key="menu.id" class="mb-1">
                    <Link
                      :href="route('menus.edit', { menu: menu.id, redirect_to: route('menus.weekly', { weekStart }) })"
                      class="text-blue-600 hover:underline block"
                      :title="menu.name"
                    >
                      {{ truncate(menu.name, 20) }}
                    </Link>
                  </div>
                </div>
                <div v-else class="text-muted-foreground text-center">-</div>
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
import { Link, router } from '@inertiajs/vue3'
import { ref, reactive, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import dayjs from 'dayjs'
import 'dayjs/locale/ja'
import { Plus, Calendar, ChevronLeft, ChevronRight } from '@lucide/vue'

import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

dayjs.locale('ja')

const { t } = useI18n()

const props = defineProps({
  user: { type: Object, default: null },
  tenant_id: { type: String, default: '' },
  tenants: { type: Array, default: () => [] },
  menuData: { type: Object, default: () => ({}) },
  servingTimes: { type: Array, default: () => [] },
  weekStart: { type: String, default: '' },
  redirect_to: { type: String, default: '' },
})

const isSuperAdmin = computed(() =>
  props.user?.roles?.some(r => r.name.toLowerCase() === 'super admin')
)

const form = reactive({
  tenant_id: props.tenant_id
    ? String(props.tenant_id)
    : (isSuperAdmin.value ? '' : String(props.user?.tenant_id ?? '')),
  redirect_to: props.redirect_to,
})

const getMonday = (dateStr) => {
  const date = dayjs(dateStr)
  const day = date.day()
  return date.add(day === 0 ? -6 : 1 - day, 'day')
}

const state = reactive({
  currentWeekStart: getMonday(props.weekStart).format('YYYY-MM-DD'),
  servingTimes: props.servingTimes,
  menuData: props.menuData,
})

const weekDays = computed(() => {
  const start = dayjs(state.currentWeekStart)
  const arr = []
  for (let i = 0; i < 7; i++) {
    arr.push(start.add(i, 'day').format('YYYY-MM-DD'))
  }
  return arr
})

const menuData = computed(() => state.menuData)

const changeWeek = (diff) => {
  const newWeekStart = getMonday(dayjs(state.currentWeekStart).add(diff * 7, 'day')).format('YYYY-MM-DD')
  state.currentWeekStart = newWeekStart
  fetchWeekData(newWeekStart)
}

const fetchWeekData = (weekStart) => {
  router.get(
    route('menus.weekly'),
    { weekStart, tenant_id: form.tenant_id },
    {
      preserveState: true,
      only: ['menuData', 'servingTimes'],
      onSuccess: (page) => {
        state.menuData = page.props.menuData
        state.servingTimes = page.props.servingTimes
      }
    }
  )
}

const formatDateShort = (dateStr) => dayjs(dateStr).format('MM/DD')
const weekdayJP = (dateStr) => ['日', '月', '火', '水', '木', '金', '土'][dayjs(dateStr).day()]
const formatTime = (timeStr) => timeStr ? timeStr.slice(0, 5) : ''
const truncate = (text = '', max = 20) => text.length > max ? text.slice(0, max) + '…' : text
</script>