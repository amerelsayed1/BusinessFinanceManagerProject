<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 px-4"
    @click.self="handleClose"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl">
      <div class="flex items-start justify-between px-4 py-3 border-b">
        <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
        <button type="button" class="text-gray-500 hover:text-gray-700" @click="handleClose">&times;</button>
      </div>
      <div class="p-4 overflow-y-auto max-h-[80vh]">
        <slot />
      </div>
      <div v-if="$slots.footer" class="px-4 py-3 border-t bg-gray-50 flex justify-end gap-2">
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue', 'close'])

const handleClose = () => {
  emit('update:modelValue', false)
  emit('close')
}
</script>
