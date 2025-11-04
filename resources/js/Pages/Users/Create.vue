<template>
  <AppLayout>
    <template #header>{{ t('create_user') }}</template>

    <div class="p-6 max-w-lg mx-auto">
      <h2 class="text-lg font-semibold mb-4">{{ t('create_user') }}</h2>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1">{{ t('name') }}</label>
          <input v-model="form.name" type="text" class="border rounded px-3 py-2 w-full" />
          <div v-if="errors.name" class="text-red-600 text-sm">{{ errors.name }}</div>
        </div>

        <div>
          <label class="block mb-1">{{ t('email') }}</label>
          <input v-model="form.email" type="email" class="border rounded px-3 py-2 w-full" />
          <div v-if="errors.email" class="text-red-600 text-sm">{{ errors.email }}</div>
        </div>

        <div>
          <label class="block mb-1">{{ t('password') }}</label>
          <input v-model="form.password" type="password" class="border rounded px-3 py-2 w-full" />
          <div v-if="errors.password" class="text-red-600 text-sm">{{ errors.password }}</div>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
          {{ t('create') }}
        </button>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { reactive } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  user: Object
})

const form = useForm({
  name: props.user?.name || '',
  email: props.user?.email || '',
  password: ''
})

const errors = reactive({})

const submit = () => {
  form.post(route('users.store'), {
    onError: (e) => Object.assign(errors, e)
  })
}
</script>
