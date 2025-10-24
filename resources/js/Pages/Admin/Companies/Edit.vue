<template>
  <AppLayout>
    <template #header>{{ t('edit_company') }}</template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <CompanyForm
          :company="company"
          method="put"
          :action="route('admin.companies.update', company.id)"
          @cancel="cancel"          
        />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import CompanyForm from '@/Components/Admin/CompanyForm.vue'
import { usePage, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { props } = usePage()
const company = props.company
const { t } = useI18n()

// キャンセル時の処理
const cancel = () => {
  router.get(route('admin.companies.index'))
}
</script>
