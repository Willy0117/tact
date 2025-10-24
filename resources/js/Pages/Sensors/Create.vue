<template>
  <AppLayout>
    <template #header>{{ t('create_sensor') }}</template>

    <div class="p-6 max-w-xl mx-auto space-y-4">
      <!-- 上部アクションボタン -->
      <div class="flex justify-end space-x-2 mb-4">
        <Link :href="route('sensors.index')" class="px-4 py-2 rounded border hover:bg-gray-100">
          {{ t('cancel') }}
        </Link>
        <button @click.prevent="submit" class="px-4 py-2 rounded bg-blue-500 text-white hover:bg-blue-600">
          {{ t('save') }}
        </button>
      </div>

      <!-- フォーム本体 -->
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
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  sensor: Object  // Editの場合は受け取る
})

const { t } = useI18n()

const form = useForm({
  code: props.sensor?.code || '',
  name: props.sensor?.name || '',
  model: props.sensor?.model || '',
  serial_number: props.sensor?.serial_number || '',
  disabled: props.sensor?.disabled || false,
  display_order: props.sensor?.display_order || 1
})

function submit() {
  if(props.sensor){
    form.put(`/sensors/${props.sensor.id}`)
  } else {
    form.post('/sensors')
  }
}
</script>



