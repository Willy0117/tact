<template>
  <AppLayout>
    <template #header>{{ t('temperature') }}</template>

    <div class="p-6">
      <!-- 検索フォーム -->
      <div class="flex gap-2 mb-4">
        <input v-model="form.menu_id" type="text" placeholder="Menu ID" class="border rounded px-3 py-2" />
        <input v-model="form.sensor_id" type="text" placeholder="Sensor ID" class="border rounded px-3 py-2" />
        <input v-model="form.device_id" type="text" placeholder="Device ID" class="border rounded px-3 py-2" />
        <input v-model="form.operator_id" type="text" placeholder="Operator ID" class="border rounded px-3 py-2" />
        <input v-model="form.serial_number" type="text" placeholder="Serial Number" class="border rounded px-3 py-2" />
        <button @click="submitSearch" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
          {{ t('search') }}
        </button>
      </div>

      <!-- ログ一覧テーブル -->
      <table class="min-w-full table-auto border-collapse border border-gray-300">
        <thead>
          <tr class="bg-gray-200">
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('menu_id')">
              {{ t('dish_name') }}
              <span v-if="form.sort==='menu_id'">{{ form.direction==='asc'?'▲':'▼' }}</span>
            </th>
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('sensor_id')">
              {{ t('sensor') }}
              <span v-if="form.sort==='sensor_id'">{{ form.direction==='asc'?'▲':'▼' }}</span>
            </th>
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('device_id')">
              {{ t('device') }}
              <span v-if="form.sort==='device_id'">{{ form.direction==='asc'?'▲':'▼' }}</span>
            </th>
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('operator_id')">
              {{ t('operator') }}
              <span v-if="form.sort==='operator_id'">{{ form.direction==='asc'?'▲':'▼' }}</span>
            </th>
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('serial_number')">
              {{ t('serial_number') }}
              <span v-if="form.sort==='serial_number'">{{ form.direction==='asc'?'▲':'▼' }}</span>
            </th>
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('temperatures')">
              {{ t('temperatures') }}
              <span v-if="form.sort==='temperatures'">{{ form.direction==='asc'?'▲':'▼' }}</span>
            </th>
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('updated_at')">
              {{ t('updated_at') }}
              <span v-if="form.sort==='updated_at'">{{ form.direction==='asc'?'▲':'▼' }}</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs.data" :key="log.id" class="odd:bg-white even:bg-gray-100">
            <td>{{ log.menu ? log.menu.dish_name : '-' }}</td>
            <td>{{ log.sensor ? log.sensor.name : '-' }}</td>
            <td>{{ log.device ? log.device.name : '-' }}</td>
            <td>{{ log.operator ? log.operator.name : '-' }}</td>
            <td class="px-3 py-2">{{ log.serial_number }}</td>
            <td>
                <ul>
                    <li v-for="temp in log.temperatures" :key="temp.recorded_at">
                        {{ temp.value }} ℃ ({{ dayjs(temp.datetime).format('YYYY/MM/DD HH:mm') }})
                    </li>
                </ul>
            </td>
            <td class="px-3 py-2">{{ log.updated_at ? dayjs(log.updated_at).format('YYYY/MM/DD HH:mm') : '' }}</td>
          </tr>
        </tbody>
      </table>

      <!-- ページネーション -->
      <Pagination :paginator="logs" :onPageChange="goPage" :startItem="startItem" :endItem="endItem"/>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { ref, reactive, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import dayjs from 'dayjs'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  logs: Object,
  tenants: Array,
  user: Object,
  filters: Object
})

console.log(props.logs);

const { t } = useI18n()

// 検索・ソート・ページネーション用
const form = reactive({
  menu_id: props.filters.menu_id || '',
  sensor_id: props.filters.sensor_id || '',
  device_id: props.filters.device_id || '',
  operator_id: props.filters.operator_id || '',
  serial_number: props.filters.serial_number || '',
  per_page: props.filters.per_page || 20,
  sort: props.filters.sort_by || 'id',
  direction: props.filters.sort_dir || 'asc'
})

// ページネーション用
const startItem = computed(() => props.logs.per_page * (props.logs.current_page - 1) + 1)
const endItem = computed(() => Math.min(props.logs.per_page * props.logs.current_page, props.logs.total))

// 検索実行
const submitSearch = () => {
  router.get(route('temperatures.index'), { ...form, page: 1 }, { preserveState: true })
}

// ページ移動
const goPage = (page) => {
  router.get(route('temperatures.index'), { ...form, page }, { preserveState: true })
}

// ソート
const sortBy = (field) => {
  if (form.sort === field) form.direction = form.direction==='asc'?'desc':'asc'
  else { form.sort = field; form.direction = 'asc' }
  submitSearch()
}
</script>
