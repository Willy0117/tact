<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="close">
      <div class="bg-white rounded-xl shadow-xl w-[90vw] max-w-2xl max-h-[90vh] flex flex-col">

        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h2 class="text-lg font-semibold">{{ t('temperature_edit') }}</h2>
          <Button variant="ghost" size="icon" @click="close">
            <X class="w-4 h-4" />
          </Button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
          <div v-if="form" class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label>{{ t('dish_name') }}</Label>
              <Autocomplete
                v-model="form.menu_id"
                api-url="/menus/autocomplete"
                :placeholder="t('select.menu')"
                :initial-item="log?.menu ? { id: log.menu.id, name: log.menu.name } : null"
              />
              <p v-if="errors.menu_id" class="text-sm text-destructive">{{ errors.menu_id }}</p>
            </div>

            <div class="space-y-1.5">
              <Label>{{ t('process') }}</Label>
              <Autocomplete
                v-model="form.process_id"
                api-url="/processes/autocomplete"
                :placeholder="t('select.process')"
                :initial-item="log?.process ? { id: log.process.id, name: log.process.name } : null"
              />
              <p v-if="errors.process_id" class="text-sm text-destructive">{{ errors.process_id }}</p>
            </div>

            <div class="space-y-1.5">
              <Label>{{ t('sensor') }}</Label>
              <Autocomplete
                v-model="form.sensor_id"
                api-url="/sensors/autocomplete"
                :placeholder="t('select.sensor')"
                :initial-item="log?.sensor ? { id: log.sensor.id, name: log.sensor.name } : null"
              />
              <p v-if="errors.sensor_id" class="text-sm text-destructive">{{ errors.sensor_id }}</p>
            </div>

            <div class="space-y-1.5">
              <Label>{{ t('device') }}</Label>
              <Autocomplete
                v-model="form.device_id"
                api-url="/devices/autocomplete"
                :placeholder="t('select.device')"
                :initial-item="log?.device ? { id: log.device.id, name: log.device.name } : null"
              />
              <p v-if="errors.device_id" class="text-sm text-destructive">{{ errors.device_id }}</p>
            </div>

            <div class="space-y-1.5">
              <Label>{{ t('operator') }}</Label>
              <Autocomplete
                v-model="form.operator_id"
                api-url="/operators/autocomplete"
                :placeholder="t('select.operator')"
                :initial-item="log?.operator ? { id: log.operator.id, name: log.operator.name } : null"
              />
              <p v-if="errors.operator_id" class="text-sm text-destructive">{{ errors.operator_id }}</p>
            </div>
          </div>

          <div v-if="form" class="space-y-2">
            <Label>{{ t('temperatures') }}</Label>
            <div
              v-for="(temp, index) in form.temperatures"
              :key="index"
              class="flex items-center gap-2"
            >
              <Input type="datetime-local" v-model="form.temperatures[index].datetime" class="w-auto" />
              <Input type="number" step="0.1" v-model="form.temperatures[index].value" class="w-24" />
              <Button variant="ghost" size="icon" class="h-8 w-8 text-destructive" @click="removeRow(index)">
                <Trash2 class="w-4 h-4" />
              </Button>
            </div>
            <Button variant="outline" size="sm" @click="addRow">
              <Plus class="w-3.5 h-3.5 mr-1" />{{ t('add') }}
            </Button>
          </div>

          <div v-if="form" class="space-y-1.5">
            <Label>{{ t('note') }}</Label>
            <textarea v-model="form.note" rows="3" class="w-full border rounded px-3 py-2" />
          </div>
        </div>

        <div class="px-6 py-4 border-t flex justify-end gap-2">
          <Button variant="outline" @click="close">{{ t('cancel') }}</Button>
          <Button :disabled="form?.processing" @click="submit">{{ t('update') }}</Button>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { X, Trash2, Plus } from '@lucide/vue'

import Autocomplete from '@/Components/Autocomplete.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const props = defineProps({
  open: Boolean,
  log: { type: Object, default: null },
})

const emit = defineEmits(['update:open', 'saved'])

const { t } = useI18n()
const errors = reactive({})
const form = ref(null)

watch(() => props.open, (val) => {
  if (val && props.log) {
    Object.keys(errors).forEach(k => delete errors[k])
    form.value = useForm({
      menu_id: props.log.menu_id,
      device_id: props.log.device_id,
      operator_id: props.log.operator_id,
      sensor_id: props.log.sensor_id ?? null,
      process_id: props.log.process_id,
      temperatures: props.log.temperatures
        ? JSON.parse(JSON.stringify(props.log.temperatures))
        : [],
      note: props.log.note,
    })
  }
})

const close = () => {
  emit('update:open', false)
}

const submit = () => {
  form.value.put(route('temperatures.update', props.log.id), {
    preserveState: true,
    preserveScroll: true,
    onError: (err) => Object.assign(errors, err),
    onSuccess: () => {
      emit('saved')
      emit('update:open', false)
      router.reload({ preserveScroll: true })
    },
  })
}

const addRow = () => {
  form.value.temperatures.push({ datetime: '', value: '' })
}

const removeRow = (index) => {
  form.value.temperatures.splice(index, 1)
}
</script>