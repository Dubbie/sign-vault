<script setup lang="ts">
withDefaults(
  defineProps<{
    modelValue: string
    type?: string
    name?: string
    required?: boolean
    placeholder?: string
    autocomplete?: string
  }>(),
  { type: 'text', required: false, placeholder: '' },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

function onInput(event: Event) {
  emit('update:modelValue', (event.target as HTMLInputElement).value)
}
</script>

<template>
  <input
    :type="type"
    :name="name"
    :value="modelValue"
    :required="required"
    :placeholder="placeholder"
    :autocomplete="autocomplete"
    class="w-full rounded-md bg-surface px-3 h-9 text-zinc-100 placeholder:text-zinc-400/50 hover:bg-surface-hover focus:bg-surface-focus focus:border-white focus:outline-hidden"
    @input="onInput"
  />
</template>
