<template>
  <AppLayout>
    <template #header>{{ t('add_sensor') }}</template>

    <div class="p-6">
      <div class="space-y-4">

        <div>
          <label class="block">{{ t('code') }}</label>
          <input
            v-model="form.code"
            @input="form.code = toHalfWidth(form.code)"
            type="text"
            placeholder="Code"
            class="border rounded px-3 py-2 w-full"
          />
          <div v-if="errors.code" class="text-red-500 text-sm">{{ errors.code }}</div>
        </div>

        <div>
          <label class="block">{{ t('name') }}</label>
          <input v-model="form.name" type="text" class="border rounded px-3 py-2 w-full" />
          <div v-if="errors.name" class="text-red-500 text-sm">{{ errors.name }}</div>
        </div>

        <div>
          <label class="block">{{ t('model') }}</label>
          <input
            v-model="form.model"
            @input="form.model = toHalfWidth(form.model)"
            type="text"
            placeholder="Model"
            class="border rounded px-3 py-2 w-full"
          />
          <div v-if="errors.model" class="text-red-500 text-sm">{{ errors.model }}</div>
        </div>

        <div>
          <label class="block">{{ t('serial_number') }}</label>
          <input
            v-model="form.serial_number"
            @input="form.serial_number = toHalfWidth(form.serial_number)"
            type="text"
            placeholder="Serial Number"
            class="border rounded px-3 py-2 w-full"
          />
          <div v-if="errors.serial_number" class="text-red-500 text-sm">{{ errors.serial_number }}</div>
        </div>

        <div>
          <label class="block">{{ t('disabled') }}</label>
          <select v-model="form.disabled" class="border rounded px-3 py-2 w-full">
            <option :value="1">{{ t('enabled') }}</option>
            <option :value="0">{{ t('disabled') }}</option>
          </select>
        </div>

        <div>
          <label class="block">{{ t('display_order') }}</label>
          <input v-model.number="form.display_order" type="number" class="border rounded px-3 py-2 w-full" />
        </div>

        <div class="flex space-x-2">
          <button @click="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">{{ t('save') }}</button>
          <button
            @click="router.get(route('sensors.index'), props.filters, { preserveState: true })"
            class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400"
          >
          {{ t('cancel') }}
        </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router} from '@inertiajs/vue3'
import { reactive, ref, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()
// Props に mode と sensor_id を追加
const props = defineProps({
  filters: Object,
  sensor: Object, // 追加
  mode: { type: String, default: '' }
})

const form = reactive({
  code: props.sensor?.code ?? '',
  name: props.sensor?.name ?? '',
  model: props.sensor?.model ?? '',
  serial_number: props.sensor?.serial_number ?? '',
  disabled: props.sensor?.disabled ?? 1,
  display_order: props.sensor?.display_order ?? 1
})

// リアルタイム重複チェック
const errors = reactive({ code: '', name: '', model: '', serial_number: '', disabled: '', display_order: '' })

const checkCode = async (code) => {
  if (!code) { errors.code = ''; return }
  try {
    const response = await axios.post(route('sensors.checkCode'), { code })
    errors.code = response.data.exists ? t('code_already_exists') : ''
  } catch (e) {
    console.error(e)
  }
}

const checkSerialNumber = async (serial_number) => {
  if (!serial_number) { errors.serial_number = ''; return }
  try {
    const response = await axios.post(route('sensors.checkSerialNumber'), { serial_number })
    errors.serial_number = response.data.exists ? t('serial_number_already_exists') : ''
  } catch (e) {
    console.error(e)
  }
}

// コピー処理
onMounted(() => {
  if (props.mode === 'copy' && props.sensor_id) {
    const sensor = props.sensors.find(s => s.id === props.sensor_id)
    if (sensor) {
      form.code = sensor.code
      form.name = sensor.name
      form.model = sensor.model
      form.serial_number = sensor.serial_number
      form.disabled = sensor.disabled
      form.display_order = sensor.display_order

      checkCode(sensor.code)
      checkSerialNumber(sensor.serial_number)
    }
  }
})

// watch で入力中にもチェック
watch(() => form.code, (newCode) => checkCode(newCode))
watch(() => form.serial_number, (newSerial) => checkSerialNumber(newSerial))

// 全角→半角変換
const toHalfWidth = (str) => {
  if (!str) return ''
  return str.replace(/[！-～]/g, (s) => String.fromCharCode(s.charCodeAt(0) - 0xFEE0))
            .replace(/　/g, ' ') // 全角スペースを半角スペースに
}

const submit = () => {
  router.post(route('sensors.store'), form, {
    preserveState: true,
    onSuccess: () => router.get(route('sensors.index', props.filters)), // index の検索条件を保持して戻る
    onError: (errs) => Object.assign(errors, errs)
  })
}
</script>

