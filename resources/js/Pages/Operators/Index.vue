<template>
  <AppLayout title="Operators">
    <div class="p-6">
      <h1 class="text-2xl font-bold mb-4">{{ t('operator_list') }}</h1>

      <Link
        href="/operators/create"
        class="mb-4 inline-block bg-blue-500 text-white px-4 py-2 rounded"
      >
        {{ t('add_operator') }}
      </Link>

      <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3">{{ t('name') }}</th>
              <th class="px-6 py-3">{{ t('email') }}</th>
              <th class="px-6 py-3">{{ t('role') }}</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="operator in operators" :key="operator.id">
              <td class="px-6 py-4">{{ operator.name }}</td>
              <td class="px-6 py-4">{{ operator.email }}</td>
              <td class="px-6 py-4">{{ operator.role }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  operators: Array
})

const { t } = useI18n()
</script>
