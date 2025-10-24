<template>
  <AppLayout>
    <template #header>{{ t('sensor_list') }}</template>

    <div class="p-6 w-full space-y-4">

      <!-- 上部：検索フォーム + 追加ボタン + 削除ボタン -->
      <div class="flex justify-between mb-4 items-center space-x-2">
        <form @submit.prevent="submitSearch" class="flex space-x-2 flex-1">
          <input
            v-model="form.search"
            type="text"
            placeholder="Search..."
            class="flex-1 border rounded px-3 py-2"
          />
          <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            {{ t('search') }}
          </button>
        </form>

       <!-- 件数選択 + 操作ボタン -->
        <div class="flex space-x-2 items-center">

          <!-- 件数選択 -->
          <select v-model="perPage" @change="changePerPage" class="border rounded px-2 py-1">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
          </select>

          <!-- 複数削除ボタン -->
          <button
            @click="deleteSelected"
            class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
            :disabled="selected.length === 0"
          >
            {{ t('delete_selected') }}
          </button>

          <!-- 追加ボタン -->
          <Link
            :href="route('sensors.create')"
            class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
          >
            {{ t('add_sensor') }}
          </Link>
        </div>
      </div>

      <!-- テーブル -->
      <div class="overflow-x-auto">
        <table class="min-w-full table-auto border-collapse border border-gray-300">
          <thead class="bg-gray-100">
            <tr>
              <th class="p-2 border text-center">
                <input type="checkbox" @change="toggleAll" :checked="allSelected"/>
              </th>
              <th class="p-2 border text-center">#</th>
              <th class="p-2 border">{{ t('code') }}</th>
              <th class="p-2 border">{{ t('name') }}</th>
              <th class="p-2 border">{{ t('model') }}</th>
              <th class="p-2 border">{{ t('serial_number') }}</th>
              <th class="p-2 border text-center">{{ t('disabled') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(sensor, index) in props.sensors.data" :key="sensor.id">
              <td class="p-2 border text-center">
                <input type="checkbox" v-model="selected" :value="sensor.id" />
              </td>
              <td class="p-2 border text-center">{{ startItem + index }}</td>
              <td class="p-2 border">{{ sensor.code }}</td>
              <td class="p-2 border">{{ sensor.name }}</td>
              <td class="p-2 border">{{ sensor.model }}</td>
              <td class="p-2 border">{{ sensor.serial_number }}</td>
              <td class="p-2 border text-center">{{ sensor.disabled ? t('enable') : t('disable') }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ページネーション -->
      <Pagination :paginator="props.sensors" :onPageChange="goPage" />

    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { useForm, router, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { ref, computed } from 'vue'

const props = defineProps({
  sensors: Object,
  filters: Object
})

const { t } = useI18n()

// 検索フォーム
const form = useForm({ search: props.filters.search || '' })
function submitSearch() {
  form.get('/sensors', { preserveState: true, replace: true })
}

// ページごとの表示件数
const perPage = ref(props.filters.per_page || 10)
function changePerPage() {
  router.get('/sensors', { search: form.search, per_page: perPage.value }, { preserveState: true, replace: true })
}

// 複数削除
const selected = ref([])
const allSelected = computed(() => selected.value.length === props.sensors.data.length)

function toggleAll(e) {
  if (e.target.checked) {
    selected.value = props.sensors.data.map(s => s.id)
  } else {
    selected.value = []
  }
}

function deleteSelected() {
  if (!confirm(t('confirm_delete'))) return
  router.delete('/sensors', { ids: selected.value }, { preserveState: true })
  selected.value = []
}

// ページ遷移
function goPage(page) {
  router.get('/sensors', { search: form.search, page }, { preserveState: true, replace: true })
}

// 表示件数
const startItem = computed(() => props.sensors.per_page * (props.sensors.current_page - 1) + 1)
const endItem = computed(() => Math.min(props.sensors.per_page * props.sensors.current_page, props.sensors.total))
</script>



