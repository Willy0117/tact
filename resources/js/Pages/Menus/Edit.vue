<template>
  <AppLayout>
    <template #header>
      {{ menu ? t('edit_menu') : t('add_menu') }}
    </template>

    <div class="p-6 max-w-2xl mx-auto">
      <div class="bg-white border rounded-lg p-6 space-y-5">
        <div class="space-y-5">

          <!-- 料理名 -->
          <div class="space-y-1.5">
            <Label for="name">{{ t('dish_name') }}</Label>
            <textarea id="name" v-model="form.name" class="w-full border rounded px-3 py-2" rows="2" />
            <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- 配膳日 -->
            <div class="space-y-1.5">
              <Label for="serving_date">{{ t('serving_date') }}</Label>
              <Input id="serving_date" v-model="form.serving_date" type="date" />
            </div>

            <!-- 配膳時間 -->
            <div class="space-y-1.5">
              <Label for="serving_time">{{ t('serving_time') }}</Label>
              <Input id="serving_time" v-model="form.serving_time" type="time" />
            </div>

            <!-- 調理日 -->
            <div class="space-y-1.5">
              <Label for="cooking_date">{{ t('cooking_date') }}</Label>
              <Input id="cooking_date" v-model="form.cooking_date" type="date" />
            </div>
          </div>

          <!-- 材料 -->
          <div class="space-y-1.5">
            <Label for="materials">{{ t('materials') }}</Label>
            <textarea id="materials" v-model="form.materials" class="w-full border rounded px-3 py-2" rows="3" />
            <p v-if="errors.materials" class="text-sm text-destructive">{{ errors.materials }}</p>
          </div>

          <!-- Tenant 選択 (Super Admin のみ) -->
          <div v-if="isSuperAdmin" class="space-y-1.5">
            <Label for="tenant_id">{{ t('tenant') }}</Label>
            <Select v-model="form.tenant_id">
              <SelectTrigger id="tenant_id">
                <SelectValue :placeholder="t('select_tenant')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">{{ t('select_tenant') }}</SelectItem>
                <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">
                  {{ tenant.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- ボタン -->
          <div class="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" @click="cancel">
              <X class="w-3.5 h-3.5 mr-1" />{{ t('cancel') }}
            </Button>
            <Button type="button" @click="submit">
              <Check class="w-3.5 h-3.5 mr-1" />{{ menu ? t('update') : t('save') }}
            </Button>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { reactive, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { router } from '@inertiajs/vue3'
import { Check, X } from '@lucide/vue'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

const { t } = useI18n()

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  tenants: { type: Array, default: () => [] },
  user: { type: Object, default: null },
  menu: { type: Object, default: null },
  redirect_to: { type: String, default: '' },
})

const isSuperAdmin = computed(() =>
  props.user?.roles?.some(r => r.name.toLowerCase() === 'super admin')
)

const form = reactive({
  name: props.menu?.name ?? '',
  serving_date: props.menu?.serving_date ?? '',
  serving_time: props.menu?.serving_time ?? '',
  cooking_date: props.menu?.cooking_date ?? '',
  materials: props.menu?.materials ?? '',
  tenant_id: props.menu?.tenant_id
    ? String(props.menu.tenant_id)
    : (isSuperAdmin.value ? 'all' : String(props.user?.tenant_id ?? '')),
  redirect_to: props.redirect_to,
})

const errors = reactive({
  name: '', serving_date: '', serving_time: '', cooking_date: '', materials: ''
})

const cancel = () => {
  router.get(form.redirect_to || route('menus.index', props.filters))
}

const submit = () => {
  const payload = {
    ...form,
    tenant_id: form.tenant_id === 'all' ? null : form.tenant_id,
    filters: props.filters,
  }

  if (props.menu?.id) {
    router.put(route('menus.update', { menu: props.menu.id }), payload, {
      onError: (errs) => Object.assign(errors, errs),
    })
  } else {
    router.post(route('menus.store'), payload, {
      onError: (errs) => Object.assign(errors, errs),
    })
  }
}
</script>