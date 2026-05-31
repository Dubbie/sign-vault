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
    class="w-full rounded-xl border border-border bg-surface-input px-4 py-[0.85rem] text-heading placeholder:text-text-muted/50 focus:border-primary/50 focus:outline-hidden"
    @input="onInput"
  />
</template>
