<template>
  <div class="sm:col-span-6">
    <label :for="name" class="block text-sm font-medium text-gray-700">{{ t(label) }}</label>
    <div class="mt-1 flex items-center">
      <input
        :id="name"
        type="file"
        @change="handleFileChange"
        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4
               file:rounded-md file:border-0
               file:text-sm file:font-semibold
               file:bg-blue-50 file:text-blue-700
               hover:file:bg-blue-100"
      />
    </div>
    <p v-if="fileName" class="mt-2 text-sm text-gray-500">{{ t('selected_file') }}: {{ fileName }}</p>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  label: { type: String, required: true },
  name: { type: String, required: true }
})

const { t } = useI18n()
const fileName = ref('')

const handleFileChange = (event) => {
  const file = event.target.files[0]
  fileName.value = file ? file.name : ''
}
</script>
