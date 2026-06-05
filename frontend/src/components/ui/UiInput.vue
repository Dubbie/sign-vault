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
    class="bg-surface-container-low border border-outline-variant/30 rounded-lg px-component-padding-x h-10 focus:outline-none focus:border-primary transition-all"
    @input="onInput"
  />
</template>
