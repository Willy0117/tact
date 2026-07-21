<template>
  <AppLayout>
    <template #header>
      {{ operator ? t('edit_operator') : t('add_operator') }}
    </template>

    <div class="p-6 max-w-2xl mx-auto">
      <div class="bg-white border rounded-lg p-6 space-y-5">
        <div class="space-y-5">

          <!-- Code -->
          <div class="space-y-1.5">
            <Label for="code">{{ t('code') }}</Label>
            <Input
              id="code"
              v-model="form.code"
              type="text"
              placeholder="Code"
              @input="form.code = toHalfWidth(form.code)"
            />
            <p v-if="errors.code" class="text-sm text-destructive">{{ errors.code }}</p>
          </div>

          <!-- Name -->
          <div class="space-y-1.5">
            <Label for="name">{{ t('name') }}</Label>
            <Input id="name" v-model="form.name" type="text" />
            <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
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
            <Input id="display_order" v-model.number="form.display_order" type="number" />
          </div>

          <!-- Buttons -->
          <div class="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" @click="cancel">
              <X class="w-3.5 h-3.5 mr-1" />{{ t('cancel') }}
            </Button>
            <Button type="button" @click="submitForm">
              <Check class="w-3.5 h-3.5 mr-1" />{{ operator ? t('update') : t('create') }}
            </Button>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import { reactive, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { Check, X } from '@lucide/vue'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'

const props = defineProps({
  operator: { type: Object, default: null },
  tenants: { type: Array, default: () => [] },
  user: { type: Object, default: null },
  filters: { type: Object, default: () => ({}) },
})

const { t } = useI18n()

const isSuperAdmin = computed(() =>
  props.user?.roles?.some(r => r.name.toLowerCase() === 'super admin')
)

const form = reactive({
  code: props.operator?.code ?? '',
  name: props.operator?.name ?? '',
  disabled: props.operator?.disabled ?? 0,
  display_order: props.operator?.display_order ?? 1,
  tenant_id: props.operator?.tenant_id
    ? String(props.operator.tenant_id)
    : (isSuperAdmin.value ? 'all' : String(props.user?.tenant_id ?? '')),
})

const errors = reactive({
  code: '',
  name: '',
})

// リアルタイム重複チェック: code
watch(() => form.code, async (newCode) => {
  if (!newCode) { errors.code = ''; return }
  try {
    const response = await axios.post(route('operators.checkCode'), {
      code: newCode,
      id: props.operator?.id ?? null,
    })
    errors.code = response.data.exists ? t('code_already_exists') : ''
  } catch (e) {
    console.error(e)
  }
})

// 全角→半角変換
const toHalfWidth = (str) => {
  if (!str) return ''
  return str.replace(/[！-～]/g, (s) => String.fromCharCode(s.charCodeAt(0) - 0xFEE0))
            .replace(/　/g, ' ')
}

const cancel = () => {
  router.get(route('operators.index', props.filters), { preserveState: true })
}

const submitForm = () => {
  const payload = {
    ...form,
    tenant_id: form.tenant_id === 'all' ? null : form.tenant_id,
    filters: props.filters,
  }

  if (props.operator?.id) {
    router.put(route('operators.update', props.operator.id), payload, {
      onError: (err) => Object.assign(errors, err),
    })
  } else {
    router.post(route('operators.store'), payload, {
      onError: (err) => Object.assign(errors, err),
    })
  }
}
</script>