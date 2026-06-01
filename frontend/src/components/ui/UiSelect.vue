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
    class="w-full rounded-xl bg-zinc-900 px-3 py-1 text-zinc-100 placeholder:text-zinc-400/50 focus:border-white focus:outline-hidden"
    @change="onChange"
  >
    <option v-for="opt in options" :key="opt.value" :value="opt.value">
      {{ opt.label }}
    </option>
  </select>
</template>
