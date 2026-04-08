<script setup lang="ts">
</script>

<template>
  <!-- HERO SECTION -->
  <section
    class="w-full min-h-[60vh] flex flex-col justify-center items-center text-center px-4
           bg-gradient-to-r from-blue-500 to-green-500 text-white py-20"
  >
    <!-- Title -->
    <h1 class="text-4xl md:text-5xl font-bold mb-4">
      Discover Amazing Events
    </h1>

    <!-- Subtitle -->
    <p class="text-lg md:text-xl mb-8 max-w-2xl leading-relaxed">
      We encourage you to apply for our invitations. We value your presence and look
      forward to having you join us in upcoming events. Do not hesitate to apply for the
      <strong>Rwanda Polytechnic Event</strong>.
    </p>

    <!-- Button -->
    <button
      class="bg-white text-gray-800 font-semibold px-6 py-3 rounded-full shadow hover:shadow-lg transition
             flex items-center gap-2"
    >
      Browse Events
      <i class="fa-solid fa-arrow-right"></i>
    </button>
  </section>

  <!-- STATS SECTION -->
  <section class="w-full flex flex-col md:flex-row justify-center gap-6 px-4 -mt-10 md:-mt-16">
    
    <!-- Card 1 -->
    <div
      class="bg-white rounded-xl shadow p-6 flex flex-col items-center text-center w-full md:w-64"
    >
      <div class="text-blue-500 text-4xl mb-2">
        <i class="fa-solid fa-calendar-days"></i>
      </div>
      <h2 class="text-2xl font-bold">2+</h2>
      <p class="text-gray-500 text-sm">Active Events</p>
    </div>

    <!-- Card 2 -->
    <div
      class="bg-white rounded-xl shadow p-6 flex flex-col items-center text-center w-full md:w-64"
    >
      <div class="text-purple-500 text-4xl mb-2">
        <i class="fa-solid fa-user-graduate"></i>
      </div>
      <h2 class="text-2xl font-bold">5000+</h2>
      <p class="text-gray-500 text-sm">Graduates</p>
    </div>

    <!-- Card 3 -->
    <div
      class="bg-white rounded-xl shadow p-6 flex flex-col items-center text-center w-full md:w-64"
    >
      <div class="text-green-500 text-4xl mb-2">
        <i class="fa-solid fa-users"></i>
      </div>
      <h2 class="text-2xl font-bold">8000+</h2>
      <p class="text-gray-500 text-sm">Graduation Event Participants</p>
    </div>

    <!-- Card 4 -->
    <div
      class="bg-white rounded-xl shadow p-6 flex flex-col items-center text-center w-full md:w-64"
    >
      <div class="text-yellow-500 text-4xl mb-2">
        <i class="fa-solid fa-user-check"></i>
      </div>
      <h2 class="text-2xl font-bold">500+</h2>
      <p class="text-gray-500 text-sm">Registered Members for Career Day</p>
    </div>

  </section>
</template>