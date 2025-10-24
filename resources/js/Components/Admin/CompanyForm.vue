<template>
  <form @submit.prevent="submit" class="space-y-4">
    <div>
      <label for="name" class="block text-sm font-medium text-gray-700">{{ t('company_name') }}</label>
      <input
        v-model="form.name"
        type="text"
        id="name"
        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
      />
      <p v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name }}</p>
    </div>

    <div>
      <label for="address" class="block text-sm font-medium text-gray-700">{{ t('address') }}</label>
      <input
        v-model="form.address"
        type="text"
        id="address"
        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
      />
      <p v-if="errors.address" class="text-red-500 text-sm mt-1">{{ errors.address }}</p>
    </div>

    <div>
      <label for="phone" class="block text-sm font-medium text-gray-700">{{ t('phone') }}</label>
      <input
        v-model="form.phone"
        type="text"
        id="phone"
        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
      />
      <p v-if="errors.phone" class="text-red-500 text-sm mt-1">{{ errors.phone }}</p>
    </div>

    <div>
      <label for="fax" class="block text-sm font-medium text-gray-700">{{ t('fax') }}</label>
      <input
        v-model="form.fax"
        type="text"
        id="fax"
        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
      />
      <p v-if="errors.fax" class="text-red-500 text-sm mt-1">{{ errors.fax }}</p>
    </div>

    <div class="flex justify-end space-x-2">
      <button
        type="button"
        @click="$emit('cancel')"
        class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
      >
        {{ t('cancel') }}
      </button>
      <button
        type="submit"
        class="px-4 py-2 bg-indigo-500 text-white rounded hover:bg-indigo-600"
      >
        {{ form.id ? t('update') : t('create') }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  company: { type: Object, default: () => ({ name: '', address: '', phone: '', fax: '' }) },
  action: { type: String, required: true },
  method: { type: String, default: 'post' }
})

const form = reactive({ ...props.company })
const errors = reactive({})
const { t } = useI18n()

const submit = () => {
  // エラー初期化
  Object.keys(errors).forEach((key) => errors[key] = null)

  router[props.method](props.action, form, {
    onError: (e) => Object.assign(errors, e)
  })
}

const cancel = () => {
  router.get(route('admin.companies.index'))
}
</script>




