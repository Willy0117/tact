<template>
  <AppLayout>
    <template #header>
      {{ permission ? t('edit_permission') : t('create_permission') }}
    </template>

    <div class="p-6 max-w-2xl mx-auto">
      <div class="bg-white border rounded-lg p-6 space-y-5">
        <form @submit.prevent="submitForm" class="space-y-5">
          <!-- Permission Name -->
          <div class="space-y-1.5">
            <Label for="name">{{ t('name') }}</Label>
            <Input
              id="name"
              v-model="form.name"
              type="text"
              placeholder="Permission Name"
              autofocus
            />
            <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
          </div>

          <!-- Tenant 選択 (Super Admin のみ) -->
          <div v-if="isSuperAdmin" class="space-y-1.5">
            <Label for="tenant_id">{{ t('tenant') }}</Label>
            <Select v-model="form.tenant_id">
              <SelectTrigger id="tenant_id">
                <SelectValue :placeholder="t('select_tenant')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                  {{ tenant.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="errors.tenant_id" class="text-sm text-destructive">{{ errors.tenant_id }}</p>
          </div>

          <!-- Buttons -->
          <div class="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" @click="cancel">
              <X class="w-3.5 h-3.5 mr-1" />{{ t('cancel') }}
            </Button>
            <Button type="submit">
              <Check class="w-3.5 h-3.5 mr-1" />{{ permission ? t('update') : t('create') }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import { reactive, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Check, X } from '@lucide/vue'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

const props = defineProps({
  permission: Object,  // null = 新規作成, オブジェクト = 編集
  tenants: Array,      // Super Admin のみ
  user: Object,        // 現在のログインユーザー
  filters: Object      // Index画面の検索条件
})

const { t } = useI18n()

const isSuperAdmin = computed(() =>
  props.user?.roles?.some(r => r.name.toLowerCase() === 'super admin')
)

const form = reactive({
  name: props.permission ? props.permission.name : '',
  tenant_id: props.permission
    ? props.permission.tenant_id
    : (isSuperAdmin.value ? null : props.user?.tenant_id ?? null)
})

const errors = reactive({
  name: ''
})

const cancel = () => {
  router.get(route('permissions.index', props.filters), { preserveState: true })
}

const submitForm = () => {
  if (props.permission) {
    router.put(route('permissions.update', props.permission.id), form, {
      preserveState: true,
      onError: (err) => Object.assign(errors, err),
      onSuccess: () => router.get(route('permissions.index', props.filters))
    })
  } else {
    router.post(route('permissions.store'), form, {
      preserveState: true,
      onError: (err) => Object.assign(errors, err),
      onSuccess: () => router.get(route('permissions.index', props.filters))
    })
  }
}
</script>