<template>
  <AppLayout>
    <template #header>{{ t('add_sensor') }}</template>

    <div class="p-6">
      <form @submit.prevent="submit">
        <div class="mb-4">
          <label class="block mb-1">{{ t('code') }}</label>
          <input v-model="form.code" type="text" class="w-full border rounded px-3 py-2"/>
        </div>
        <div class="mb-4">
          <label class="block mb-1">{{ t('name') }}</label>
          <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2"/>
        </div>
        <div class="mb-4">
          <label class="block mb-1">{{ t('model') }}</label>
          <input v-model="form.model" type="text" class="w-full border rounded px-3 py-2"/>
        </div>
        <div class="mb-4">
          <label class="block mb-1">{{ t('serial_number') }}</label>
          <input v-model="form.serial_number" type="text" class="w-full border rounded px-3 py-2"/>
        </div>

        <div class="flex space-x-2">
          <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">{{ t('save') }}</button>
          <Link :href="route('sensors.index', filters)" class="bg-gray-300 px-4 py-2 rounded">{{ t('cancel') }}</Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  filters: Object
})

const { t } = useI18n()

const form = useForm({
  code: '',
  name: '',
  model: '',
  serial_number: ''
})

function submit() {
  form.post('/sensors', { preserveState: true, replace: true })
}
</script>



