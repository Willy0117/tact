<template>
  <AppLayout>
    <template #header>
      {{ user ? t('edit_user') : t('create_user') }}
    </template>

    <div class="p-6 max-w-2xl mx-auto">
      <div class="bg-white border rounded-lg p-6 space-y-5">
        <div class="space-y-5">

          <!-- 名前 -->
          <div class="space-y-1.5">
            <Label for="name">{{ t('name') }}</Label>
            <Input id="name" v-model="form.name" type="text" autofocus />
            <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
          </div>

          <!-- メール -->
          <div class="space-y-1.5">
            <Label for="email">{{ t('email') }}</Label>
            <Input id="email" v-model="form.email" type="email" />
            <p v-if="errors.email" class="text-sm text-destructive">{{ errors.email }}</p>
          </div>

          <!-- パスワード -->
          <div class="space-y-1.5">
            <Label for="password">{{ t('password') }}</Label>
            <Input id="password" v-model="form.password" type="password" />
            <p v-if="errors.password" class="text-sm text-destructive">{{ errors.password }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="password_confirmation">{{ t('confirm password') }}</Label>
            <Input id="password_confirmation" v-model="form.password_confirmation" type="password" />
          </div>

          <!-- Tenant（SuperAdminのみ） -->
          <div v-if="isSuperAdmin" class="space-y-1.5">
            <Label for="tenant_id">{{ t('tenant') }}</Label>
            <Select v-model="form.tenant_id">
              <SelectTrigger id="tenant_id">
                <SelectValue :placeholder="t('select_tenant')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">
                  {{ tenant.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="errors.tenant_id" class="text-sm text-destructive">{{ errors.tenant_id }}</p>
          </div>

          <!-- Role選択 -->
          <div class="space-y-1.5">
            <Label for="role_id">{{ t('role') }}</Label>
            <Select v-model="form.role_id">
              <SelectTrigger id="role_id">
                <SelectValue :placeholder="t('select_role')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="role in roles" :key="role.id" :value="String(role.id)">
                  {{ role.name }} - {{ role.tenant_name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="errors.role_id" class="text-sm text-destructive">{{ errors.role_id }}</p>
          </div>

          <!-- 保存ボタン -->
          <div class="flex justify-between items-center pt-2">
            <Button type="button" variant="outline" @click="cancel">
              <X class="w-3.5 h-3.5 mr-1" />{{ t('cancel') }}
            </Button>
            <Button type="button" @click="submit">
              <Check class="w-3.5 h-3.5 mr-1" />{{ user ? t('update') : t('create') }}
            </Button>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Check, X } from '@lucide/vue'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

const { t } = useI18n()

const props = defineProps({
  auth: { type: Object, default: () => ({}) },
  user: { type: Object, default: null },
  roles: { type: Array, default: () => [] },
  tenants: { type: Array, default: () => [] },
  selected_role: { type: Number, default: null },
  filters: { type: Object, default: () => ({}) },
})

const isSuperAdmin = computed(() => {
  const roles = props.auth.user?.roles || []
  return roles.some(role => role.name.toLowerCase() === 'super admin')
})

const persistQuery = () => ({
  name: props.filters.name ?? '',
  email: props.filters.email ?? '',
  tenant_id: props.filters.tenant_id ?? '',
  per_page: props.filters.per_page ?? 20,
  sort_by: props.filters.sort_by ?? 'id',
  sort_dir: props.filters.sort_dir ?? 'asc',
  page: props.filters.page ?? 1,
})

const form = useForm({
  name: props.user?.name || '',
  email: props.user?.email || '',
  password: '',
  password_confirmation: '',
  role_id: props.selected_role ? String(props.selected_role) : null,
  tenant_id: props.user?.tenant_id ? String(props.user.tenant_id) : null,
})

const errors = form.errors

const cancel = () => {
  router.get(route('users.index', persistQuery()))
}

const submit = () => {
  const method = props.user?.id ? 'put' : 'post'
  const url = props.user?.id ? route('users.update', props.user.id) : route('users.store')

  form.transform((data) => ({
    ...data,
    filters: props.filters,
  })).submit(method, url, {
    onError: (e) => console.log(e)
  })
}
</script>