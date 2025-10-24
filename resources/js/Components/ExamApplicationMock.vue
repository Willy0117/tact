<template>
  <div class="max-w-4xl mx-auto p-6 bg-white shadow rounded-lg space-y-8">
    <h1 class="text-2xl font-bold">{{ t('exam_application') }}</h1>

    <!-- 個人情報 -->
    <section class="space-y-4">
      <h2 class="text-xl font-semibold">{{ t('personal_info') }}</h2>

      <div>
        <label class="block font-medium text-gray-700">{{ t('name') }}</label>
        <input type="text" placeholder="山田 太郎" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" disabled />
      </div>

      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="block font-medium text-gray-700">{{ t('birth_year') }}</label>
          <input type="number" placeholder="1980" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" disabled />
        </div>
        <div>
          <label class="block font-medium text-gray-700">{{ t('birth_month') }}</label>
          <input type="number" placeholder="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" disabled />
        </div>
        <div>
          <label class="block font-medium text-gray-700">{{ t('birth_day') }}</label>
          <input type="number" placeholder="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" disabled />
        </div>
      </div>

      <div>
        <label class="block font-medium text-gray-700">{{ t('gender') }}</label>
        <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" disabled>
          <option value="male">{{ t('male') }}</option>
          <option value="female">{{ t('female') }}</option>
          <option value="other">{{ t('other') }}</option>
        </select>
      </div>

      <div>
        <label class="block font-medium text-gray-700">{{ t('job') }}</label>
        <input type="text" placeholder="医師" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" disabled />
      </div>

      <div>
        <label class="block font-medium text-gray-700">{{ t('workplace') }}</label>
        <input type="text" placeholder="東京病院" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" disabled />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block font-medium text-gray-700">{{ t('department') }}</label>
          <input type="text" placeholder="腎臓科" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" disabled />
        </div>
        <div>
          <label class="block font-medium text-gray-700">{{ t('position') }}</label>
          <input type="text" placeholder="部長" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" disabled />
        </div>
      </div>

      <div>
        <label class="block font-medium text-gray-700">{{ t('email') }}</label>
        <input type="email" placeholder="taro@example.com" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" disabled />
      </div>

      <div>
        <label class="block font-medium text-gray-700">{{ t('phone') }}</label>
        <input type="text" placeholder="090-1234-5678" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" disabled />
      </div>
    </section>

    <!-- 添付ファイル -->
    <section class="space-y-4">
      <h2 class="text-xl font-semibold">{{ t('attachments') }}</h2>

      <div v-for="file in files" :key="file.key" class="space-y-1">
        <label class="block font-medium text-gray-700">{{ t(file.label) }}</label>
        <input type="file" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" disabled />
        <p class="text-gray-500 text-sm">{{ t('mock_upload_note') }}</p>
      </div>
    </section>

    <div>
      <button class="bg-blue-600 text-white px-4 py-2 rounded cursor-not-allowed" disabled>
        {{ t('submit') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const files = [
  { key: 'certificate', label: 'certificate' },
  { key: 'recommendation_3_1', label: 'recommendation_3_1' },
  { key: 'recommendation_3_2', label: 'recommendation_3_2' },
  { key: 'self_report_1', label: 'self_report_1' },
  { key: 'self_report_2', label: 'self_report_2' },
]
</script>
