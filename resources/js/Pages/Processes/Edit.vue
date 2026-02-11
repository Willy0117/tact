<template>
  <AppLayout>
    <template #header>
      <span v-if="isEdit">{{ t('processes.edit') }}</span>
      <span v-else-if="mode === 'copy'">{{ t('processes.copy') }}</span>
      <span v-else>{{ t('processes.add') }}</span>
    </template>
    <div class="p-6">
      <div class="space-y-4">
      <form @submit.prevent="submitForm">
        <!-- Name -->
        <div>
          <label class="block mb-1">{{ t('name') }}</label>
          <input v-model="form.name" type="text" class="border rounded px-3 py-2 w-full" />
          <p v-if="form.errors.name">{{ form.errors.name }}</p>
        </div>

        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700">
            {{ t('processes.threshold_type_label') ?? '判定条件' }}
          </label>

          <div class="mt-2 flex gap-6">
            <label class="inline-flex items-center">
              <input
                type="radio"
                class="text-indigo-600"
                value="upper"
                v-model="form.threshold_type"
              />
              <span class="ml-2">
                {{ t('processes.threshold_type.upper') }}
              </span>
            </label>

            <label class="inline-flex items-center">
              <input
                type="radio"
                class="text-indigo-600"
                value="lower"
                v-model="form.threshold_type"
              />
              <span class="ml-2">
                {{ t('processes.threshold_type.lower') }}
              </span>
            </label>

            <label class="inline-flex items-center">
              <input
                type="radio"
                value="none"
                v-model="form.threshold_type"
              />
              <span class="ml-2">
                {{ t('processes.threshold_type.none') }}
              </span>
            </label>
          </div>
        </div>

        <div v-if="form.threshold_type !== 'none'">
          <label class="block mb-1">{{ t('processes.threshold_value') }}</label>
          <input
            type="text"
            v-model="form.threshold_value"
            placeholder="例: 76.0"
            @blur="normalizeThreshold"
            class="border rounded px-3 py-2 w-full"
          />
          <p v-if="form.errors.threshold_value"
            class="text-red-500 text-sm mt-1">
            {{ form.errors.threshold_value }}
          </p>
        </div>

        <div v-if="isSuperAdmin" class="mt-4">
          <label class="block mb-1">{{ t('tenant') }}</label>
          <select v-model="form.tenant_id" class="border rounded px-3 py-2 w-full">
            <option :value="null">{{ t('select_tenant') }}</option>
            <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
              {{ tenant.name }}
            </option>
          </select>
        </div>
        <!-- Display Order -->
        <div class="mb-4">
          <label class="block mb-1">{{ t('display_order') }}</label>
          <input
            v-model="form.display_order"
            type="text"
            class="border rounded px-3 py-2 w-full"
            @blur="normalizeThreshold"
          />
          <p v-if="form.errors.display_order" class="text-red-500 text-sm mt-1">{{ form.errors.display_order }}</p>

        </div>
        <!-- Disabled -->
        <div>
          <label class="block">
            <span class="block mb-1">{{ t('status') }}</span>

            <div class="flex items-center space-x-6 h-10">
              <label class="flex items-center cursor-pointer">
                <input
                  type="radio"
                  v-model.number="form.disabled"
                  :value="1"
                />
                <span class="ml-2">{{ t('enable') }}</span>
              </label>

              <label class="flex items-center cursor-pointer">
                <input
                  type="radio"
                  v-model.number="form.disabled"
                  :value="0"
                />
                <span class="ml-2">{{ t('disable') }}</span>
              </label>
            </div>
          </label>

        </div>
        <!-- Buttons -->
        <div class="flex space-x-2">
          <button
            type="submit"
            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
          >
            {{ isEdit ? t('update') : t('save') }}
          </button>

          <button
            type="button"
            @click="router.get(route('processes.index'), props.filters, { preserveState: true })"
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
import { Link, router, useForm } from '@inertiajs/vue3'
import { ref, reactive, watch, computed} from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const props = defineProps({
  process: {
    type: Object,
    default: () => ({}),
  },
  tenants: Array,
  user: Object,
  filters: Object,
  mode: String,
})

console.log(props.process)

const { t } = useI18n({ useScope: 'global' })

const isSuperAdmin = computed(() =>
  props.user?.roles?.some(r => r.name.toLowerCase() === 'super admin')
)

const form = useForm({
  name: props.process?.name ?? '',
  display_order: props.process?.display_order ?? 0,
  threshold_value: props.process?.threshold_value ?? '',
  threshold_type: props.process?.threshold_type ?? 'lower',
  tenant_id: props.process?.tenant_id
    ?? (isSuperAdmin.value ? null : props.user?.tenant_id ?? null),
  disabled:props.process?.disabled ?? 1,
})

const errors = reactive({
  name: '',
})

const thresholdTypes = [
  { label: t('processes.threshold_type.upper'), value: 'upper' },
  { label: t('processes.threshold_type.lower'), value: 'lower' },
  { label: t('processes.threshold_type.none'), value: 'none' },
]

watch(
  () => form.threshold_type,
  (type) => {
    if (type === 'none') {
      form.threshold_value = null
    }
  }
)

const normalizeThreshold = () => {
  form.threshold_value = string(form.threshold_value ?? '').replace(/[^\d.-]/g, '')
}

const isEdit = computed(() => props.mode === 'edit')

const submitForm = () => {
  if (isEdit.value) {
    router.put(
      route('processes.update', props.process.id), // filters は付けない
      form,
      {
        preserveState: true,
        onError: (err) => Object.assign(errors, err),
        onSuccess: () => router.get(route('processes.index', props.filters)), // index の検索条件を保持して戻る
      }
    )
  } else {
    router.post(route('processes.store'),
      form, {
        preserveState: true,
        onSuccess: () => router.get(route('processes.index', props.filters)), // index の検索条件を保持して戻る
        onError: (errs) => Object.assign(errors, errs)
      })
  }
}
</script>