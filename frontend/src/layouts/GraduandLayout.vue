<template>
  <div class="flex min-h-screen bg-gray-100">
    
    <Sidebar />

    <div class="flex-1 flex flex-col">
      <header class="h-16 bg-white shadow-sm border-b border-slate-200 px-8 py-4 flex justify-between items-center">
        <h1 class="text-xl font-semibold text-gray-700">System Portal</h1>

             <div class="flex right-0 items-center gap-3">
          <span class="text-sm font-medium text-slate-600">{{ auth.user?.last_name }}</span>
          <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-bold">
            {{ auth.user?.last_name?.charAt(0) }}
          </div>
        </div>
      </header>

      <main class="p-6">
        <router-view />
      </main>
    </div>

  </div>
</template>


<script setup>
import Sidebar from '@/components/GraduandBar/Sidebar/Sidebar.vue' // Adjust path to your sidebar
import { useGraduandStore } from '@/store/graduand';

const auth = useGraduandStore();
</script>