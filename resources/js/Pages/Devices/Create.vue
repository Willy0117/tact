<template>
  <AppLayout title="Create Device">
    <div class="p-6">
      <h1 class="text-2xl font-bold mb-4">{{ t('add_device') }}</h1>

      <form @submit.prevent="submit">
        <div class="mb-4">
          <label class="block mb-1">{{ t('name') }}</label>
          <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2"/>
        </div>

        <div class="mb-4">
          <label class="block mb-1">{{ t('serial_number') }}</label>
          <input v-model="form.serial_number" type="text" class="w-full border rounded px-3 py-2"/>
        </div>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
          {{ t('save') }}
        </button>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const form = useForm({
  name: '',
  serial_number: '',
})

function submit() {
  form.post('/devices')
}
</script>
