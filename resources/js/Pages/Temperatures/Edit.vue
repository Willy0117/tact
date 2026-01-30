<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Autocomplete from '@/Components/Autocomplete.vue'

import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  temperature: Object,
})

const form = useForm({
  handy_no: props.temperature.handy_no,
  menu_id: props.temperature.menu_id,
  device_id: props.temperature.device_id,
  operator_id: props.temperature.operator_id,
  sensor_id: props.temperature.sensor_id ?? null,
  process_id: props.temperature.process_id,
  temperatures: props.temperature.temperatures
    ? JSON.parse(JSON.stringify(props.temperature.temperatures))
    : [],
  note: props.temperature.note,
})

const submit = () => {
  form.put(route('temperatures.update', props.temperature.id))
}

const addRow = () => {
    form.temperatures.push({
        datetime: '',
        value: '',
    })
}

const removeRow = (index) => {
  form.temperatures.splice(index, 1)
}

</script>

<template>
  <AppLayout>
    <template #header>温度ログ編集</template>

    <div class="max-w-3xl mx-auto bg-white rounded shadow p-6 space-y-5">

      <!-- handy_no -->
      <div>
        <label class="label">{{ t('handy_no') }}</label>
        <input v-model="form.handy_no" type="number" class="input" />
      </div>

      <!-- セレクト群 -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <!-- Menu Autocomplete -->
          <Autocomplete
            v-model="form.menu_id"
            :label="t('menu')"
            :placeholder="t('select.menu')"
            :initial-item="{
                id: temperature.menu.id,
                label: temperature.menu.name
            }"
            fetch-url="/menus/autocomplete"
          />
        </div>
          <Autocomplete
            v-model="form.process_id"
            :label="t('process')"
            :placeholder="t('select.process')"
            :initial-item="{
                id: temperature.process.id,
                label: temperature.process.name
            }"
            fetch-url="/processes/autocomplete"
          />

          <Autocomplete
            v-model="form.sensor_id"
            :label="t('sensor')"
            :placeholder="t('select.sensor')"
            :initial-item="props.temperature.sensor
                ? { id: props.temperature.sensor.id, label: props.temperature.sensor.name }
                : null"

            fetch-url="/sensors/autocomplete"
          />

          <Autocomplete
            v-model="form.device_id"
            :label="t('device')"
            :placeholder="t('select.device')"
            :initial-item="{
                id: temperature.device.id,
                label: temperature.device.name
            }"
            fetch-url="/devices/autocomplete"
          />

          <Autocomplete
            v-model="form.operator_id"
            :label="t('operator')"
            :placeholder="t('select.operator')"
            :initial-item="{
                id: temperature.operator.id,
                label: temperature.operator.name
            }"

            fetch-url="/operators/autocomplete"
          />
      </div>

      <!-- temperatures -->
      <div class="space-y-2">
        <div
        v-for="(temp, index) in form.temperatures"
        :key="index"
        class="flex items-center gap-2"
        >
            <input
                type="datetime-local"
                v-model="form.temperatures[index].datetime"
                class="border rounded px-2 py-1"
            />

            <input
                type="number"
                step="0.1"
                v-model="form.temperatures[index].value"
                class="border rounded px-2 py-1"
            />

            <!-- 削除（これは残す） -->
            <button
                type="button"
                class="text-red-500"
                @click="form.temperatures.splice(index, 1)"
            >
                ×
            </button>
        </div>

        <button
            type="button"
            @click="addRow"
            class="text-blue-500 hover:text-blue-700 text-sm"
        >
            ＋ {{ t('add') }}
        </button>
      </div>


      <!-- note -->
      <div>
        <label class="label">メモ</label>
        <textarea v-model="form.note" rows="3" class="input" />
      </div>

      <!-- buttons -->
      <div class="flex justify-end gap-3 pt-4">
        <SecondaryButton @click="$inertia.visit(route('temperatures.index'))">
          {{ t('cancel') }} 
        </SecondaryButton>
        <PrimaryButton :disabled="form.processing" @click="submit">
          {{ t('update') }}
        </PrimaryButton>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.label {
  @apply block text-sm font-medium mb-1;
}
.input {
  @apply w-full border rounded px-3 py-2;
}
</style>

