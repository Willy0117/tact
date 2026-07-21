<template>
  <AppLayout>
    <template #header>
      <span v-if="isEdit">{{ t('processes.edit') }}</span>
      <span v-else-if="mode === 'copy'">{{ t('processes.copy') }}</span>
      <span v-else>{{ t('processes.add') }}</span>
    </template>

    <div class="p-6 max-w-2xl mx-auto">
      <div class="bg-white border rounded-lg p-6 space-y-5">
        <form @submit.prevent="submitForm" class="space-y-5">

          <!-- Name -->
          <div class="space-y-1.5">
            <Label for="name">{{ t('name') }}</Label>
            <Input id="name" v-model="form.name" type="text" autofocus />
            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
          </div>

          <!-- 判定条件 -->
          <div class="space-y-1.5">
            <Label>{{ t('processes.threshold_type_label') ?? '判定条件' }}</Label>
            <RadioGroup v-model="form.threshold_type" class="flex items-center gap-6">
              <label class="flex items-center gap-2 cursor-pointer">
                <RadioGroupItem value="upper" />
                <span>{{ t('processes.threshold_type.upper') }}</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <RadioGroupItem value="lower" />
                <span>{{ t('processes.threshold_type.lower') }}</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <RadioGroupItem value="none" />
                <span>{{ t('processes.threshold_type.none') }}</span>
              </label>
            </RadioGroup>
          </div>

          <!-- 閾値 -->
          <div v-if="form.threshold_type !== 'none'" class="space-y-1.5">
            <Label for="threshold_value">{{ t('processes.threshold_value') }}</Label>
            <Input
              id="threshold_value"
              v-model="form.threshold_value"
              type="text"
              placeholder="例: 76.0"
              @blur="normalizeThreshold"
            />
            <p v-if="form.errors.threshold_value" class="text-sm text-destructive">{{ form.errors.threshold_value }}</p>
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

          <!-- Display Order -->
          <div class="space-y-1.5">
            <Label for="display_order">{{ t('display_order') }}</Label>
            <Input
              id="display_order"
              v-model="form.display_order"
              type="text"
              @blur="normalizeDisplayOrder"
            />
            <p v-if="form.errors.display_order" class="text-sm text-destructive">{{ form.errors.display_order }}</p>
          </div>

          <!-- Disabled -->
          <div class="space-y-1.5">
            <Label>{{ t('status') }}</Label>
            <RadioGroup v-model="form.disabled" class="flex items-center gap-6">
              <label class="flex items-center gap-2 cursor-pointer">
                <RadioGroupItem :value="0" />
                <span>{{ t('enable') }}</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <RadioGroupItem :value="1" />
                <span>{{ t('disable') }}</span>
              </label>
            </RadioGroup>
          </div>

          <!-- Buttons -->
          <div class="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" @click="cancel">
              <X class="w-3.5 h-3.5 mr-1" />{{ t('cancel') }}
            </Button>
            <Button type="submit">
              <Check class="w-3.5 h-3.5 mr-1" />{{ isEdit ? t('update') : t('save') }}
            </Button>
          </div>

        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm } from '@inertiajs/vue3'
import { watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Check, X } from '@lucide/vue'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'

const props = defineProps({
  process: { type: Object, default: null },
  tenants: { type: Array, default: () => [] },
  user: { type: Object, default: null },
  filters: { type: Object, default: () => ({}) },
  mode: { type: String, default: '' },
})

const { t } = useI18n({ useScope: 'global' })

const isSuperAdmin = computed(() =>
  props.user?.roles?.some(r => r.name.toLowerCase() === 'super admin')
)

const isEdit = computed(() => !!props.process?.id)

const form = useForm({
  name: props.process?.name ?? '',
  display_order: props.process?.display_order ?? 0,
  threshold_value: props.process?.threshold_value ?? '',
  threshold_type: props.process?.threshold_type ?? 'lower',
  tenant_id: props.process?.tenant_id
    ? String(props.process.tenant_id)
    : (isSuperAdmin.value ? 'all' : String(props.user?.tenant_id ?? '')),
  disabled: props.process?.disabled ?? 0,
})

watch(
  () => form.threshold_type,
  (type) => {
    if (type === 'none') {
      form.threshold_value = null
    }
  }
)

const normalizeThreshold = () => {
  form.threshold_value = String(form.threshold_value ?? '').replace(/[^\d.-]/g, '')
}

const normalizeDisplayOrder = () => {
  form.display_order = String(form.display_order ?? '').replace(/[^\d-]/g, '')
}

const cancel = () => {
  router.get(route('processes.index', props.filters), { preserveState: true })
}

const submitForm = () => {
  const payload = {
    ...form.data(),
    tenant_id: form.tenant_id === 'all' ? null : form.tenant_id,
    filters: props.filters,
  }

  if (isEdit.value) {
    router.put(route('processes.update', props.process.id), payload, {
      onError: (err) => form.setError(err),
    })
  } else {
    router.post(route('processes.store'), payload, {
      onError: (err) => form.setError(err),
    })
  }
}
</script>