<template>
  <AppLayout :title="t('edit_sensor')">
    <div class="p-6 max-w-xl mx-auto">
      <h1 class="text-2xl font-bold mb-6">{{ t('edit_sensor') }}</h1>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1">{{ t('sensor_code') }}</label>
          <input v-model="form.code" type="text" maxlength="10" class="w-full border rounded px-3 py-2"/>
        </div>

        <div>
          <label class="block mb-1">{{ t('sensor_name') }}</label>
          <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2"/>
        </div>

        <div>
          <label class="block mb-1">{{ t('model') }}</label>
          <input v-model="form.model" type="text" maxlength="10" class="w-full border rounded px-3 py-2"/>
        </div>

        <div>
          <label class="block mb-1">{{ t('serial_number') }}</label>
          <input v-model="form.serial_number" type="text" maxlength="7" class="w-full border rounded px-3 py-2"/>
        </div>

        <div class="flex items-center space-x-2">
          <input v-model="form.disabled" type="checkbox" class="h-4 w-4"/>
          <label>{{ t('disabled') }}</label>
        </div>

        <div>
          <label class="block mb-1">{{ t('display_order') }}</label>
          <input v-model.number="form.display_order" type="number" min="1" class="w-full border rounded px-3 py-2"/>
        </div>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
          {{ t('save') }}
        </button>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  sensor: Object
})

const { t } = useI18n()

const form = useForm({
  code: props.sensor.code,
  name: props.sensor.name,
  model: props.sensor.model,
  serial_number: props.sensor.serial_number,
  disabled: props.sensor.disabled,
  display_order: props.sensor.display_order
})

function submit() {
  form.put(`/sensors/${props.sensor.id}`)
}
</script>
