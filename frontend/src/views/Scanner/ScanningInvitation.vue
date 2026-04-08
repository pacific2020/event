<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Html5QrcodeScanner } from "html5-qrcode";
import axiosInstance from '@/axios';
import { useAuthStore } from '@/store/auth';
import { useToast } from 'vue-toastification';

const toast = useToast();
const auth = useAuthStore();

// --- State ---
const scannedResult = ref<any>(null);
const isProcessing = ref(false); 
const lastScannedId = ref<string | null>(null);
const scanStatus = ref<'idle' | 'success' | 'error'>('idle');

// --- Haptic Feedback (Vibration Only) ---
const triggerVibration = (type: 'success' | 'error') => {
  if (navigator.vibrate) {
    navigator.vibrate(type === 'success' ? 80 : [80, 40, 80]);
  }
};

const onScanSuccess = async (decodedText: string) => {
  if (isProcessing.value || decodedText === lastScannedId.value) return;

  if (!auth.user?.id) {
    toast.error("Session expired. Please log in.");
    return;
  }

  isProcessing.value = true;
  lastScannedId.value = decodedText;

  try {
    const payload = {
      entrance_user_id: auth.user.id,
      scanned: 'Scanned',
      date_scanned: new Date().toLocaleString('sv-SE').replace(' ', 'T'),
    };

    const { data } = await axiosInstance.post(`/auto-scan/${decodedText}`, payload);
    
    scannedResult.value = data.invitation;
    scanStatus.value = 'success';
    triggerVibration('success');
    toast.success("Access Granted");

  } catch (error: any) {
    scannedResult.value = error.response?.data?.invitation || { error: true };
    scanStatus.value = 'error';
    triggerVibration('error');
    toast.error(error.response?.data?.message || "Verification Failed");
  } finally {
    // Reset after 4 seconds to allow the gatekeeper to read the data
    setTimeout(() => {
      isProcessing.value = false;
      lastScannedId.value = null;
      scanStatus.value = 'idle';
    }, 4500);
  }
};

let scanner: any = null;
onMounted(async () => {
  if (!auth.user) await auth.getUser();
  scanner = new Html5QrcodeScanner("qr-reader", { fps: 20, qrbox: 250 }, false);
  scanner.render(onScanSuccess);
});

onUnmounted(() => { if (scanner) scanner.clear(); });

// Format Date Helper
const formatDate = (dateStr: string) => {
  if (!dateStr) return 'Just Now';
  return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
};
</script>

<template>
  <div class="min-h-screen transition-all duration-700 p-4 font-sans"
       :class="{
         'bg-slate-900': scanStatus === 'idle',
         'bg-emerald-950': scanStatus === 'success',
         'bg-rose-950': scanStatus === 'error'
       }">
    
    <div class="max-w-xl mx-auto">
      <header class="flex justify-between items-center mb-8 pt-2">
        <div>
          <h2 class="text-white font-black text-2xl tracking-tighter uppercase italic">
            Gate<span class="text-indigo-400">Control</span>
          </h2>
          <p class="text-[9px] text-slate-400 font-bold tracking-[0.3em] uppercase">Security Terminal Alpha</p>
        </div>
        <div class="text-right">
          <p class="text-white font-mono text-sm">{{ new Date().toLocaleTimeString() }}</p>
          <div class="flex items-center gap-1 justify-end">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-[9px] text-emerald-500 font-black uppercase">Online</span>
          </div>
        </div>
      </header>

      <div class="relative mb-10">
        <div class="absolute -inset-4 rounded-[3rem] blur-xl opacity-20 transition-all duration-500"
             :class="{
               'bg-indigo-500': scanStatus === 'idle',
               'bg-emerald-500': scanStatus === 'success',
               'bg-rose-500': scanStatus === 'error'
             }"></div>
             
        <div class="relative bg-black rounded-[2.5rem] overflow-hidden border-[4px] transition-all duration-500"
             :class="{
               'border-slate-700': scanStatus === 'idle',
               'border-emerald-400 scale-[1.03]': scanStatus === 'success',
               'border-rose-400 scale-[1.03]': scanStatus === 'error'
             }">
          <div id="qr-reader" class="w-full opacity-80"></div>
          
          <transition name="fade">
            <div v-if="isProcessing" class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-10">
              <div class="text-center">
                 <div class="w-14 h-14 border-[5px] border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                 <p class="text-white text-[11px] font-black mt-6 tracking-[0.5em] animate-pulse">ANALYZING</p>
              </div>
            </div>
          </transition>
        </div>
      </div>

      <transition name="slide-up">
        <div v-if="scannedResult" class="relative group">
          <div class="bg-white rounded-[2rem] shadow-2xl overflow-hidden border border-white/20">
            
            <div class="p-8">
              <div class="flex items-center gap-4 mb-8">
                <div :class="scanStatus === 'success' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'" 
                     class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0">
                  <svg v-if="scanStatus === 'success'" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-2xl font-black text-slate-900 leading-tight">{{ scannedResult.fullname }}</h3>
                  <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ scannedResult.type || 'Standard Entry' }}</p>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-y-8 gap-x-4 mb-8">
                <div class="border-l-2 border-slate-100 pl-4">
                  <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Reg Number</label>
                  <p class="text-sm font-bold text-slate-800">{{ scannedResult.reg_no }}</p>
                </div>
                <div class="border-l-2 border-slate-100 pl-4">
                  <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">ID Number</label>
                  <p class="text-sm font-bold text-slate-800">{{ scannedResult.graduate_idnumber || 'N/A' }}</p>
                </div>
                <div class="border-l-2 border-slate-100 pl-4">
                  <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Contact No</label>
                  <p class="text-sm font-bold text-slate-800">{{ scannedResult.phonenumber || 'N/A' }}</p>
                </div>
                <div class="border-l-2 border-slate-100 pl-4">
                  <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Scan Time</label>
                  <p class="text-sm font-bold text-indigo-600 italic">{{ formatDate(scannedResult.date_scanned) }}</p>
                </div>
              </div>

              <div class="flex items-center justify-between pt-6 border-t border-slate-50">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-[10px] font-black">
                    {{ auth.user?.fullname?.charAt(0) }}
                  </div>
                  <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase">Operator</p>
                    <p class="text-[11px] font-bold text-slate-800">{{ auth.user?.fullname }}</p>
                  </div>
                </div>
                <div class="text-[10px] font-black px-3 py-1 rounded-md bg-slate-100 text-slate-500 uppercase tracking-tighter">
                  {{ scannedResult.scanned }}
                </div>
              </div>
            </div>

            <div class="h-2 w-full bg-slate-50">
               <div class="h-full bg-indigo-500 animate-progress-bar"></div>
            </div>
          </div>
        </div>
      </transition>

      <div v-if="!scannedResult && !isProcessing" class="mt-20 text-center">
         <div class="w-16 h-16 border-2 border-dashed border-slate-700 rounded-full flex items-center justify-center mx-auto mb-4">
            <div class="w-2 h-2 bg-indigo-500 rounded-full animate-ping"></div>
         </div>
         <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.5em]">Awaiting Target</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Scoped overrides for library UI */
#qr-reader { border: none !important; background: transparent !important; }
#qr-reader__dashboard_section_csr button {
  @apply bg-white text-slate-900 px-6 py-2 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg;
}

/* Animations */
.slide-up-enter-active { transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-up-enter-from { opacity: 0; transform: translateY(40px) scale(0.95); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@keyframes progress-fill {
  from { width: 100%; }
  to { width: 0%; }
}
.animate-progress-bar { animation: progress-fill 4.5s linear forwards; }
</style>