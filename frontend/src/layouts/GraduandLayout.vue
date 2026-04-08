<template>
  <div class="flex min-h-screen bg-gray-100">
    
    <Sidebar />

    <div class="flex-1 flex flex-col">
      <header class="h-16 bg-white shadow-sm flex items-center px-8">
        <h1 class="text-xl font-semibold text-gray-700">System Portal</h1>
      </header>

      <main class="p-6">
        <router-view />
      </main>
    </div>

  </div>
</template>

<script setup>
import Sidebar from '@/components/GraduandBar/Sidebar/Sidebar.vue' // Adjust path to your sidebar
</script>