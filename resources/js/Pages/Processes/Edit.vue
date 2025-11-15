<template>
  <AppLayout>
    <template #header>{{ t('edit_process') }}</template>

    <div class="p-6">
      <div class="space-y-4">
      <form @submit.prevent="submitForm">
        <!-- Name -->
        <div>
          <label class="block mb-1">{{ t('name') }}</label>
          <input v-model="form.name" type="text" class="border rounded px-3 py-2 w-full" />
          <p v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name }}</p>
        </div>
        <!-- Disabled -->
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

        <!-- Buttons -->
        <div class="flex space-x-2">
          <button
            type="submit"
            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
          >
            {{ t('update') }}
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
import { Link, router } from '@inertiajs/vue3'
import { ref, reactive, watch, computed} from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const props = defineProps({
  process: Object,
  user: Object,
  filters: Object
})

const { t } = useI18n()

const form = reactive({
  name: props.process.name,
})

const errors = reactive({
  name: '',
})
const submitForm = () => {
  router.put(
    route('processes.update', props.process.id), // filters は付けない
    form,
    {
      preserveState: true,
      onError: (err) => Object.assign(errors, err),
      onSuccess: () => router.get(route('processes.index', props.filters)), // index の検索条件を保持して戻る
    }
  )
}

</script>