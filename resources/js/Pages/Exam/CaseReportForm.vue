<template>
  <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
    <h3 class="text-md font-medium text-gray-900">{{ t('self_report') }} {{ index }}</h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">{{ t('facility_name') }}</label>
        <input v-model="form.facility" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">{{ t('age') }}</label>
        <input v-model="form.age" type="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">{{ t('gender') }}</label>
        <select v-model="form.gender" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
          <option value="male">{{ t('male') }}</option>
          <option value="female">{{ t('female') }}</option>
          <option value="other">{{ t('other') }}</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">{{ t('visit_type') }}</label>
        <select v-model="form.visit_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
          <option value="outpatient">{{ t('outpatient') }}</option>
          <option value="inpatient">{{ t('inpatient') }}</option>
        </select>
      </div>

      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">{{ t('diagnosis') }}</label>
        <input v-model="form.diagnosis" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
      </div>

      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">{{ t('current_history') }}</label>
        <textarea v-model="form.current_history" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
      </div>

      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">{{ t('past_history') }}</label>
        <textarea v-model="form.past_history" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
      </div>

      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">{{ t('rehab_program') }}</label>
        <textarea v-model="form.rehab" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
      </div>

      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">{{ t('future_plan') }}</label>
        <textarea v-model="form.future_plan" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  index: { type: Number, required: true }
})

const { t } = useI18n()

const form = reactive({
  facility: '',
  age: '',
  gender: 'male',
  visit_type: 'outpatient',
  diagnosis: '',
  current_history: '',
  past_history: '',
  rehab: '',
  future_plan: ''
})
</script>



