<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="$emit('update:open', false)">
      <div class="bg-white rounded-xl shadow-xl w-[90vw] max-w-md flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h2 class="text-lg font-semibold">{{ t('note_edit') }}</h2>
          <Button variant="ghost" size="icon" @click="$emit('update:open', false)">
            <X class="w-4 h-4" />
          </Button>
        </div>

        <div class="px-6 py-5">
          <Label for="note">{{ t('note') }}</Label>
          <textarea
            id="note"
            v-model="noteValue"
            rows="4"
            class="w-full border rounded px-3 py-2 mt-1.5"
            placeholder="メモを入力"
          />
        </div>

        <div class="px-6 py-4 border-t flex justify-end gap-2">
          <Button variant="outline" @click="$emit('update:open', false)">{{ t('cancel') }}</Button>
          <Button @click="save">{{ t('save') }}</Button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { router } from '@inertiajs/vue3'
import { X } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'

const props = defineProps({
  open: Boolean,
  log: { type: Object, default: null },
})

const emit = defineEmits(['update:open', 'saved'])

const { t } = useI18n()
const noteValue = ref('')

watch(() => props.open, (val) => {
  if (val && props.log) {
    noteValue.value = props.log.note ?? ''
  }
})

const save = () => {
  router.put(
    route('temperatures.updateNote', props.log.id),
    { note: noteValue.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        emit('saved', noteValue.value)
        emit('update:open', false)
      },
    }
  )
}
</script>