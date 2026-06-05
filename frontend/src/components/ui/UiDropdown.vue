<script setup lang="ts">
import UiPopover from '@/components/ui/UiPopover.vue'

const props = withDefaults(
  defineProps<{
    modelValue: boolean
    placement?:
      | 'bottom-start'
      | 'bottom-end'
      | 'top-start'
      | 'top-end'
      | 'left-start'
      | 'right-start'
    offset?: number
    matchTriggerWidth?: boolean
    triggerClass?: string
    menuClass?: string
  }>(),
  {
    placement: 'bottom-start',
    offset: 8,
    matchTriggerWidth: false,
    triggerClass: '',
    menuClass: '',
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  closed: []
}>()
</script>

<template>
  <UiPopover
    :model-value="props.modelValue"
    :placement="props.placement"
    :offset="props.offset"
    :match-trigger-width="props.matchTriggerWidth"
    :trigger-class="props.triggerClass"
    panel-class="z-50"
    @update:model-value="emit('update:modelValue', $event)"
    @closed="emit('closed')"
  >
    <template #trigger="triggerProps">
      <slot name="trigger" v-bind="triggerProps" />
    </template>

    <template #default="{ close }">
      <div
        class="min-w-52 rounded-md border border-white/10 bg-background p-1 shadow-2xl"
        :class="menuClass"
      >
        <slot :close="close" />
      </div>
    </template>
  </UiPopover>
</template>
