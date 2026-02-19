<template>
  <AppLayout>
    <template #header>{{ t('temperature') }}</template>

    <div dir="rtl">
      <!-- 検索 トリガーボタン -->
        <div class="relative size-4 ...">
          <div class="absolute start-0 top-0 size-14 ...">
              <button
              @click="openDrawer = !openDrawer"
              class="p-2 rounded hover:bg-gray-200 flex items-center justify-center"
            >
              <FunnelIcon class="w-5 h-5 text-gray-600" />
            </button>
          </div>
        </div>
    </div>
    <div v-if="success" class="mb-4 rounded bg-green-100 px-4 py-2 text-green-800">
      {{ success }}
    </div>

    <div class="p-6 space-y-4">
      <!-- 検索フォーム -->
      <div v-if="openDrawer" class="grid grid-cols-5 gap-2 items-end">
     
          <!-- Menu Autocomplete -->
          <Autocomplete
            v-model="form.menu_id"
            :label="t('dish_name')"
            :placeholder="t('select.menu')"
            fetch-url="/menus/autocomplete"
          />

          <Autocomplete
            v-model="form.process_id"
            :label="t('process')"
            :placeholder="t('select.process')"
            fetch-url="/processes/autocomplete"
          />

          <Autocomplete
            v-model="form.sensor_id"
            :label="t('sensor')"
            :placeholder="t('select.sensor')"
            fetch-url="/sensors/autocomplete"
          />

          <Autocomplete
            v-model="form.device_id"
            :label="t('device')"
            :placeholder="t('select.device')"
            fetch-url="/devices/autocomplete"
          />

          <Autocomplete
            v-model="form.operator_id"
            :label="t('operator')"
            :placeholder="t('select.operator')"
            fetch-url="/operators/autocomplete"
          />

          <!-- Handy No -->
          <!-- div>
            <label class="block text-sm font-medium mb-1">{{ t('handy_no') }}</label>
            <input type="text" v-model="form.handy_no" placeholder="Handy No" class="border rounded px-3 py-2 w-full"/>
          </div -->
      </div>
      <div class="grid grid-cols-6 gap-2 items-end">

          <!-- 2行目：日付 + 献立日/調理日 + 検索ボタン -->
          <!-- From日 -->
          <div class="relative">
            <label class="block text-sm font-medium mb-1">{{ t('from') }}</label>
            <div class="relative flex items-center">
              <input 
                type="date" 
                v-model="form.date_from" 
                class="w-full border rounded px-3 py-2" 
              />
            </div>
          </div>

          <!-- To日 -->
          <div class="relative">
            <label class="block text-sm font-medium mb-1">{{ t('to') }}</label>
            <div class="relative flex items-center">
              <input 
                type="date" 
                v-model="form.date_to" 
                class="w-full border rounded px-3 py-2" 
              />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">{{ t('date_type') }}</label>
            <select v-model="form.date_type" class="border rounded px-3 py-2 pr-8 appearance-none">
              <option value="serving">{{ t('logs.serving_date') }}</option>
              <option value="cooking">{{ t('logs.cooking_date') }}</option>
              <option value="created">{{ t('logs.created_date') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">{{ t('page') }}</label>
            <select
              v-model.number="form.per_page"
              @change="submitSearch"
              class="border rounded px-3 py-2 w-16 h-10"
            >
              <option v-for="n in [10,20,30,50]" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
          <div v-if="isSuperAdmin">
            <label class="block mb-1">{{ t('tenant') }}</label>
            <select
              v-model.number="form.tenant_id"
              :placeholder="t('select_tenant')"
              class="border rounded px-3 py-2 w-full"
            >
              <option value="">{{ t('select_tenant') }}</option>
              <option
                v-for="tenant in tenants"
                :key="tenant.id"
                :value="tenant.id"
              >
                {{ tenant.name }}
              </option>
            </select>
          </div>  
          <div class="flex items-center justify-end gap-2">
            <!-- 検索ボタン -->
            <button @click="submitSearch" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 whitespace-nowrap">
              {{ t('search') }}
            </button>
            <!-- PDFボタン -->
            <SecondaryButton @click="exportPdf()" class="p-2" title="PDF出力">
              <PrinterIcon class="h-5 w-5 text-gray-600" /> 
            </SecondaryButton>
          </div>
      </div>

      <!-- ログ一覧テーブル -->
      <table class="min-w-full table-auto border-collapse border border-gray-300 text-sm">
        <thead>
          <tr class="bg-gray-200">
            <th
              class="px-3 py-2 cursor-pointer"
              @click="toggleDateSort"
            >
             {{ t(dateTypeKey) }}
              <span v-if="form.sort_by === 'menu_date'">{{ form.sort_dir === 'asc' ? '▲' : '▼' }}</span>
            </th>
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('menu_id')">
              {{ t('dish_name') }}
              <span v-if="form.sort_by==='menu_id'">{{ form.sort_dir==='asc'?'▲':'▼' }}</span>
            </th>
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('process_id')">
              {{ t('process') }}
              <span v-if="form.sort_by==='process_id'">{{ form.sort_dir==='asc'?'▲':'▼' }}</span>
            </th>
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('device_id')">
              {{ t('device') }}
              <span v-if="form.sort_by==='device_id'">{{ form.sort_dir==='asc'?'▲':'▼' }}</span>
            </th>
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('operator_id')">
              {{ t('operator') }}
              <span v-if="form.sort_by==='operator_id'">{{ form.sort_dir==='asc'?'▲':'▼' }}</span>
            </th>
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('sensor_id')">
              {{ t('sensor') }}
              <span v-if="form.sort_by==='sensor_id'">{{ form.sort_dir==='asc'?'▲':'▼' }}</span>
            </th>
            <!-- th class="px-3 py-2 cursor-pointer" @click="sortBy('handy_no')">
              {{ t('handy_no') }}
              <span v-if="form.sort_by==='handy_no'">{{ form.sort_dir==='asc'?'▲':'▼' }}</span>
            </th -->
            <th>
              {{ t('temperatures') }} (℃)
            </th>
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('created_at')">
              {{ t('logs.created_at') }}
              <span v-if="form.sort_by==='created_at'">{{ form.sort_dir==='asc'?'▲':'▼' }}</span>
            </th>
            <th class="px-3 py-2">{{ t('note') }}</th>
            <th class="px-3 py-2">{{ t('actions') }}</th>
          </tr>
        </thead>
        <tbody>
            <tr
              v-for="log in logs.data"
              :key="log.id"
              class="odd:bg-white even:bg-gray-100 border-l-4"
              :class="isUpdated(log)
                ? 'border-l-orange-400'
                : 'border-l-transparent'"
            >
            <td>
              {{
                form.date_type === 'serving'
                  ? log.menu?.serving_date
                    ? dayjs(log.menu.serving_date).format('MM/DD')
                    : '-'
                : form.date_type === 'cooking'
                  ? log.menu?.cooking_date
                    ? dayjs(log.menu.cooking_date).format('MM/DD')
                    : '-'
                : log.created_at
                  ? dayjs(log.created_at).format('MM/DD')
                  : '-'
              }}
            </td>

            <td>{{ log.menu ? log.menu.name : '-' }}</td>
            <td>{{ log.process ? log.process.name : '-' }}</td>
            <td>{{ log.device ? log.device.name : '-' }}</td>
            <td>{{ log.operator ? log.operator.name : '-' }}</td>
            <td>{{ log.sensor ? log.sensor.name : '-' }}</td>
            <!-- td class="mr px-3 py-2">{{ log.handy_no }}</td -->
            <td>
              <ul class="temp-grid">
                <li
                  v-for="temp in log.temperatures"
                  :key="temp.recorded_at"
                >
                  {{ formatTemp(temp.value) }}
                </li>
              </ul>
            </td>
            <td class="px-3 py-2">{{ log.created_at ? dayjs(log.created_at).format('MM/DD HH:mm') : '' }}</td>
            <td class="px-3 py-2">{{ log.note }}</td>
            <td class="px-3 py-2">
              <div class="flex justify-center space-x-1">
                <Link
                  :href="route('temperatures.edit', {temperature: log.id, ...persistQuery() })"
                  class="text-blue-500 hover:text-blue-700"
                >
                  <PencilIcon class="w-4 h-4"/>
                </Link>
                <button
                  @click="openNote(log)"
                  class="text-green-500 hover:text-green-700"
                >
                  <DocumentPlusIcon class="w-4 h-4"/>
                </button -->
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- ページネーション -->
      <Pagination :paginator="logs" :onPageChange="goPage" :startItem="startItem" :endItem="endItem"/>
    </div>
    <DialogModal :show="showNoteModal" @close="showNoteModal = false">
      <template #title>
        {{ t('note_edit') }}
      </template>

      <template #content>
        <textarea
          v-model="noteValue"
          rows="4"
          class="w-full border rounded px-3 py-2"
          placeholder="メモを入力"
        />
      </template>

      <template #footer>
        <div class="flex justify-end gap-3">
          <SecondaryButton @click="showNoteModal = false">
            {{ t('cancel') }}
          </SecondaryButton>

          <PrimaryButton @click="saveNote">
            {{ t('save') }}
          </PrimaryButton>
        </div>
      </template>
    </DialogModal>
 
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import Autocomplete from '@/Components/Autocomplete.vue'
import DialogModal from '@/Components/DialogModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

import { ref, reactive, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { router,Link } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import axios from 'axios'
import { PlusIcon, PencilIcon, PrinterIcon, FunnelIcon, MagnifyingGlassIcon, DocumentPlusIcon} from '@heroicons/vue/24/outline'


const props = defineProps({
  logs: Object,
  tenants: Array,
  user: Object,
  filters: Object,
  success: String,
})
console.log(props.logs);

const { t } = useI18n()

const dateTypeKey = computed(() => {
  return {
    serving: 'logs.serving_date',
    cooking: 'logs.cooking_date',
    created: 'logs.created_date',
  }[form.date_type] ?? ''
})

const isSuperAdmin = computed(() =>
  props.user?.roles?.some(r => r.name.toLowerCase() === 'super admin')
)
// 検索フォーム・per_page・sort・sort_dirを reactive で管理
const openDrawer = ref(false)

const isUpdated = (log) => {
  return log.updated_at && log.created_at !== log.updated_at
}

// Form
const form = reactive({
  menu_id: props.filters.menu_id || '',
  sensor_id: props.filters.sensor_id || '',
  device_id: props.filters.device_id || '',
  operator_id: props.filters.operator_id || '',
  handy_no: props.filters.handy_no || '',
  process_id: props.filters.process_id || '',
  per_page: props.filters.per_page || 20,
  sort_by: props.filters.sort_by || 'serving_date',
  sort_dir: props.filters.sort_dir || 'desc',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  date_type: props.filters.date_type || 'serving',
  tenant_id: props.filters.tenant_id,
})
// persistQueryに各検索項目を追加
const persistQuery = () => ({
  menu_id: form.menu_id || '',
  sensor_id: form.sensor_id || '',
  device_id: form.device_id || '',
  operator_id: form.operator_id || '',
  handy_no: form.handy_no || '',
  process_id: form.process_id || '',
  date_from: form.date_from || '',
  date_to: form.date_to || '',
  date_type: form.date_type || 'serving',
  per_page: form.per_page,
  sort_by: form.sort_by,
  sort_dir: form.sort_dir,
  page: props.current_page || 1,
  tenant_id: props.tenant_id,
})


const startItem = computed(() => props.logs.per_page * (props.logs.current_page - 1) + 1)
const endItem = computed(() => Math.min(props.logs.per_page * props.logs.current_page, props.logs.total))

// Search
const submitSearch = () => { router.get(route('temperatures.index'), {...form,page:1}, {preserveState:true}) }
const goPage = (page) => { router.get(route('temperatures.index'), {...form,page}, {preserveState:true}) }

const toggleDateSort = () => {
  if (form.sort_by === 'menu_date') {
    // すでに日付ソート中 → 昇降切り替え
    form.sort_dir = form.sort_dir === 'asc' ? 'desc' : 'asc'
  } else {
    // 初回クリック → 日付ソート開始
    form.sort_by = 'menu_date'
    form.sort_dir = 'desc' // 初期は新しい順がおすすめ
  }

  form.page = 1
  submitSearch()
}
// Sort
const sortBy = (field) => {
  if (form.sort_by===field) form.sort_dir=form.sort_dir==='asc'?'desc':'asc'
  else { form.sort_by=field; form.sort_dir='desc' }
  submitSearch()
}
// 小数点第一まで
const formatTemp = (v) => {
  return v != null ? Number(v).toFixed(1) : '-'
}

const showNoteModal = ref(false)
const currentLog = ref(null)
const noteValue = ref('')

const openNote = (log) => {
  currentLog.value = log
  noteValue.value = log.note ?? ''
  showNoteModal.value = true
}



const saveNote = () => {
  router.put(
    route('temperatures.updateNote', currentLog.value.id),
    { note: noteValue.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        // フロント側も即更新（UX向上）
        currentLog.value.note = noteValue.value
        showNoteModal.value = false
      },
    }
  )
}

const exportPdf = () => {
    // persistQuery() で現在の条件をまるごと取得
    const query = persistQuery();
    
    // PDFにはページネーション(page)や件数(per_page)は不要かもしれないので削除
    delete query.page;
    delete query.per_page;

    const params = new URLSearchParams(query).toString();
    const url = `${route('pdf.temperature')}?${params}`;
    
    window.open(url, '_blank');
};

</script>
<style>
.temp-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 4px 12px; /* 縦 横 */
  list-style: none;
  padding: 0;
  margin: 0;
}
</style>


