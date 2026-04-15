<script setup lang="ts">
import logo from '@/assets/logo/logo_rp.png'
import { ref, onMounted, onUnmounted, watch } from 'vue'

const open = ref(false)
const menuRef = ref<HTMLElement | null>(null)

// 1. Core Logic: Clean Toggle
const toggleMenu = (e: Event) => {
  e.stopPropagation() // Prevents the 'click outside' from firing immediately
  open.value = !open.value
}

const closeMenu = () => {
  open.value = false
}

// 2. Click Outside: Uses mousedown for better mobile response
const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement
  if (open.value && menuRef.value && !menuRef.value.contains(target)) {
    open.value = false
  }
}

// 3. Screen Resize: Auto-fix if orientation changes
const handleResize = () => {
  if (window.innerWidth >= 768) {
    open.value = false
  }
}

// 4. Scroll Lock: Prevents the page from scrolling behind the menu
watch(open, (isOpen) => {
  if (isOpen) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

onMounted(() => {
  window.addEventListener('resize', handleResize)
  window.addEventListener('mousedown', handleClickOutside)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  window.removeEventListener('mousedown', handleClickOutside)
  document.body.style.overflow = '' // Cleanup
})
</script>

<template>
  <header class="fixed top-0 left-0 w-full z-[100] bg-white shadow-md">
    <transition name="fade">
      <div 
        v-if="open" 
        class="fixed inset-0 bg-black/40 backdrop-blur-sm md:hidden"
        @click="closeMenu"
      ></div>
    </transition>

    <nav class="relative z-[101] bg-white">
      <div class="max-w-7xl mx-auto px-4 h-16 lg:h-20 flex items-center justify-between">
        
        <router-link to="/" class="flex-shrink-0" @click="closeMenu">
          <img :src="logo" alt="Logo" class="h-9 lg:h-12 w-auto" />
        </router-link>

        <div class="hidden md:flex items-center space-x-8">
          <router-link to="/" class="nav-item">Home</router-link>
          <router-link to="/apply" class="btn-primary">Get Started</router-link>
        </div>

        <div class="md:hidden flex items-center">
          <button
            @click.stop="toggleMenu"
            class="p-2 text-gray-800 rounded-lg hover:bg-gray-100 transition-colors"
            aria-label="Toggle Menu"
          >
            <i :class="['fa-solid text-2xl', open ? 'fa-xmark' : 'fa-bars']"></i>
          </button>
        </div>
      </div>
    </nav>

    <transition name="mobile-menu">
      <div
        v-if="open"
        ref="menuRef"
        class="md:hidden absolute top-full left-0 w-full bg-white shadow-2xl border-t z-[101]"
      >
        <div class="p-6 space-y-3">
          <router-link to="/" @click="closeMenu" class="mobile-link">
            <i class="fa-solid fa-house mr-4 text-blue-600"></i>Home
          </router-link>
          <div class="pt-4">
            <router-link to="/apply" @click="closeMenu" class="mobile-btn">
              Get Started
            </router-link>
          </div>
        </div>
      </div>
    </transition>
  </header>

  <div class="h-16 lg:h-20"></div>
</template>

<style scoped>
/* Common Nav Items */
.nav-item {
  @apply text-gray-600 font-bold hover:text-blue-600 transition;
}

.router-link-active.nav-item {
  @apply text-blue-600 border-b-2 border-blue-600 pb-1;
}

.btn-primary {
  @apply px-6 py-2 bg-blue-600 text-white rounded-full font-bold hover:bg-blue-700 transition shadow-sm;
}

/* Mobile Links */
.mobile-link {
  @apply flex items-center p-4 text-lg font-bold text-gray-700 rounded-xl hover:bg-gray-50 transition;
}

.router-link-active.mobile-link {
  @apply bg-blue-50 text-blue-600;
}

.mobile-btn {
  @apply flex items-center justify-center w-full py-4 bg-blue-600 text-white font-bold rounded-xl shadow-lg active:scale-95 transition;
}

/* Animations */
.mobile-menu-enter-active, .mobile-menu-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}
.mobile-menu-enter-from, .mobile-menu-leave-to {
  transform: translateY(-10px);
  opacity: 0;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>