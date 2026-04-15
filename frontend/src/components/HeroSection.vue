<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axiosInstance from '@/axios'

// Define reactive variables for stats
const stats = ref({
  events: 0,
  graduands: 0,
  invitations: 0
});

const isLoading = ref(true);

const fetchStats = async () => {
  try {
    const response = await axiosInstance.get('/eventsCount'); // Update with your actual route
    stats.value = response.data;
  } catch (error) {
    console.error("Failed to fetch stats:", error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  // Wait for 2 seconds before fetching
  setTimeout(() => {
    fetchStats();
  }, 2000);
});
</script>

<template>
  <section
    class="w-full min-h-[60vh] flex flex-col justify-center items-center text-center px-4
           bg-gradient-to-r from-blue-500 to-green-500 text-white py-20"
  >
    <h1 class="text-4xl md:text-5xl font-bold mb-4">
      Discover Amazing Events
    </h1>

    <p class="text-lg md:text-xl mb-8 max-w-2xl leading-relaxed">
      We encourage you to apply for our invitations. We value your presence and look
      forward to having you join us in upcoming events.
    </p>

    <button
      class="bg-white text-gray-800 font-semibold px-6 py-3 rounded-full shadow hover:shadow-lg transition
             flex items-center gap-2"
    >
      Browse Events
      <i class="fa-solid fa-arrow-right"></i>
    </button>
  </section>

  <section class="w-full flex flex-col md:flex-row justify-center gap-6 px-4 -mt-10 md:-mt-16">
    
    <div class="bg-white rounded-xl shadow p-6 flex flex-col items-center text-center w-full md:w-64">
      <div class="text-blue-500 text-4xl mb-2">
        <i class="fa-solid fa-calendar-days"></i>
      </div>
      <h2 class="text-2xl font-bold">
        {{ isLoading ? '...' : stats.events + '+' }}
      </h2>
      <p class="text-gray-500 text-sm">Active Events</p>
    </div>

    <div class="bg-white rounded-xl shadow p-6 flex flex-col items-center text-center w-full md:w-64">
      <div class="text-purple-500 text-4xl mb-2">
        <i class="fa-solid fa-user-graduate"></i>
      </div>
      <h2 class="text-2xl font-bold">
        {{ isLoading ? '...' : stats.graduands.toLocaleString() + '+' }}
      </h2>
      <p class="text-gray-500 text-sm">Graduates</p>
    </div>

    <div class="bg-white rounded-xl shadow p-6 flex flex-col items-center text-center w-full md:w-64">
      <div class="text-green-500 text-4xl mb-2">
        <i class="fa-solid fa-users"></i>
      </div>
      <h2 class="text-2xl font-bold">
        {{ isLoading ? '...' : stats.invitations.toLocaleString() + '+' }}
      </h2>
      <p class="text-gray-500 text-sm">Graduation Event Participants</p>
    </div>

  </section>
</template>