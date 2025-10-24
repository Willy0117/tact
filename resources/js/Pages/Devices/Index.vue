<template>
  <AppLayout title="Devices">
    <div class="p-6">
      <h1 class="text-2xl font-bold mb-4">{{ t('device_list') }}</h1>

      <Link
        href="/devices/create"
        class="mb-4 inline-block bg-blue-500 text-white px-4 py-2 rounded"
      >
        {{ t('add_device') }}
      </Link>

      <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3">{{ t('name') }}</th>
              <th class="px-6 py-3">{{ t('serial_number') }}</th>
              <th class="px-6 py-3">{{ t('created_at') }}</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="device in devices" :key="device.id">
              <td class="px-6 py-4">{{ device.name }}</td>
              <td class="px-6 py-4">{{ device.serial_number }}</td>
              <td class="px-6 py-4">{{ device.created_at }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link,useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  devices: Array
})

const { t } = useI18n()
</script>
