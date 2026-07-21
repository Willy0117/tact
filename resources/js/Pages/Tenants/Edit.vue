<template>
  <AppLayout>
    <template #header>
      {{ tenant ? t('edit_tenant') : t('add_tenant') }}
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

          <!-- Contact Email -->
          <div class="space-y-1.5">
            <Label for="contact_email">{{ t('contact_email') }}</Label>
            <Input id="contact_email" v-model="form.contact_email" type="email" />
            <p v-if="errors.contact_email" class="text-sm text-destructive">{{ errors.contact_email }}</p>
          </div>

          <!-- Contact Phone -->
          <div class="space-y-1.5">
            <Label for="contact_phone">{{ t('contact_phone') }}</Label>
            <Input id="contact_phone" v-model="form.contact_phone" type="text" />
            <p v-if="errors.contact_phone" class="text-sm text-destructive">{{ errors.contact_phone }}</p>
          </div>

          <!-- Address -->
          <div class="space-y-1.5">
            <Label for="address">{{ t('address') }}</Label>
            <Input id="address" v-model="form.address" type="text" />
            <p v-if="errors.address" class="text-sm text-destructive">{{ errors.address }}</p>
          </div>

          <!-- Buttons -->
          <div class="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" @click="cancel">
              <X class="w-3.5 h-3.5 mr-1" />{{ t('cancel') }}
            </Button>
            <Button type="submit">
              <Check class="w-3.5 h-3.5 mr-1" />{{ tenant ? t('update') : t('create') }}
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
import { reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import { Check, X } from '@lucide/vue'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const props = defineProps({
  tenant: { type: Object, default: null },
  filters: { type: Object, default: () => ({}) }
})

const { t } = useI18n()

const form = reactive({
  name: props.tenant?.name ?? '',
  contact_email: props.tenant?.contact_email ?? '',
  contact_phone: props.tenant?.contact_phone ?? '',
  address: props.tenant?.address ?? ''
})

const errors = reactive({
  name: '',
  contact_email: '',
  contact_phone: '',
  address: ''
})

const cancel = () => {
  router.get(route('tenants.index', props.filters), { preserveState: true })
}

const submitForm = () => {
  const payload = {
    ...form,
    filters: props.filters,
  }

  if (props.tenant) {
    router.put(route('tenants.update', props.tenant.id), payload, {
      onError: (err) => Object.assign(errors, err),
    })
  } else {
    router.post(route('tenants.store'), payload, {
      onError: (err) => Object.assign(errors, err),
    })
  }
}
</script>