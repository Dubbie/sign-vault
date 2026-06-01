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
    class="w-full rounded-xl bg-zinc-900 px-3 py-1 text-zinc-100 placeholder:text-zinc-400/50 focus:border-white focus:outline-hidden"
    @input="onInput"
  />
</template>
