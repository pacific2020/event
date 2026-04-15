<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { useGraduandStore } from '@/store/graduand';
import { 
  TicketIcon, 
  AcademicCapIcon, 
  UserGroupIcon, 
  XMarkIcon, 
  MapPinIcon 
} from '@heroicons/vue/24/outline'
import axiosInstance from '@/axios';
import type { College } from '@/types';
import { useToast } from 'vue-toastification'

const toast = useToast()
const auth = useGraduandStore();

// --- State Management ---
const isModalOpen = ref(false)
const selectedCollegeId = ref<number | null>(null)
const isSaving = ref(false)
const colleges = ref<College[]>([]) 

// ✅ 1. Fetch all colleges (This is fine as a local fetch for the list)
const getColleges = async () => {
  try {
    const { data } = await axiosInstance.get('/colleges')
    colleges.value = data.data 
  } catch (e) {
    console.error("Failed to load colleges");
  }
}

// ✅ 2. Save/Update Pickup Location
const confirmPickupPlace = async () => {
  if (!selectedCollegeId.value) {
    toast.warning("Please select your campus first");
    return;
  }

  isSaving.value = true;
  try {
    await axiosInstance.post('/set-gown-pickup', {
      college_id: selectedCollegeId.value,
      reg_no: auth.user?.reg_no 
    });
    
    toast.success("Location saved successfully!");
    isModalOpen.value = false;
    
    // ✅ Refresh the global store state (passing true to bypass the cache check)
    await auth.getPickupLocation(true); 
    await auth.getUser();
  } catch (e) {
    toast.error("Failed to update location");
  } finally {
    isSaving.value = false;
  }
}

// ✅ 3. Auto-sync the radio selection when global state changes
watch(() => auth.pickupData, (newData) => {
  if (newData?.college_id) {
    selectedCollegeId.value = newData.college_id;
  }
}, { immediate: true });

onMounted(async () => {
  await auth.getUser();
  getColleges();
  
  // ✅ This will only call the DB if auth.pickupData is null
  auth.getPickupLocation();
})
</script>

<template>
  <div class="flex min-h-screen bg-gray-50">
    <div class="flex-1">

      <main class="p-8 max-w-6xl mx-auto">
        <div v-if="auth.loading" class="animate-pulse space-y-4">
          <div class="h-10 bg-gray-200 rounded w-1/3"></div>
          <div class="grid grid-cols-3 gap-6"><div v-for="i in 3" :key="i" class="h-40 bg-gray-200 rounded-2xl"></div></div>
        </div>

        <div v-else>
          <div class="mb-8">
            <h2 class="text-3xl font-extrabold text-gray-900">Welcome, {{ auth.user?.first_name }}! 👋</h2>
            <p class="text-gray-600 mt-2">Verify your graduation details and gown collection point.</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 ring-1 ring-black/5">
              <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-4 text-purple-600">
                <AcademicCapIcon class="w-7 h-7" />
              </div>
              <h3 class="font-bold text-lg text-gray-800">Graduation Gown</h3>
              
              <div class="mt-3 min-h-[40px]">
                <div v-if="auth.pickupData" class="flex items-start gap-2 text-sm">
                  <MapPinIcon class="w-5 h-5 text-green-600 shrink-0" />
                  <div>
                    <p class="text-gray-500 text-xs font-bold uppercase">Collection Point</p>
                    <p class="font-bold text-green-700">{{ auth.pickupData.college?.short_name }} Campus</p>
                  </div>
                </div>
                <p v-else class="text-sm text-orange-600 font-medium italic flex items-center gap-2">
                  <span class="w-2 h-2 bg-orange-500 rounded-full animate-ping"></span>
                  Pickup location not yet selected
                </p>
              </div>

              <button 
                @click="isModalOpen = true"
                class="mt-5 w-full py-2.5 rounded-xl font-bold transition-all"
                :class="auth.pickupData ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-purple-600 text-white shadow-lg shadow-purple-200 hover:bg-purple-700'"
              >
                {{ auth.pickupData ? 'Change Location' : 'Select Pickup Place' }}
              </button>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
               <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-4 text-blue-600"><TicketIcon class="w-7 h-7" /></div>
               <h3 class="font-bold text-lg text-gray-800">Invitations</h3>
               <p class="text-sm text-gray-500 mt-1">Generate your guest entry passes.</p>
               <router-link to="/Graduand/generate/create" class="mt-4 block text-center py-2 bg-blue-50 text-blue-600 rounded-lg font-bold hover:bg-blue-100">Open Generator</router-link>
            </div>
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
               <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-4 text-emerald-600"><UserGroupIcon class="w-7 h-7" /></div>
               <h3 class="font-bold text-lg text-gray-800">Guest Limit</h3>
               <p class="text-sm text-gray-500 mt-1">Standard allowance: 2 Guests.</p>
            </div>
          </div>
        </div>
      </main>
    </div>

    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-8">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Select Campus</h2>
            <button @click="isModalOpen = false" class="p-2 hover:bg-gray-100 rounded-full transition-colors"><XMarkIcon class="w-6 h-6 text-gray-400" /></button>
          </div>

          <p class="text-gray-500 mb-6 text-sm">Graduands must collect gowns from their registered campus: <b>{{ auth.user?.college_name }}</b></p>

          <div class="space-y-3 max-h-[350px] overflow-y-auto pr-2 custom-scrollbar">
            <div v-for="college in colleges" :key="college.id">
              <label 
                :class="[
                  'flex items-center justify-between p-4 rounded-2xl border-2 transition-all',
                  selectedCollegeId === college.id ? 'border-purple-600 bg-purple-50' : 'border-gray-100 bg-white',
                  college.short_name !== auth.user?.college_name ? 'opacity-40 cursor-not-allowed bg-gray-50' : 'hover:border-purple-200 cursor-pointer'
                ]"
              >
                <div class="flex items-center gap-3">
                  <input type="radio" v-model="selectedCollegeId" :value="college.id" :disabled="college.short_name !== auth.user?.college_name" class="w-5 h-5 text-purple-600 focus:ring-purple-500" />
                  <span class="font-bold text-sm text-gray-700">{{ college.short_name }}</span>
                </div>
                <span v-if="college.short_name === auth.user?.college_name" class="text-[9px] font-black uppercase px-2 py-1 bg-purple-600 text-white rounded-md">Eligible</span>
              </label>
            </div>
          </div>

          <div class="mt-8 flex gap-4">
            <button @click="isModalOpen = false" class="flex-1 py-3 text-gray-500 font-bold hover:bg-gray-50 rounded-xl transition-colors">Cancel</button>
            <button @click="confirmPickupPlace" :disabled="isSaving || !selectedCollegeId" class="flex-1 py-3 bg-purple-600 text-white rounded-xl font-bold hover:bg-purple-700 disabled:opacity-50 transition-all">
              {{ isSaving ? 'Saving...' : 'Confirm Location' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>