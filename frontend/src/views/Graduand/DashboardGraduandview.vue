<script setup lang="ts">
import { onMounted, ref, computed } from 'vue';
import { useGraduandStore } from '@/store/graduand';
import { 
  TicketIcon, 
  AcademicCapIcon, 
  UserGroupIcon, 
  XMarkIcon, 
  LockClosedIcon, 
  CheckCircleIcon,
  InformationCircleIcon 
} from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'

const toast = useToast()
const auth = useGraduandStore();

// --- States ---
const isGownDetailsModalOpen = ref(false)
const isPickupModalOpen = ref(false)

const gownData = computed(() => auth.gownData);

// When gownData becomes null (via the 2s poll), 
// this automatically switches back to "Go to pickup gown..."
const buttonText = computed(() => gownData.value ? 'View Gown Details' : 'Go to pickup gown on your college');

// Returns true only if gown is issued/collected and NOT returned
const canAccessInvitations = computed(() => {
  if (!gownData.value) return false;
  const status = gownData.value.status?.toLowerCase();
  // Allow access if issued/collected, deny if returned or no data
  return status !== 'returned' && !!gownData.value;
});

// Helper to identify why the invitation card is locked
const lockReason = computed(() => {
  if (!gownData.value) return "Set Gown First";
  if (gownData.value.status?.toLowerCase() === 'returned') return "Gown Returned";
  return "Locked";
});

// --- Lifecycle ---
onMounted(async () => {
  // 1. Ensure user is loaded (this triggers the store's logic)
  if (!auth.user) {
    await auth.getUser();
  }
  // Note: We removed the manual fetchGownStatus call here. 
  // The store's useQuery starts automatically because it is "enabled" 
  // as soon as auth.user.reg_no exists.
});

// --- Actions ---
const handleGownAction = () => {
  if (gownData.value) {
    isGownDetailsModalOpen.value = true;
  } else {
    // Logic for when no gown record exists
    toast.info("Please visit your college to pick up your gown.");
    isPickupModalOpen.value = true; 
  }
};

const checkInvitationAccess = (e: Event) => {
  if (!canAccessInvitations.value) {
    e.preventDefault();
    const msg = gownData.value?.status?.toLowerCase() === 'returned' 
      ? "Invitations are disabled because the gown has been returned."
      : "Please set your Gown Pickup Place first!";
    toast.warning(msg);
  }
};
</script>

<template>
  <div class="flex min-h-screen bg-[#F8FAFC]">

    <div class="flex-1">
      <header class="bg-white/80 backdrop-blur-md sticky top-0 z-10 border-b border-slate-200 px-8 py-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-slate-800">Graduand Portal</h1>
        <div class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-black uppercase tracking-wider">
          Eligible
        </div>
      </header>

      <main class="p-8 max-w-6xl mx-auto">
        <div v-if="auth.loading" class="animate-pulse space-y-6">
          <div class="h-12 bg-slate-200 rounded-xl w-64"></div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div v-for="i in 3" :key="i" class="h-48 bg-slate-200 rounded-[2rem]"></div>
          </div>
        </div>

        <div v-else>
          <div class="mb-10 flex justify-between items-end">
            <div>
              <h2 class="text-3xl font-black text-slate-900">Welcome, {{ auth.user?.first_name }}! 👋</h2>
              <p class="text-slate-500 mt-1">Manage your graduation requirements and track your regalia status.</p>
            </div>
            <div v-if="auth.isGownLoading" class="text-[10px] text-slate-400 animate-pulse font-mono uppercase">
              Syncing Data...
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
            
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-md transition-all">
              <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center mb-6 text-indigo-600 relative">
                <AcademicCapIcon class="w-8 h-8" />
                <span v-if="auth.isGownLoading" class="absolute -top-1 -right-1 flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
                </span>
              </div>
              <h3 class="font-bold text-xl text-slate-800">Graduation Gown</h3>
              <p class="text-sm mt-2 text-slate-500">
                Status: 
                <span :class="[
                  'font-bold',
                  gownData?.status?.toLowerCase() === 'returned' ? 'text-rose-600' : 'text-indigo-600'
                ]">
                  {{ gownData ? gownData.status : 'Not Selected' }}
                </span>
              </p>
              <button 
                @click="handleGownAction"
                class="mt-8 w-full py-3.5 bg-slate-900 text-white rounded-xl font-bold hover:bg-indigo-600 transition-all active:scale-95"
              >
                {{ gownData ? 'View Gown Details' : 'Go to pickup gown on your college' }}
              </button>
            </div>

            <div :class="[
              'p-8 rounded-[2rem] border transition-all duration-500 relative overflow-hidden', 
              !canAccessInvitations ? 'bg-slate-50 border-slate-200 grayscale' : 'bg-white border-slate-100 shadow-sm hover:shadow-md'
            ]">
              <div v-if="!canAccessInvitations" class="absolute inset-0 bg-slate-50/40 z-10 flex items-center justify-center backdrop-blur-[1px]">
                 <div class="bg-white px-4 py-2 rounded-full shadow-md border border-slate-200 flex items-center gap-2">
                   <LockClosedIcon class="w-4 h-4 text-slate-400" />
                   <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">{{ lockReason }}</span>
                 </div>
              </div>

              <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center mb-6 text-blue-600">
                <TicketIcon class="w-8 h-8" />
              </div>
              <h3 class="font-bold text-xl text-slate-800">Invitations</h3>
              <p class="text-sm mt-2 text-slate-500">Generate your guest entry cards.</p>
              
              <router-link 
                to="/Graduand/generate/create" 
                @click="checkInvitationAccess"
                class="mt-8 block text-center py-3.5 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100"
              >
                Generate Invitations
              </router-link>
            </div>

            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100">
              <div class="w-14 h-14 bg-emerald-50 rounded-2xl flex items-center justify-center mb-6 text-emerald-600">
                <UserGroupIcon class="w-8 h-8" />
              </div>
              <h3 class="font-bold text-xl text-slate-800">Guest Limit</h3>
              <p class="text-sm mt-2 text-slate-500">
                Maximum of <strong>2 accompanied guests</strong> allowed per graduand.
              </p>
              <div class="mt-6 flex -space-x-2">
                <div v-for="i in 3" :key="i" class="w-9 h-9 rounded-full bg-slate-200 border-2 border-white"></div>
              </div>
            </div>

          </div>
        </div>
      </main>
    </div>

    <div v-if="isGownDetailsModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-md overflow-hidden animate-pop">
        <div class="p-10">
          <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Gown Details</h2>
            <button @click="isGownDetailsModalOpen = false" class="p-2 hover:bg-slate-100 rounded-full transition-colors">
              <XMarkIcon class="w-6 h-6 text-slate-400" />
            </button>
          </div>

          <div class="space-y-6">
            <div v-if="gownData?.status?.toLowerCase() === 'returned'" class="p-4 bg-rose-50 border border-rose-100 rounded-2xl flex gap-3">
              <InformationCircleIcon class="w-5 h-5 text-rose-600 shrink-0" />
              <p class="text-[11px] text-rose-800 leading-relaxed font-medium">
                Gown has been returned. Invitation generation is now disabled.
              </p>
            </div>

            <div class="flex items-center justify-between p-5 bg-indigo-50 rounded-2xl border border-indigo-100">
              <div class="flex items-center gap-3">
                <CheckCircleIcon class="w-6 h-6 text-indigo-600" />
                <span class="text-sm font-bold text-indigo-900 uppercase">Status</span>
              </div>
              <span :class="[
                'px-3 py-1 text-[10px] font-black rounded-lg uppercase tracking-widest',
                gownData?.status?.toLowerCase() === 'returned' ? 'bg-rose-600 text-white' : 'bg-indigo-600 text-white'
              ]">
                {{ gownData?.status }}
              </span>
            </div>

            <div class="space-y-4 px-2">
              <div class="flex justify-between items-center py-2 border-b border-slate-50">
                <span class="text-slate-400 text-sm">Issued Date</span>
                <span class="font-bold text-slate-700">{{ gownData?.created_at ? new Date(gownData.created_at).toLocaleDateString() : 'N/A' }}</span>
              </div>
              <div class="flex justify-between items-center py-2 border-b border-slate-50">
                <span class="text-slate-400 text-sm">Expected Return</span>
                <span class="font-bold text-rose-600">{{ gownData?.expected_returning_date?.split(' ')[0] || 'N/A' }}</span>
              </div>
              <div v-if="gownData?.returned_date" class="flex justify-between items-center py-2 border-b border-slate-50">
                <span class="text-slate-400 text-sm">Returned Date</span>
                <span class="font-bold text-emerald-600">{{ new Date(gownData.returned_date).toLocaleDateString() }}</span>
              </div>
              <div class="flex justify-between items-center py-2">
                <span class="text-slate-400 text-sm">Reg No</span>
                <span class="font-bold text-slate-700">{{ gownData?.reg_no }}</span>
              </div>
            </div>
          </div>

          <button @click="isGownDetailsModalOpen = false" class="mt-10 w-full py-4 bg-slate-900 text-white rounded-2xl font-black hover:bg-slate-800 transition-all active:scale-95">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>