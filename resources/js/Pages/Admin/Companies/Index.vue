<template>
  <AppLayout>
    <template #header>{{ t('company_list') }}</template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

        <!-- 検索フォーム -->
        <div class="flex justify-between items-center">
          <input
            v-model="search"
            @input="fetchCompanies"
            type="text"
            placeholder="Search..."
            class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-3 py-2"
          />

          <Link
            :href="route('admin.companies.create')"
            class="bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-600"
          >
            {{ t('create_new') }}
          </Link>
        </div>

        <!-- 会社一覧テーブル -->
        <div class="overflow-x-auto bg-white shadow overflow-hidden sm:rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t('company_name') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t('address') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t('phone') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t('fax') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t('actions') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="company in companies.data" :key="company.id">
                <td class="px-6 py-4 whitespace-nowrap">{{ company.name }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ company.address }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ company.phone }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ company.fax }}</td>
                <td class="px-6 py-4 whitespace-nowrap space-x-2">
                  <Link :href="route('admin.companies.edit', company.id)" class="text-blue-600 hover:underline">{{ t('edit') }}</Link>
                  <button @click="destroy(company.id)" class="text-red-600 hover:underline">{{ t('delete') }}</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ページネーション -->
        <div class="mt-4">
          <nav class="inline-flex -space-x-px rounded-md shadow-sm">
            <button
              v-for="page in companies.last_page"
              :key="page"
              @click="gotoPage(page)"
              class="px-3 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"
              :class="{ 'bg-indigo-500 text-white': page === companies.current_page }"
            >
              {{ page }}
            </button>
          </nav>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { router } from '@inertiajs/vue3'

const { t } = useI18n()
const { props } = usePage()

const search = ref('')
const companies = ref(props.companies || { data: [], current_page: 1, last_page: 1 })

// 会社一覧取得
const fetchCompanies = () => {
  router.get(route('admin.companies.index'), { search: search.value }, { preserveState: true, replace: true })
}

// ページ切替
const gotoPage = (page) => {
  router.get(route('admin.companies.index'), { page, search: search.value }, { preserveState: true, replace: true })
}

// 削除
const destroy = (id) => {
  if (!confirm(t('confirm_delete'))) return
  router.delete(route('admin.companies.destroy', id), {
    onSuccess: () => fetchCompanies()
  })
}
</script>




