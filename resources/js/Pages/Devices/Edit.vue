<template>
  <AppLayout>
    <template #header>
      {{ device ? t('edit_device') : t('add_device') }}
    </template>

    <div class="p-6 max-w-2xl mx-auto">
      <div class="bg-white border rounded-lg p-6 space-y-5">
        <div class="space-y-5">

          <!-- Name -->
          <div class="space-y-1.5">
            <Label for="name">{{ t('name') }}</Label>
            <Input id="name" v-model="form.name" type="text" autofocus />
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
                <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">
                  {{ tenant.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Process -->
          <div class="space-y-1.5">
            <Label for="process_id">{{ t('process') }}</Label>
            <Select v-model="form.process_id">
              <SelectTrigger id="process_id">
                <SelectValue :placeholder="t('please_select')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="p in processes" :key="p.id" :value="String(p.id)">
                  {{ p?.name ?? '' }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Measurement -->
          <div class="space-y-1.5">
            <Label>{{ t('measurement') }}</Label>
            <RadioGroup v-model="form.measurement" class="flex items-center gap-6">
              <label class="flex items-center gap-2 cursor-pointer">
                <RadioGroupItem :value="1" />
                <span>{{ t('do') }}</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <RadioGroupItem :value="0" />
                <span>{{ t('dont') }}</span>
              </label>
            </RadioGroup>
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
              <Check class="w-3.5 h-3.5 mr-1" />{{ device ? t('update') : t('create') }}
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
import { reactive, ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { Check, X } from '@lucide/vue'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  tenants: { type: Array, default: () => [] },
  device: { type: Object, default: null },
  user: { type: Object, default: null },
  processes: { type: Array, default: () => [] },
})

const { t } = useI18n()

const isSuperAdmin = computed(() =>
  props.user?.roles?.some(r => r.name.toLowerCase() === 'super admin')
)

const processesList = ref([...props.processes])

const form = reactive({
  name: props.device?.name ?? '',
  process_id: props.device?.process_id ? String(props.device.process_id) : '',
  measurement: props.device?.measurement ?? 1,
  disabled: props.device?.disabled ?? 0,
  display_order: props.device?.display_order ?? 1,
  tenant_id: props.device?.tenant_id
    ? String(props.device.tenant_id)
    : (isSuperAdmin.value ? '' : String(props.user?.tenant_id ?? '')),
})

const errors = reactive({ name: '' })

let initialLoad = true

// テナント変更時に工程リストを動的に取得
watch(
  () => form.tenant_id,
  async (tenantId) => {
    if (!tenantId) {
      processesList.value = []
      return
    }

    // 初期ロード時、元々渡されたテナントと同じなら再取得しない
    if (initialLoad && props.device?.tenant_id && String(props.device.tenant_id) === tenantId) {
      processesList.value = [...props.processes]
      initialLoad = false
      return
    }

    const res = await axios.get(route('processes.byTenant'), {
      params: { tenant: tenantId },
    })
    processesList.value = res.data

    if (!initialLoad) {
      form.process_id = ''
    } else {
      initialLoad = false
    }
  },
  { immediate: true }
)

const cancel = () => {
  router.get(route('devices.index', props.filters), { preserveState: true })
}

const submitForm = () => {
  const payload = {
    ...form,
    process_id: form.process_id || null,
    filters: props.filters,
  }

  if (props.device?.id) {
    router.put(route('devices.update', props.device.id), payload, {
      onError: (err) => Object.assign(errors, err),
    })
  } else {
    router.post(route('devices.store'), payload, {
      onError: (err) => Object.assign(errors, err),
    })
  }
}
</script>