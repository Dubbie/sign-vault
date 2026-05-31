<script setup lang="ts">
withDefaults(
  defineProps<{
    modelValue: string
    name?: string
    options: { value: string; label: string }[]
  }>(),
  {},
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

function onChange(event: Event) {
  emit('update:modelValue', (event.target as HTMLSelectElement).value)
}
</script>

<template>
  <select
    :name="name"
    :value="modelValue"
    class="w-full rounded-xl border border-border bg-surface-input px-4 py-[0.85rem] text-heading focus:border-primary/50 focus:outline-hidden"
    @change="onChange"
  >
    <option v-for="opt in options" :key="opt.value" :value="opt.value">
      {{ opt.label }}
    </option>
  </select>
</template>
