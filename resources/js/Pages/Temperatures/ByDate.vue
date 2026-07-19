<template>
  <AppLayout>
    <template #header>{{ pageTitle }}</template>

    <div v-if="success" class="mb-4 rounded bg-green-100 px-4 py-2 text-green-800">
      {{ success }}
    </div>

    <div class="p-6 space-y-4">

      <!-- ツールバー -->
      <div class="flex items-center justify-between gap-3">
        <!-- 日付ナビゲーション -->
        <div class="flex items-center gap-1 w-fit">
            <Button variant="ghost" size="icon" @click="changeDate(-1)" :title="t('prev_day')">
                <ChevronLeft class="w-5 h-5" />
            </Button>

            <button
                @click="openDatePicker"
                class="flex items-center justify-center gap-1 px-4 py-2 border rounded hover:bg-gray-50 font-medium min-w-[180px]"
            >
                {{ dayjs(form.date).format('YYYY/MM/DD (ddd)') }}
            </button>
            <input
                ref="dateInputRef"
                type="date"
                v-model="form.date"
                :max="today"
                @change="onDateInput"
                class="absolute opacity-0 pointer-events-none w-0 h-0"
            />

            <Button variant="ghost" size="icon" @click="changeDate(1)" :disabled="isToday" :title="t('next_day')">
                <ChevronRight class="w-5 h-5" />
            </Button>
        </div>

        <div class="flex items-center gap-2">
          <Select v-model="form.per_page" @update:modelValue="submitSearch">
            <SelectTrigger class="w-20 h-9">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="n in [10,20,30,50]" :key="n" :value="n">{{ n }}</SelectItem>
            </SelectContent>
          </Select>

          <Button variant="outline" size="sm" @click="openDrawer = !openDrawer">
            <Filter class="w-3.5 h-3.5 mr-1" />{{ t('search') }}
          </Button>
          <Button variant="outline" size="sm" @click="exportPdf">
            <Printer class="w-3.5 h-3.5 mr-1" />PDF
          </Button>
        </div>
      </div>

      <!-- 検索フォーム（テーブル上部に常時表示、開閉ボタンで隠せる） -->
      <div v-if="openDrawer" class="border rounded-lg p-4 space-y-4 bg-muted/20">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
          <div class="space-y-1.5">
            <Label>{{ t('dish_name') }}</Label>
            <Autocomplete
              v-model="form.menu_id"
              v-model:name="formLabels.menu_id"
              api-url="/menus/autocomplete"
              :placeholder="t('select.menu')"
            />
          </div>

          <div class="space-y-1.5">
            <Label>{{ t('process') }}</Label>
            <Autocomplete
              v-model="form.process_id"
              v-model:name="formLabels.process_id"
              api-url="/processes/autocomplete"
              :placeholder="t('select.process')"
            />
          </div>

          <div class="space-y-1.5">
            <Label>{{ t('sensor') }}</Label>
            <Autocomplete
              v-model="form.sensor_id"
              v-model:name="formLabels.sensor_id"
              api-url="/sensors/autocomplete"
              :placeholder="t('select.sensor')"
            />
          </div>

          <div class="space-y-1.5">
            <Label>{{ t('device') }}</Label>
            <Autocomplete
              v-model="form.device_id"
              v-model:name="formLabels.device_id"
              api-url="/devices/autocomplete"
              :placeholder="t('select.device')"
            />
          </div>

          <div class="space-y-1.5">
            <Label>{{ t('operator') }}</Label>
            <Autocomplete
              v-model="form.operator_id"
              v-model:name="formLabels.operator_id"
              api-url="/operators/autocomplete"
              :placeholder="t('select.operator')"
            />
          </div>

          <div v-if="isSuperAdmin" class="space-y-1.5">
            <Label>{{ t('tenant') }}</Label>
            <Select v-model="form.tenant_id">
              <SelectTrigger><SelectValue :placeholder="t('select_tenant')" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">{{ t('select_tenant') }}</SelectItem>
                <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">
                  {{ tenant.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <div class="flex justify-end gap-2">
          <Button size="sm" @click="submitSearch">
            <Search class="w-3.5 h-3.5 mr-1" />{{ t('search') }}
          </Button>
          <Button variant="outline" size="sm" @click="resetSearch">{{ t('reset') }}</Button>
        </div>
      </div>

      <!-- 検索中バッジ -->
      <div v-if="hasActiveFilters" class="flex items-center gap-2 flex-wrap">
        <span class="text-xs text-muted-foreground">検索条件:</span>
        <Badge v-if="form.menu_id" variant="secondary" class="gap-1">
          {{ t('dish_name') }}: {{ formLabels.menu_id }}
          <button @click="clearFilter('menu_id')"><X class="w-3 h-3" /></button>
        </Badge>
        <Badge v-if="form.process_id" variant="secondary" class="gap-1">
          {{ t('process') }}: {{ formLabels.process_id }}
          <button @click="clearFilter('process_id')"><X class="w-3 h-3" /></button>
        </Badge>
        <Badge v-if="form.sensor_id" variant="secondary" class="gap-1">
          {{ t('sensor') }}: {{ formLabels.sensor_id }}
          <button @click="clearFilter('sensor_id')"><X class="w-3 h-3" /></button>
        </Badge>
        <Badge v-if="form.device_id" variant="secondary" class="gap-1">
          {{ t('device') }}: {{ formLabels.device_id }}
          <button @click="clearFilter('device_id')"><X class="w-3 h-3" /></button>
        </Badge>
        <Badge v-if="form.operator_id" variant="secondary" class="gap-1">
          {{ t('operator') }}: {{ formLabels.operator_id }}
          <button @click="clearFilter('operator_id')"><X class="w-3 h-3" /></button>
        </Badge>
      </div>

      <!-- テーブル -->
      <div class="border rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-muted border-b-2 border-border">
            <tr>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {{ t(dateTypeKey) }}
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('menu_id')">
                {{ t('dish_name') }}
                <SortIcon field="menu_id" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('process_id')">
                {{ t('process') }}
                <SortIcon field="process_id" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('device_id')">
                {{ t('device') }}
                <SortIcon field="device_id" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('operator_id')">
                {{ t('operator') }}
                <SortIcon field="operator_id" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('sensor_id')">
                {{ t('sensor') }}
                <SortIcon field="sensor_id" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {{ t('temperatures') }} (℃)
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('created_at')">
                {{ t('logs.created_at') }}
                <SortIcon field="created_at" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ t('note') }}</th>
              <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ t('actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="log in logs.data"
              :key="log.id"
              class="odd:bg-white even:bg-muted/30 hover:bg-muted/50 transition-colors border-b border-l-4"
              :class="isUpdated(log) ? 'border-l-orange-400' : 'border-l-transparent'"
            >
              <td class="px-3 py-2.5">
                {{
                  dateType === 'serving'
                    ? log.menu?.serving_date ? dayjs(log.menu.serving_date).format('MM/DD') : '-'
                  : log.menu?.cooking_date ? dayjs(log.menu.cooking_date).format('MM/DD') : '-'
                }}
              </td>
              <td class="px-3 py-2.5">{{ log.menu ? log.menu.name : '-' }}</td>
              <td class="px-3 py-2.5">{{ log.process ? log.process.name : '-' }}</td>
              <td class="px-3 py-2.5">{{ log.device ? log.device.name : '-' }}</td>
              <td class="px-3 py-2.5">{{ log.operator ? log.operator.name : '-' }}</td>
              <td class="px-3 py-2.5">{{ log.sensor ? log.sensor.name : '-' }}</td>
              <td class="px-3 py-2.5">
                <ul v-if="log.temperatures && log.temperatures.length" class="temp-grid">
                <li v-for="temp in log.temperatures" :key="temp.recorded_at">
                    {{ formatTemp(temp.value) }}
                </li>
                </ul>
                <span v-else>-</span>
              </td>
              <td class="px-3 py-2.5 text-sm text-muted-foreground">
                {{ log.created_at ? dayjs(log.created_at).format('MM/DD HH:mm') : '' }}
              </td>
              <td class="px-3 py-2.5 text-sm text-muted-foreground">{{ log.note }}</td>
              <td class="px-3 py-2.5">
                <div class="flex items-center justify-center gap-1">
                  <Button variant="ghost" size="icon" class="h-7 w-7 text-blue-600" @click="openEdit(log)">
                    <Pencil class="w-3.5 h-3.5" />
                  </Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ページネーション -->
      <div class="flex items-center justify-between text-sm text-muted-foreground">
        <span>{{ startItem }}〜{{ endItem }} 件 / 全{{ logs.total }}件</span>
        <Pagination :paginator="logs" :onPageChange="goPage" />
      </div>
    </div>

    <!-- ========== ノート編集 Dialog ========== -->
    <TemperatureEditDialog
    v-model:open="showEditModal"
    :log="currentLog"
    />

  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import SortIcon from '@/Components/SortIcon.vue'
import Autocomplete from '@/Components/Autocomplete.vue'
import TemperatureEditDialog from '@/Components/TemperatureEditDialog.vue'

import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

import { ref, reactive, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { router, Link } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import 'dayjs/locale/ja'

dayjs.locale('ja')

import {
  Pencil, Printer, Filter,
  ChevronLeft, ChevronRight, Search, X,
} from '@lucide/vue'

const props = defineProps({
  logs: Object,
  tenants: Array,
  user: Object,
  filters: Object,
  success: String,
  dateType: String, // 'serving' or 'cooking' ← コントローラーから渡す
})

const { t } = useI18n()
const dateType = props.dateType

const dateTypeKey = computed(() => ({
  serving: 'logs.serving_date',
  cooking: 'logs.cooking_date',
}[dateType]))

const pageTitle = computed(() => ({
  serving: t('by_serving_date'),
  cooking: t('by_cooking_date'),
}[dateType]))

const isSuperAdmin = computed(() =>
  props.user?.roles?.some(r => r.name.toLowerCase() === 'super admin')
)

const openDrawer = ref(true)
const dateInputRef = ref(null)

const isUpdated = (log) => log.updated_at && log.created_at !== log.updated_at

const form = reactive({
  menu_id: props.filters.menu_id || '',
  sensor_id: props.filters.sensor_id || '',
  device_id: props.filters.device_id || '',
  operator_id: props.filters.operator_id || '',
  process_id: props.filters.process_id || '',
  per_page: props.filters.per_page || 20,
  sort_by: props.filters.sort_by || 'menu_id',
  sort_dir: props.filters.sort_dir || 'desc',
  date: props.filters.date || dayjs().format('YYYY-MM-DD'),
  tenant_id: props.filters.tenant_id ? String(props.filters.tenant_id) : 'all',
})

const formLabels = reactive({
  menu_id: '',
  process_id: '',
  sensor_id: '',
  device_id: '',
  operator_id: '',
})

const hasActiveFilters = computed(() =>
  form.menu_id || form.process_id || form.sensor_id || form.device_id || form.operator_id
)

const persistQuery = () => ({
  menu_id: form.menu_id || '',
  sensor_id: form.sensor_id || '',
  device_id: form.device_id || '',
  operator_id: form.operator_id || '',
  process_id: form.process_id || '',
  date: form.date || '',
  per_page: form.per_page,
  sort_by: form.sort_by,
  sort_dir: form.sort_dir,
  page: props.logs.current_page || 1,
  tenant_id: form.tenant_id === 'all' ? '' : form.tenant_id,
})

const startItem = computed(() => props.logs.per_page * (props.logs.current_page - 1) + 1)
const endItem = computed(() => Math.min(props.logs.per_page * props.logs.current_page, props.logs.total))

const routeName = dateType === 'serving' ? 'temperatures.by-serving-date' : 'temperatures.by-cooking-date'

const submitSearch = () => { router.get(route(routeName), { ...persistQuery(), page: 1 }, { preserveState: true, replace: true }) }
const goPage = (page) => { router.get(route(routeName), { ...persistQuery(), page }, { preserveState: true, replace: true }) }

const resetSearch = () => {
  form.menu_id = ''
  form.sensor_id = ''
  form.device_id = ''
  form.operator_id = ''
  form.process_id = ''
  submitSearch()
}

const clearFilter = (field) => {
  form[field] = ''
  formLabels[field] = ''
  submitSearch()
}

const changeDate = (offset) => {
  form.date = dayjs(form.date).add(offset, 'day').format('YYYY-MM-DD')
  submitSearch()
}

const openDatePicker = () => {
  if (dateInputRef.value?.showPicker) {
    dateInputRef.value.showPicker()
  } else {
    dateInputRef.value?.focus()
  }
}

const onDateInput = () => { submitSearch() }

const sortBy = (field) => {
  if (form.sort_by === field) form.sort_dir = form.sort_dir === 'asc' ? 'desc' : 'asc'
  else { form.sort_by = field; form.sort_dir = 'desc' }
  submitSearch()
}

const formatTemp = (v) => v != null ? Number(v).toFixed(1) : '-'

const showEditModal = ref(false)
const currentLog = ref(null)
const openEdit = (log) => {
  currentLog.value = log
  showEditModal.value = true
}

const exportPdf = () => {
  const query = persistQuery()
  delete query.page
  delete query.per_page
  const params = new URLSearchParams(query).toString()
  const url = `${route('pdf.temperature')}?${params}`
  window.open(url, '_blank')
}
</script>

<style>
.temp-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 4px 12px;
  list-style: none;
  padding: 0;
  margin: 0;
}
</style>