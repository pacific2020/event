<script setup lang="ts">
</script>

<template>
  <footer class="bg-gray-900 text-gray-300 py-10 mt-12">
    <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-4 gap-10">

      <!-- LOGO + ABOUT -->
      <div>
        <h2 class="text-white text-xl font-semibold mb-4">Rwanda Polytechnic</h2>
        <p class="text-sm leading-relaxed">
          We organize and host professional, academic, and community‑focused events. 
          Connect with us and stay updated with upcoming opportunities.
        </p>
      </div>

      <!-- QUICK LINKS -->
      <div>
        <h3 class="text-white text-lg font-semibold mb-4">Quick Links</h3>
        <ul class="space-y-2 text-sm">
          <li><router-link to="./">Home</router-link></li>
          <li><router-link to="/apply">Apply</router-link></li>
        </ul>
      </div>

      <!-- SUPPORT -->
      <div>
        <h3 class="text-white text-lg font-semibold mb-4">Support</h3>
        <ul class="space-y-2 text-sm">
          <li>graduation@rp.ac.rw</li>
          <li> +250 786 749 012</li>
        </ul>
      </div>

      <!-- SOCIAL -->
      <div>
        <h3 class="text-white text-lg font-semibold mb-4">Follow Us</h3>
        <div class="flex items-center space-x-4 text-xl">
          <i class="fa-brands fa-facebook hover:text-white cursor-pointer"></i>
          <i class="fa-brands fa-twitter hover:text-white cursor-pointer"></i>
          <i class="fa-brands fa-instagram hover:text-white cursor-pointer"></i>
          <i class="fa-brands fa-linkedin hover:text-white cursor-pointer"></i>
        </div>
      </div>

    </div>

    <!-- COPYRIGHT -->
    <div class="text-center text-gray-500 text-sm mt-10 border-t border-gray-700 pt-5">
      © {{ new Date().getFullYear() }} Rwanda Polytechnic — All Rights Reserved.
    </div>
  </footer>
</template>
