<template>
  <AppLayout>
    <template #header>
      {{ t('exam_application') }}
    </template>

    <form class="space-y-6">
      <!-- 申請者情報 -->
      <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
        <h2 class="text-lg font-medium text-gray-900">{{ t('applicant_info') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">{{ t('name') }}</label>
            <input v-model="applicant.name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">{{ t('birthdate') }}</label>
            <input v-model="applicant.birthdate" type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">{{ t('gender') }}</label>
            <select v-model="applicant.gender" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
              <option value="male">{{ t('male') }}</option>
              <option value="female">{{ t('female') }}</option>
              <option value="other">{{ t('other') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">{{ t('occupation') }}</label>
            <input v-model="applicant.occupation" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">{{ t('workplace') }}</label>
            <input v-model="applicant.workplace" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
          </div>
          <!-- 所属部科名 -->
          <div>
            <label class="block text-sm font-medium text-gray-700">{{ t('department') }}</label>
            <input v-model="applicant.department" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
          </div>

          <!-- 役職名 -->
          <div>
            <label class="block text-sm font-medium text-gray-700">{{ t('position') }}</label>
            <input v-model="applicant.position" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
          </div>

          <!-- 勤務先住所 -->
          <div>
            <label class="block text-sm font-medium text-gray-700">{{ t('work_address') }}</label>
            <input v-model="applicant.work_address" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">{{ t('email') }}</label>
            <input v-model="applicant.email" type="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">{{ t('phone') }}</label>
            <input v-model="applicant.phone" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
          </div>
        </div>
      </div>

      <!-- 添付ファイル -->
      <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
        <h2 class="text-lg font-medium text-gray-900">{{ t('attachments') }}</h2>
        <ExamFilesUpload label="Certificate" name="certificate" />
        <ExamFilesUpload label="Recommendation 1" name="recommendation_1" />
        <ExamFilesUpload label="Recommendation 2" name="recommendation_2" />
      </div>

      <!-- 自験例フォーム x10 -->
      <div class="space-y-6">
        <CaseReportForm v-for="i in 10" :key="i" :index="i" />
      </div>

      <!-- 送信ボタン（モック） -->
      <div class="flex justify-end">
        <button type="button" @click="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
          {{ t('submit') }}
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Layouts/AppLayout.vue'
import ExamFilesUpload from './ExamFilesUpload.vue'
import CaseReportForm from './CaseReportForm.vue'

const { t } = useI18n()

const applicant = reactive({
  name: '',
  birthdate: '',
  gender: 'male',
  occupation: '',
  workplace: '',
  department: '',
  position: '',
  workplace_address: '',
  email: '',
  phone: ''
})

const submit = () => {
  alert('モック送信: 実際の保存はありません')
}
</script>










