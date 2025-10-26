<template>
  <AppLayout>
    <template #header>{{ t('sensor_list') }}</template>

    <div class="p-6">
      <!-- 検索 + 追加 -->
      <div class="flex flex-col md:flex-row md:justify-between mb-4 space-y-2 md:space-y-0">
        <div class="flex space-x-2">
          <input
            v-model="form.search"
            @keyup.enter="submitSearch"
            type="text"
            placeholder="Search..."
            class="border rounded px-3 py-2"
          />
          <button @click="submitSearch" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            {{ t('search') }}
          </button>
        </div>

        <div class="flex space-x-2">
          <select v-model="form.per_page" @change="submitSearch" class="border rounded px-3 py-2">
            <option v-for="n in [10,20,30,50]" :key="n" :value="n">{{ n }}</option>
          </select>

          <Link
            :href="route('sensors.create', persistQuery())"
            class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 flex items-center space-x-1"
          >
            <PlusIcon class="w-4 h-4"/>
            <span>{{ t('add_sensor') }}</span>
          </Link>
        </div>
      </div>

      <!-- 複数削除ボタン -->
      <div class="mb-2">
        <button
          @click="bulkDelete"
          :disabled="selectedIds.length === 0"
          class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 disabled:opacity-50 flex items-center space-x-1"
        >
          <TrashIcon class="w-4 h-4"/>
          <span>{{ t('delete_selected') }}</span>
        </button>
      </div>

      <!-- センサー一覧テーブル -->
      <table class="min-w-full table-auto border-collapse border border-gray-300">
        <thead>
          <tr class="bg-gray-200">
            <th class="px-3 py-2">
              <input type="checkbox" v-model="selectAll" />
            </th>
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('code')">
              {{ t('code') }}
              <span v-if="form.sort==='code'">{{ form.direction==='asc'?'▲':'▼' }}</span>
            </th>
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('name')">
              {{ t('name') }}
              <span v-if="form.sort==='name'">{{ form.direction==='asc'?'▲':'▼' }}</span>
            </th>
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('model')">
              {{ t('model') }}
              <span v-if="form.sort==='model'">{{ form.direction==='asc'?'▲':'▼' }}</span>
            </th>
            <th class="px-3 py-2 cursor-pointer" @click="sortBy('serial_number')">
              {{ t('serial_number') }}
              <span v-if="form.sort==='serial_number'">{{ form.direction==='asc'?'▲':'▼' }}</span>
            </th>
            <th class="px-3 py-2">{{ t('updated_at') }}</th>
            <th class="px-3 py-2">{{ t('disabled') }}</th>
            <th class="px-3 py-2">{{ t('display_order') }}</th>
            <th class="px-3 py-2">{{ t('actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="sensor in sensors.data" :key="sensor.id" class="odd:bg-white even:bg-gray-50">

            <td class="px-3 py-2">
              <input type="checkbox" :value="sensor.id" v-model="selectedIds" />
            </td>
            <td class="px-3 py-2">{{ sensor.code }}</td>
            <td class="px-3 py-2">{{ sensor.name }}</td>
            <td class="px-3 py-2">{{ sensor.model }}</td>
            <td class="px-3 py-2">{{ sensor.serial_number }}</td>
            <td class="px-3 py-2">{{ sensor.updated_at }}</td>
            <td class="px-3 py-2">{{ sensor.disabled ? 'Yes' : 'No' }}</td>
            <td class="px-3 py-2">{{ sensor.display_order }}</td>
            <td class="px-3 py-2 flex space-x-1">
              <Link :href="route('sensors.edit', { sensor: sensor.id, ...persistQuery() })" class="text-blue-500 hover:text-blue-700">
                <PencilIcon class="w-4 h-4"/>
              </Link>
              <button @click="deleteSensor(sensor.id)" class="text-red-500 hover:text-red-700">
                <TrashIcon class="w-4 h-4"/>
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- ページネーション -->
      <Pagination :paginator="sensors" :onPageChange="goPage" :startItem="startItem" :endItem="endItem"/>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { Link, router } from '@inertiajs/vue3'
import { reactive, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  sensors: Object,
  filters: {
    type: Object,
    default: () => ({ search: '', per_page: 10, sort: 'id', direction: 'asc', page: 1 })
  }
})

const { t } = useI18n()

// 検索フォーム・per_page・sort・directionを reactive で管理
const form = reactive({
  search: props.filters.search,
  per_page: props.filters.per_page,
  sort: props.filters.sort,
  direction: props.filters.direction
})

// 選択削除
const selectedIds = reactive([])
const selectAll = computed({
  get() { return selectedIds.length === props.sensors.data.length },
  set(value) { selectedIds.splice(0, selectedIds.length, ...(value ? props.sensors.data.map(s => s.id) : [])) }
})

// persistQuery: 追加/編集でも検索・ページ・sort情報を保持
const persistQuery = () => ({
  search: form.search,
  per_page: form.per_page,
  sort_by: form.sort,      // ここを Controller に合わせる
  sort_dir: form.direction,
  page: props.sensors.current_page
})


// 検索実行
const submitSearch = () => {
  router.get(route('sensors.index'), persistQuery(), { preserveState: true })
}

// ページ番号クリック
const goPage = (page) => {
  router.get(route('sensors.index'), { ...persistQuery(), page }, { preserveState: true })
}

// 列ヘッダクリックでソート
const sortBy = (field) => {
  if (form.sort === field) form.direction = form.direction==='asc'?'desc':'asc'
  else { form.sort = field; form.direction = 'asc' }
  submitSearch()
}

// 行単位削除
const deleteSensor = (id) => {
  if (!confirm(t('confirm_delete'))) return
  router.delete(route('sensors.destroy', id), { preserveState: true })
}

// 複数削除
const bulkDelete = () => {
  if (!confirm(t('confirm_delete_selected'))) return
  router.post(route('sensors.bulkDelete'), { ids: selectedIds }, { preserveState: true })
}

// 表示件数計算
const startItem = computed(() => props.sensors.per_page * (props.sensors.current_page - 1) + 1)
const endItem = computed(() => Math.min(props.sensors.per_page * props.sensors.current_page, props.sensors.total))
</script>




