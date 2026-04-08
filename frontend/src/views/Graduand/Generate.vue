<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useGraduandStore } from '@/store/graduand'
import { useToast } from 'vue-toastification'
import axiosInstance from '@/axios'

const auth = useGraduandStore();
const toast = useToast();
const isSubmitting = ref(false);
const hasInvitation = ref(false);
const backendUrl = import.meta.env.VITE_BACKEND_URL; 

const formKey = ref(0);

interface Invitation {
  id: number;
  reg_no: string;
  fullname: string;
  email: string;
  phonenumber: string | number;
  type: 'graduand' | 'parent';
  graduate_idnumber: string | null;
  pdf: string;
}

const invitationsList = ref<Invitation[]>([]);

const initialValues = ref({
  email: '',
  phonenumber: '',
  parent1_name: '',
  parent1_id: '',
  parent1_phone: '',
  parent2_name: '',
  parent2_id: '',
  parent2_phone: ''
});

const fullName = computed(() => `${auth.user?.first_name || ''} ${auth.user?.last_name || ''}`)

// Helper to open PDF directly from storage
const openPdf = (pdfFileName: string) => {
  // Constructed as BackendURL/storage/Invitations/filename.pdf
  const url = `${backendUrl}/storage/Invitations/${pdfFileName}`;
  window.open(url, '_blank');
};

const submitForm = async (formData: any) => {
  isSubmitting.value = true;
  try {
    await axiosInstance.post('/submit-invitation-details', formData);
    toast.success("Details updated successfully!");
    await checkExistingStatus();
  } catch (error) {
    toast.error("Error updating details.");
  } finally {
    isSubmitting.value = false;
  }
}

const checkExistingStatus = async () => {
  if (!auth.user?.reg_no) return;
  
  try {
    const response = await axiosInstance.get(`/get-invitation-data/${auth.user.reg_no}`);
    const data: Invitation[] = response.data;

    if (Array.isArray(data) && data.length > 0) {
      invitationsList.value = data;
      hasInvitation.value = true;

      const graduand = data.find(item => item.type === 'graduand');
      const parents = data.filter(item => item.type === 'parent');

      const parent1 = (parents[0] || {}) as Partial<Invitation>;
      const parent2 = (parents[1] || {}) as Partial<Invitation>;

      initialValues.value = {
        email: graduand?.email || '',
        phonenumber: String(graduand?.phonenumber || ''),
        parent1_name: parent1?.fullname || '',
        parent1_id: parent1?.graduate_idnumber || '',
        parent1_phone: String(parent1?.phonenumber || ''),
        parent2_name: parent2?.fullname || '',
        parent2_id: parent2?.graduate_idnumber || '',
        parent2_phone: String(parent2?.phonenumber || ''),
      };

      formKey.value++;
    }
  } catch (error) {
    console.error("No existing data found.");
  }
}

onMounted(async () => { 
  await auth.getUser();
  await checkExistingStatus();
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-slate-800 p-6 lg:p-10">
    <div class="max-w-4xl mx-auto">
      
      <div class="flex justify-between items-center mb-8 border-b pb-4">
        <div>
          <h1 class="text-2xl font-bold">Invitation Details</h1>
          <p class="text-sm text-gray-500">Update your details and generate guest passes</p>
        </div>
        <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">✓ ELIGIBLE</span>
      </div>

      <FormKit 
        :key="formKey"
        type="form" 
        @submit="submitForm" 
        :actions="false"
        :value="initialValues"
      >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
          <FormKit type="text" name="reg_no" label="Reg No" :value="auth.user?.reg_no || ''" readonly disabled />
          <FormKit type="text" name="fullname" label="Name" :value="fullName || ''" readonly disabled />
          <FormKit type="text" name="degree" label="Degree" :value="auth.user?.degree || ''" readonly disabled />
          <FormKit type="text" name="scanned_number" label="ID Number" :value="auth.user?.scanned_number || ''" readonly disabled />
        </div>

        <div class="bg-blue-50 p-6 rounded-xl border border-blue-100 grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
          <FormKit type="email" name="email" label="Invitation Delivery Email" validation="required|email" />
          <FormKit type="text" name="phonenumber" label="Phone Number" validation="required" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
          <div class="space-y-4 p-4 border-l-4 border-blue-500 bg-white shadow-sm rounded-r-lg">
            <p class="font-bold text-sm uppercase text-blue-600">First Parent</p>
            <FormKit type="text" name="parent1_name" label="Full Name" validation="required" />
            <FormKit type="text" name="parent1_id" label="ID Number" validation="required" />
            <FormKit type="text" name="parent1_phone" label="Phone Number" validation="required" />
          </div>

          <div class="space-y-4 p-4 border-l-4 border-gray-300 bg-white shadow-sm rounded-r-lg">
            <p class="font-bold text-sm uppercase text-gray-500">Second Parent</p>
            <FormKit type="text" name="parent2_name" label="Full Name" validation="required" />
            <FormKit type="text" name="parent2_id" label="ID Number" validation="required" />
            <FormKit type="text" name="parent2_phone" label="Phone Number" validation="required" />
          </div>
        </div>

        <button 
          type="submit" 
          :disabled="isSubmitting"
          class="w-full py-4 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition disabled:bg-gray-400 shadow-md mb-12"
        >
          {{ isSubmitting ? 'Updating...' : 'Update & Re-generate Invitations' }}
        </button>
      </FormKit>

      <div v-if="hasInvitation && invitationsList.length > 0" class="mt-10 p-8 bg-white rounded-2xl shadow-sm border border-gray-200">
        <h2 class="text-xl font-bold mb-6 text-gray-800 flex items-center">
          <i class="fa-solid fa-cloud-arrow-down mr-3 text-blue-600"></i>
          Your Invitations
        </h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="invite in invitationsList" 
            :key="invite.id" 
            class="group p-5 border border-gray-100 rounded-xl bg-gray-50 hover:bg-blue-50 hover:border-blue-200 transition-all duration-300 flex flex-col items-center text-center"
          >
            <div class="mb-3 p-3 bg-white rounded-full shadow-sm group-hover:scale-110 transition-transform">
              <i class="fa-solid fa-file-pdf text-red-500 text-2xl"></i>
            </div>
            
            <span class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">
              {{ invite.type }}
            </span>
            
            <p class="text-sm font-bold text-gray-800 mb-4 line-clamp-1">
              {{ invite.fullname }}
            </p>

            <button 
              @click="openPdf(invite.pdf)"
              class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-gray-900 text-white rounded-lg text-xs font-bold hover:bg-blue-600 transition-colors shadow-sm"
            >
              <i class="fa-solid fa-up-right-from-square"></i>
              VIEW / DOWNLOAD
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* Ensures the parent card looks consistent */
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;  
  overflow: hidden;
}
</style>