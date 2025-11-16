<template>
  <AppLayout>
    <template #header>{{ t('add_process') }}</template>

    <div class="p-6">
      <div class="space-y-4">
        <div>
          <label class="block">{{ t('name') }}</label>
          <input v-model="form.name" type="text" class="border rounded px-3 py-2 w-full" />
          <div v-if="errors.name" class="text-red-500 text-sm">{{ errors.name }}</div>
        </div>

        <div class="flex space-x-2">
          <button @click="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">{{ t('save') }}</button>
          <button
            type="button"
            @click="router.get(route('processs.index'), props.filters, { preserveState: true })"
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
import { reactive, ref, watch, onMounted, computed} from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()

// Props に mode と process_id を追加
const props = defineProps({
  filters: Object,
  process: Object, // 追加
  user: Object,
  mode: { type: String, default: '' }
})

const form = reactive({
  name: props.process?.name ?? '',
})

// リアルタイム重複チェック
const errors = reactive({ name: '' })

// コピー処理
onMounted(() => {
  if (props.mode === 'copy' && props.process_id) {
    const process = props.processs.find(s => s.id === props.process_id)
    if (process) {
      form.name = process.name
    }
  }
})

const submit = () => {
  router.post(route('processes.store'), form, {
    preserveState: true,
    onSuccess: () => router.get(route('processes.index', props.filters)), // index の検索条件を保持して戻る
    onError: (errs) => Object.assign(errors, errs)
  })
}
</script>
