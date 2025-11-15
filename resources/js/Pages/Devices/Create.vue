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
       <div>
          <label class="block">{{ t('process') }}</label>
          <select v-model="form.process_id" class="mt-1 block w-full">
            <option value="">{{ t('please_select') }}</option>
            <option v-for="p in processes" :key="p.id" :value="p.id">
              {{ p.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block">
            <span class="block mb-1">{{ t('measurement') }}</span>

            <div
              class="flex items-center border rounded px-3 h-10 bg-white cursor-pointer"
              @click="form.measurement = form.measurement ? 0 : 1"
            >
              <input
                type="checkbox"
                v-model="form.measurement"
                :true-value="1"
                :false-value="0"
                class="w-4 h-4"
              />
              <span class="ml-2 text-gray-700">
                {{ form.measurement ? t('yes') : t('no') }}
              </span>
            </div>
          </label>
        </div>

        <div>
          <label class="block">
            <span class="block mb-1">{{ t('disabled') }}</span>

            <div
              class="flex items-center border rounded px-3 h-10 bg-white cursor-pointer"
              @click="form.disabled = form.disabled ? 0 : 1"
            >
              <input
                type="checkbox"
                v-model="form.disabled"
                :true-value="1"
                :false-value="0"
                class="w-4 h-4"
              />
              <span class="ml-2 text-gray-700">
                {{ form.disabled ? t('enable') : t('disable') }}
              </span>
            </div>
          </label>
        </div>

       <!-- Tenant 選択 (Super Admin のみ) -->
        <div v-if="isSuperAdmin" class="mt-4">
          <label class="block mb-1">{{ t('tenant') }}</label>
          <select v-model="form.tenant_id" class="border rounded px-3 py-2 w-full">
            <option :value="null">{{ t('select_tenant') }}</option>
            <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
              {{ tenant.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block">{{ t('display_order') }}</label>
          <input v-model.number="form.display_order" type="number" class="border rounded px-3 py-2 w-full" />
        </div>

        <div class="flex space-x-2">
          <button @click="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">{{ t('save') }}</button>
          <button
            type="button"
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
import { reactive, ref, watch, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()
const isSuperAdmin = computed(() =>
  props.user?.roles?.some(r => r.name.toLowerCase() === 'super admin')
)

// Props に mode と device_id を追加
const props = defineProps({
  filters: Object,
  tenants: Array,      // Super Admin のみ
  device: Object,
  user: Object, 
  processes: Array,
  mode: { type: String, default: '' }
})

const processes = props.processes

const form = reactive({
  code: props.device?.code ?? '',
  name: props.device?.name ?? '',
  process_id: props.device?.process_id ?? '',
  measurement: props.device?.measurement ?? 1,
  disabled: props.device?.disabled ?? 1,
  display_order: props.device?.display_order ?? 1,
  tenant_id: props.device
  ? props.device.tenant_id
  : (isSuperAdmin.value ? null : props.user?.tenant_id ?? null)
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
      form.process_id = device.process_id 
      form.measurement = device.measurement
      form.disabled = device.disabled
      form.display_order = device.display_order
      form.device_id = device.device_id
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
