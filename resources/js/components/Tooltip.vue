<template>
  <div
    ref="triggerRef"
    class="relative inline-block"
    @mouseenter="onMouseEnter"
    @mouseleave="onMouseLeave"
  >
    <slot></slot>
    <Teleport to="body">
      <div
        v-if="text && isHovered"
        class="fixed z-[999999] px-2.5 py-1.5 text-xs font-medium text-white bg-gray-900 rounded-lg shadow-2xl pointer-events-none whitespace-nowrap animate-tooltip-fade-in"
        :style="tooltipStyle"
        style="max-width: 280px;"
      >
        {{ text }}
        <div
          class="absolute w-2 h-2 bg-gray-900 transform rotate-45"
          :class="arrowClass"
        ></div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';

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

const isHovered = ref(false);
const triggerRef = ref(null);
const triggerRect = ref(null);
let hoverTimeout = null;

const onMouseEnter = () => {
  if (triggerRef.value) {
    triggerRect.value = triggerRef.value.getBoundingClientRect();
  }
  hoverTimeout = setTimeout(() => {
    isHovered.value = true;
  }, 300);
};

const onMouseLeave = () => {
  if (hoverTimeout) {
    clearTimeout(hoverTimeout);
    hoverTimeout = null;
  }
  isHovered.value = false;
};

const tooltipStyle = computed(() => {
  if (!triggerRect.value) return { opacity: 0 };

  const rect = triggerRect.value;
  const gap = 8;

  switch (props.position) {
    case 'top':
      return {
        left: `${rect.left + rect.width / 2}px`,
        top: `${rect.top - gap}px`,
        transform: 'translate(-50%, -100%)'
      };
    case 'bottom':
      return {
        left: `${rect.left + rect.width / 2}px`,
        top: `${rect.bottom + gap}px`,
        transform: 'translate(-50%, 0)'
      };
    case 'left':
      return {
        left: `${rect.left - gap}px`,
        top: `${rect.top + rect.height / 2}px`,
        transform: 'translate(-100%, -50%)'
      };
    case 'right':
      return {
        left: `${rect.right + gap}px`,
        top: `${rect.top + rect.height / 2}px`,
        transform: 'translate(0, -50%)'
      };
    default:
      return {};
  }
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

onBeforeUnmount(() => {
  if (hoverTimeout) {
    clearTimeout(hoverTimeout);
  }
});
</script>

<style scoped>
@keyframes tooltip-fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.animate-tooltip-fade-in {
  animation: tooltip-fade-in 0.15s ease-out forwards;
}
</style>


