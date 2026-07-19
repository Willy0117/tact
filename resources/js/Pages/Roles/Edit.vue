<template>
  <AppLayout>
    <template #header>
      {{ role ? t('edit_role') : t('create_role') }}
    </template>

    <div class="p-6 max-w-3xl mx-auto">
      <div class="bg-white border rounded-lg p-6 space-y-5">
        <form @submit.prevent="submitForm" class="space-y-5">

          <!-- Role Name -->
          <div class="space-y-1.5">
            <Label for="name">{{ t('role_name') }}</Label>
            <Input id="name" v-model="form.name" type="text" placeholder="Role Name" autofocus />
            <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
          </div>

          <!-- Permissions MultiSelect -->
          <div class="space-y-1.5">
            <Label>{{ t('permissions') }}</Label>
            <div class="border rounded-lg overflow-hidden">
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 max-h-96 overflow-y-auto p-3">
                <label
                  v-for="permission in permissions"
                  :key="permission.id"
                  :for="'perm-' + permission.id"
                  class="flex items-center gap-2 cursor-pointer"
                >
                  <Checkbox
                    :id="'perm-' + permission.id"
                    :model-value="form.permissions.includes(permission.id)"
                    @update:model-value="(checked) => togglePermission(permission.id, checked)"
                  />
                  <span class="text-sm">
                    {{ permission.name }} {{ permission.tenant_label }}
                  </span>
                </label>
              </div>
            </div>
            <p v-if="errors.permissions" class="text-sm text-destructive">{{ errors.permissions }}</p>
          </div>

          <!-- Buttons -->
          <div class="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" @click="cancel">
              <X class="w-3.5 h-3.5 mr-1" />{{ t('cancel') }}
            </Button>
            <Button type="submit">
              <Check class="w-3.5 h-3.5 mr-1" />{{ role ? t('update') : t('create') }}
            </Button>
          </div>

        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useI18n } from 'vue-i18n'
import { Check, X } from '@lucide/vue'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'

const props = defineProps({
  role: { type: Object, default: null },
  permissions: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

const { t } = useI18n()

const form = reactive({
  name: props.role?.name || '',
  permissions: props.role?.permissions?.map(p => p.id) || [],
})

const errors = reactive({})

const togglePermission = (id, checked) => {
  if (checked) {
    if (!form.permissions.includes(id)) form.permissions.push(id)
  } else {
    form.permissions = form.permissions.filter(i => i !== id)
  }
}

const cancel = () => {
  router.get(route('roles.index', props.filters), { preserveState: true })
}

const submitForm = () => {
  const method = props.role ? 'put' : 'post'
  const url = props.role
    ? route('roles.update', props.role.id)
    : route('roles.store')

  router[method](url, form, {
    preserveState: true,
    onError: err => Object.assign(errors, err),
    onSuccess: () => router.get(route('roles.index', props.filters)),
  })
}
</script>