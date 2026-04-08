<script setup lang="ts">
import logo from '@/assets/logo/logo_rp.png'
import { ref } from 'vue'

const open = ref(false)
</script>

<template>
  <nav class="w-full bg-white shadow px-4 py-3 flex items-center justify-between">
    
    <!-- Logo -->
    <div class="text-xl font-bold">
    <router-link to="/">
       <img :src="logo" alt="Logo" class="h-8" />
    </router-link>
     
    </div>

    <!-- Desktop Menu -->
    <ul class="hidden md:flex space-x-6 text-gray-700 font-medium">
      <li>
        <router-link to="/" class="hover:text-blue-600">Home</router-link></li>
      <li> <router-link to="/apply" class="hover:text-blue-600">Apply</router-link> </li>
    </ul>

    <!-- Mobile Hamburger -->
    <button
      class="md:hidden text-2xl"
      @click="open = !open"
    >
      <i class="fa-solid fa-bars"></i>
    </button>
  </nav>

  <!-- Mobile Dropdown -->
  <div
    v-if="open"
    class="md:hidden bg-white shadow px-4 py-3 space-y-3"
  >
    <router-link to="/" class="hover:text-blue-600">Home</router-link>
    <router-link to="/apply" class="hover:text-blue-600">Apply</router-link>
  </div>
</template>

<style scoped>
</style>