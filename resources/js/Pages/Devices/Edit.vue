<template>
  <AppLayout>
    <template #header>{{ t('edit_device') }}</template>

    <div class="p-6">
      <div class="space-y-4">
      <form @submit.prevent="submitForm">
        <!-- Code -->
        <div>
          <label class="block mb-1">{{ t('code') }}</label>
          <input
            v-model="form.code"
            @input="form.code = toHalfWidth(form.code)"
            type="text"
            placeholder="Code"
            class="border rounded px-3 py-2 w-full"
          />
          <p v-if="errors.code" class="text-red-500 text-sm mt-1">{{ errors.code }}</p>
        </div>

        <!-- Name -->
        <div>
          <label class="block mb-1">{{ t('name') }}</label>
          <input v-model="form.name" type="text" class="border rounded px-3 py-2 w-full" />
          <p v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name }}</p>
        </div>

        <!-- process -->
        <div>
          <label class="block mb-1">{{ t('process') }}</label>
          <input
            v-model="form.process"
            @input="form.process = toHalfWidth(form.process)"
            type="text"
            placeholder="Process"
            class="border rounded px-3 py-2 w-full"
          />
          <p v-if="errors.process" class="text-red-500 text-sm mt-1">{{ errors.process }}</p>
        </div>

        <!-- Serial Number -->
        <div>
          <label class="block mb-1">{{ t('measurement') }}</label>
          <input
            v-model="form.measurement"
            @input="form.measurement = toHalfWidth(form.measurement)"
            type="text"
            placeholder="Serial Number"
            class="border rounded px-3 py-2 w-full"
          />
          <p v-if="errors.measurement" class="text-red-500 text-sm mt-1">{{ errors.measurement }}</p>
        </div>

        <!-- Disabled -->
        <div class="flex items-center">
          <input type="checkbox" v-model="form.disabled" id="disabled" class="mr-2" />
          <label for="disabled">{{ t('disabled') }}</label>
        </div>

        <!-- Display Order -->
        <div class="mb-4">
          <label class="block mb-1">{{ t('display_order') }}</label>
          <input
            v-model.number="form.display_order"
            type="number"
            class="border rounded px-3 py-2 w-full"
          />
        </div>

        <!-- Buttons -->
        <div class="flex space-x-2">
          <button
            type="submit"
            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
          >
            {{ t('update') }}
          </button>
          <button
            @click="router.get(route('devices.index'), props.filters, { preserveState: true })"
            class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400"
            >
            {{ t('cancel') }}
          </button>
        </div>
      </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, reactive, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const props = defineProps({
  device: Object,
  filters: Object
})

const { t } = useI18n()

const form = reactive({
  code: props.device.code,
  name: props.device.name,
  process: props.device.process,
  measurement: props.device.measurement,
  disabled: props.device.disabled,
  display_order: props.device.display_order
})

const errors = reactive({
  code: '',
  name: '',
  process: '',
  measurement: '',
})

// リアルタイム重複チェック: code
watch(() => form.code, async (newCode) => {
  if (!newCode) { errors.code = ''; return }
  try {
    const response = await axios.post(route('devices.checkCode'), { code: newCode, id: props.device.id })
    errors.code = response.data.exists ? t('code_already_exists') : ''
  } catch (e) {
    console.error(e)
  }
})
// 全角→半角変換
const toHalfWidth = (str) => {
  if (!str) return ''
  return str.replace(/[！-～]/g, (s) => String.fromCharCode(s.charCodeAt(0) - 0xFEE0))
            .replace(/　/g, ' ') // 全角スペースを半角スペースに
}

const submitForm = () => {
  router.put(
    route('devices.update', props.device.id), // filters は付けない
    form,
    {
      preserveState: true,
      onError: (err) => Object.assign(errors, err),
      onSuccess: () => router.get(route('devices.index', props.filters)), // index の検索条件を保持して戻る
    }
  )
}

</script>