<template>
  <AppLayout>
    <template #header>{{ t('add_device') }}</template>

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

        <label class="block">{{ t('process') }}</label>
        <input
          v-model="form.process"
          @input="form.process = toHalfWidth(form.process)"
          type="text"
          placeholder="Process"
          class="border rounded px-3 py-2 w-full"
        />

        <label class="block">{{ t('measurement') }}</label>
        <input
          v-model="form.measurement"
          @input="form.measurement = toHalfWidth(form.measurement)"
          type="text"
          placeholder="Measurable"
          class="border rounded px-3 py-2 w-full"
        />

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
            @click="router.get(route('devices.index'), props.filters, { preserveState: true })"
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
// Props に mode と device_id を追加
const props = defineProps({
  filters: Object,
  device: Object, // 追加
  mode: { type: String, default: '' }
})

const form = reactive({
  code: props.device?.code ?? '',
  name: props.device?.name ?? '',
  model: props.device?.process ?? '',
  serial_number: props.device?.measurement ?? '',
  disabled: props.device?.disabled ?? 1,
  display_order: props.device?.display_order ?? 1
})

// リアルタイム重複チェック
const errors = reactive({ code: '', name: '', process: '', measurement: '', disabled: '', display_order: '' })

const checkCode = async (code) => {
  if (!code) { errors.code = ''; return }
  try {
    const response = await axios.post(route('devices.checkCode'), { code })
    errors.code = response.data.exists ? t('code_already_exists') : ''
  } catch (e) {
    console.error(e)
  }
}

// コピー処理
onMounted(() => {
  if (props.mode === 'copy' && props.device_id) {
    const device = props.devices.find(s => s.id === props.device_id)
    if (device) {
      form.code = device.code
      form.name = device.name
      form.process = device.process
      form.measurement = device.measurement
      form.disabled = device.disabled
      form.display_order = device.display_order

      checkCode(device.code)
    }
  }
})

// watch で入力中にもチェック
watch(() => form.code, (newCode) => checkCode(newCode))

// 全角→半角変換
const toHalfWidth = (str) => {
  if (!str) return ''
  return str.replace(/[！-～]/g, (s) => String.fromCharCode(s.charCodeAt(0) - 0xFEE0))
            .replace(/　/g, ' ') // 全角スペースを半角スペースに
}

const submit = () => {
  router.post(route('devices.store'), form, {
    preserveState: true,
    onSuccess: () => router.get(route('devices.index', props.filters)), // index の検索条件を保持して戻る
    onError: (errs) => Object.assign(errors, errs)
  })
}
</script>
