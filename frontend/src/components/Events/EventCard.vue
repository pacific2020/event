<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'

interface Props {
  category: string
  title: string
  starting_date: string
  ending_date: string
  generated_at: string
  attendees: number
  image?: string | null
}

const props = defineProps<Props>()

// UI State for countdown
const days = ref(0)
const hours = ref(0)
const minutes = ref(0)
const seconds = ref(0)

/**
 * FIX: Use number | undefined for browser-based timers to avoid 
 * the 'Timeout' vs 'number' assignment error.
 */
let timer: number | undefined

const updateCountdown = () => {
  const now = new Date().getTime()
  const start = new Date(props.starting_date).getTime()
  const distance = start - now

  if (distance <= 0) {
    days.value = hours.value = minutes.value = seconds.value = 0
    if (timer) clearInterval(timer)
    return
  }

  days.value = Math.floor(distance / (1000 * 60 * 60 * 24))
  hours.value = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  minutes.value = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60))
  seconds.value = Math.floor((distance % (1000 * 60)) / 1000)
}

// Lifecycle hooks
onMounted(() => {
  updateCountdown()
  // Explicitly use window.setInterval to ensure the 'number' type is returned
  timer = window.setInterval(updateCountdown, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

// Computed values for cleaner templates
const countdownText = computed(() => {
  if (days.value + hours.value + minutes.value + seconds.value === 0) {
    return 'Event started!'
  }
  return `${days.value}d ${hours.value}h ${minutes.value}m ${seconds.value}s`
})

const formattedStartDate = computed(() => {
  return new Date(props.starting_date).toLocaleDateString(undefined, {
    weekday: 'short',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})

const eventImage = computed(() => {
  return props.image || 'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?q=80&w=1000&auto=format&fit=crop'
})
</script>

<template>
  <div class="bg-white rounded-xl shadow-md overflow-hidden w-full md:w-85 relative hover:scale-105 transform transition duration-300 border border-gray-100">
    
    <div class="relative h-48 w-full bg-gray-200">
      <img 
        :src="eventImage" 
        :alt="title"
        class="w-full h-full object-cover" 
      />
      
      <span class="absolute top-3 right-3 bg-blue-600 text-white text-[10px] uppercase font-bold px-3 py-1 rounded-full shadow-lg">
        {{ category }}
      </span>

      <span 
        class="absolute top-3 left-3 text-white text-xs px-3 py-1 rounded-full shadow-lg backdrop-blur-md"
        :class="countdownText === 'Event started!' ? 'bg-green-600' : 'bg-red-600'"
      >
        <i class="fa-solid fa-clock mr-1"></i>
        {{ countdownText }}
      </span>
    </div>

    <div class="p-5">
      <h3 class="text-lg font-bold text-gray-800 mb-3 line-clamp-1">{{ title }}</h3>

      <div class="space-y-2 mb-5">
        <div class="flex items-center text-gray-500 text-sm">
          <i class="fa-solid fa-calendar-days w-5 text-blue-500"></i>
          <span>{{ formattedStartDate }}</span>
        </div>

        <div class="flex items-center text-gray-500 text-sm">
          <i class="fa-solid fa-user-group w-5 text-blue-500"></i>
          <span>{{ attendees.toLocaleString() }} attending</span>
        </div>
      </div>

      <router-link
        to="/apply"
        class="w-full block text-center bg-gray-900 text-white font-medium text-sm py-2.5 rounded-lg hover:bg-blue-600 transition-colors duration-200"
      >
        Apply for Invitation
      </router-link>
    </div>
  </div>
</template>

<style scoped>
/* Optional: ensures long titles don't break the card layout */
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;  
  overflow: hidden;
}
</style>