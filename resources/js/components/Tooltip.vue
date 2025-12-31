<template>
  <div class="relative inline-block group">
    <slot></slot>
    <div
      v-if="text"
      class="absolute z-[999999] px-2.5 py-1.5 text-xs font-medium text-white bg-gray-900 rounded-lg shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-150 delay-300 pointer-events-none whitespace-nowrap"
      :class="positionClass"
      style="max-width: 280px;"
    >
      {{ text }}
      <div
        class="absolute w-2 h-2 bg-gray-900 transform rotate-45"
        :class="arrowClass"
      ></div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  text: {
    type: String,
    default: ''
  },
  position: {
    type: String,
    default: 'bottom', // top, bottom, left, right
    validator: (value) => ['top', 'bottom', 'left', 'right'].includes(value)
  }
});

const positionClass = computed(() => {
  const classes = {
    top: 'bottom-full left-1/2 -translate-x-1/2 mb-2',
    bottom: 'top-full left-1/2 -translate-x-1/2 mt-2',
    left: 'right-full top-1/2 -translate-y-1/2 mr-2',
    right: 'left-full top-1/2 -translate-y-1/2 ml-2'
  };
  return classes[props.position];
});

const arrowClass = computed(() => {
  const classes = {
    top: 'bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2',
    bottom: 'top-0 left-1/2 -translate-x-1/2 -translate-y-1/2',
    left: 'right-0 top-1/2 -translate-y-1/2 translate-x-1/2',
    right: 'left-0 top-1/2 -translate-y-1/2 -translate-x-1/2'
  };
  return classes[props.position];
});
</script>


