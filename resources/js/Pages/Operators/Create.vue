<template>
  <AppLayout title="Create Operator">
    <div class="p-6">
      <h1 class="text-2xl font-bold mb-4">{{ t('add_operator') }}</h1>

      <form @submit.prevent="submit">
        <div class="mb-4">
          <label class="block mb-1">{{ t('name') }}</label>
          <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2"/>
        </div>

        <div class="mb-4">
          <label class="block mb-1">{{ t('email') }}</label>
          <input v-model="form.email" type="email" class="w-full border rounded px-3 py-2"/>
        </div>

        <div class="mb-4">
          <label class="block mb-1">{{ t('role') }}</label>
          <select v-model="form.role" class="w-full border rounded px-3 py-2">
            <option value="admin">{{ t('admin') }}</option>
            <option value="operator">{{ t('operator') }}</option>
          </select>
        </div>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
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

const { t } = useI18n()

const form = useForm({
  name: '',
  email: '',
  role: 'operator',
})

function submit() {
  form.post('/operators')
}
</script>
