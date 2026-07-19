<template>
  <AppLayout>
    <template #header>
      {{ sensor ? t('edit_sensor') : t('add_sensor') }}
    </template>

    <div class="p-6 max-w-2xl mx-auto">
      <div class="bg-white border rounded-lg p-6 space-y-5">
        <form @submit.prevent="submitForm" class="space-y-5">

          <!-- Name -->
          <div class="space-y-1.5">
            <Label for="name">{{ t('name') }}</Label>
            <Input id="name" v-model="form.name" type="text" autofocus />
            <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
          </div>

          <!-- Model -->
          <div class="space-y-1.5">
            <Label for="model">{{ t('model') }}</Label>
            <Input
              id="model"
              v-model="form.model"
              type="text"
              placeholder="Model"
              @input="form.model = toHalfWidth(form.model)"
            />
            <p v-if="errors.model" class="text-sm text-destructive">{{ errors.model }}</p>
          </div>

          <!-- Serial Number -->
          <div class="space-y-1.5">
            <Label for="serial_number">{{ t('serial_number') }}</Label>
            <Input
              id="serial_number"
              v-model="form.serial_number"
              type="text"
              placeholder="Serial Number"
              @input="form.serial_number = toHalfWidth(form.serial_number)"
            />
            <p v-if="errors.serial_number" class="text-sm text-destructive">{{ errors.serial_number }}</p>
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
            <Button type="submit">
              <Check class="w-3.5 h-3.5 mr-1" />{{ sensor ? t('update') : t('create') }}
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
  sensor: { type: Object, default: null },
  tenants: { type: Array, default: () => [] },
  user: { type: Object, default: null },
  filters: { type: Object, default: () => ({}) },
})

const { t } = useI18n()

const isSuperAdmin = computed(() =>
  props.user?.roles?.some(r => r.name.toLowerCase() === 'super admin')
)

const form = reactive({
  name: props.sensor?.name ?? '',
  model: props.sensor?.model ?? '',
  serial_number: props.sensor?.serial_number ?? '',
  disabled: props.sensor?.disabled ?? 0,
  display_order: props.sensor?.display_order ?? 1,
  tenant_id: props.sensor?.tenant_id
    ? String(props.sensor.tenant_id)
    : (isSuperAdmin.value ? 'all' : String(props.user?.tenant_id ?? '')),
})

const errors = reactive({
  name: '',
  model: '',
  serial_number: '',
})

// リアルタイム重複チェック: serial_number + model の組み合わせ
watch(
  [() => form.serial_number, () => form.model],
  async ([newSerial, newModel]) => {
    if (!newSerial) { errors.serial_number = ''; return }
    try {
      const response = await axios.post(route('sensors.checkSerialNumber'), {
        serial_number: newSerial,
        model: newModel,
        id: props.sensor?.id ?? null,
      })
      errors.serial_number = response.data.exists ? t('serial_number_already_exists') : ''
    } catch (e) {
      console.error(e)
    }
  }
)

// 全角→半角変換
const toHalfWidth = (str) => {
  if (!str) return ''
  return str.replace(/[！-～]/g, (s) => String.fromCharCode(s.charCodeAt(0) - 0xFEE0))
            .replace(/　/g, ' ')
}

const cancel = () => {
  router.get(route('sensors.index', props.filters), { preserveState: true })
}

const submitForm = () => {
  const payload = {
    ...form,
    tenant_id: form.tenant_id === 'all' ? null : form.tenant_id,
  }

  if (props.sensor) {
    router.put(route('sensors.update', props.sensor.id), payload, {
      preserveState: true,
      onError: (err) => Object.assign(errors, err),
      onSuccess: () => router.get(route('sensors.index', props.filters)),
    })
  } else {
    router.post(route('sensors.store'), payload, {
      preserveState: true,
      onError: (err) => Object.assign(errors, err),
      onSuccess: () => router.get(route('sensors.index', props.filters)),
    })
  }
}
</script>