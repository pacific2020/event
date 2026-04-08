<script setup>
import { onMounted, ref } from 'vue'
import { useGraduandStore } from '@/store/graduand'

const userOpen = ref(false)
const eventOpen = ref(false)


const toggleUser = () => {
  userOpen.value = !userOpen.value
}

const toggleEvent = () => {
  eventOpen.value = !eventOpen.value
}



const auth = useGraduandStore()


onMounted(() => {
  auth.getUser();
})
</script>


<template>
  <div class="w-64 h-screen bg-gray-900 text-white p-4 flex flex-col justify-between">
     
    <!-- TOP MENU -->
    <div>
      <h2 class="text-2xl font-bold mb-6">Graduand/ <strong v-if="auth.user">{{ auth.user.reg_no }}</strong></h2>

<div>
  <router-link to="/Graduand/dashboard" class="block p-2 hover:bg-gray-700 rounded">Dashboard</router-link>
</div>

   



  






    </div>

    <!-- LOGOUT LINK AT BOTTOM -->
    <div>
      <a 
        href="#"
        @click.prevent="auth.logout"
        class="block p-2 text-red-500 hover:bg-gray-700 rounded"
      >
        🚪 Logout
      </a>
    </div>

  </div>
</template>

