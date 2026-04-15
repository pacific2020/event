<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useGraduandStore } from '@/store/graduand'
import { useToast } from 'vue-toastification'
import axiosInstance from '@/axios'

const auth = useGraduandStore();
const toast = useToast();
const isSubmitting = ref(false);
const localLoading = ref(false); // ✅ Added to track initial fetch
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
  pdf: string | null;
  city: string | null;
  province: string | null;
  district: string | null;
  sector: string | null;
  cell: string | null;
  village: string | null;
  stay_overnight: string | null;
}

const invitationsList = ref<Invitation[]>([]);

const initialValues = ref({
  email: '',
  phonenumber: '',
  city: '',
  stay: '', 
  parent1_name: '',
  parent1_id: '',
  parent1_phone: '',
  parent1_province: '',
  parent1_district: '',
  parent1_sector: '',
  parent1_cell: '',
  parent1_village: '',
  parent1_stay: '',
  parent2_name: '',
  parent2_id: '',
  parent2_phone: '',
  parent2_province: '',
  parent2_district: '',
  parent2_sector: '',
  parent2_cell: '',
  parent2_village: '',
  parent2_stay: '',
});

const fullName = computed(() => `${auth.user?.first_name || ''} ${auth.user?.last_name || ''}`)

const openPdf = (pdfFileName: string | null) => {
  if (!pdfFileName) return;
  const url = `${backendUrl}/storage/invitations/${pdfFileName}`;
  window.open(url, '_blank');
};

const submitForm = async (formData: any) => {
  isSubmitting.value = true;
  try {
    const response = await axiosInstance.post('/submit-invitation-details', formData);
    toast.success(response.data.message);
    await checkExistingStatus(true); // ✅ Force refresh after submit
  } catch (error: any) {
    if (error.response?.status === 403) {
      toast.warning(error.response.data.message, { timeout: 10000 });
      await checkExistingStatus(true);
    } else {
      toast.error("Error updating details.");
    }
  } finally {
    isSubmitting.value = false;
  }
}


const checkExistingStatus = async (force = false) => {
  if (!auth.user?.reg_no) return;
  
  if (initialValues.value.email && !force) return;

  localLoading.value = true; 
  try {
    const response = await axiosInstance.get(`/get-invitation-data/${auth.user.reg_no}`);
    const data: Invitation[] = response.data;

    if (Array.isArray(data) && data.length > 0) {
      invitationsList.value = data;
      hasInvitation.value = true;

      const graduand = data.find(item => item.type === 'graduand');
      const parents = data.filter(item => item.type === 'parent');

      // ✅ Fix: Type these as Partial<Invitation> so properties like p1.fullname are recognized
      const p1: Partial<Invitation> = parents[0] || {};
      const p2: Partial<Invitation> = parents[1] || {};

      initialValues.value = {
        email: graduand?.email || '',
        phonenumber: String(graduand?.phonenumber || ''),
        city: graduand?.city || '',
        stay: graduand?.stay_overnight || '',
        parent1_name: p1.fullname || '',
        parent1_id: p1.graduate_idnumber || '',
        parent1_phone: String(p1.phonenumber || ''),
        parent1_province: p1.province || '',
        parent1_district: p1.district || '',
        parent1_sector: p1.sector || '',
        parent1_cell: p1.cell || '',
        parent1_village: p1.village || '',
        parent1_stay: p1.stay_overnight || '',
        parent2_name: p2.fullname || '',
        parent2_id: p2.graduate_idnumber || '',
        parent2_phone: String(p2.phonenumber || ''),
        parent2_province: p2.province || '',
        parent2_district: p2.district || '',
        parent2_sector: p2.sector || '',
        parent2_cell: p2.cell || '',
        parent2_village: p2.village || '',
        parent2_stay: p2.stay_overnight || '',
      };

      formKey.value++; 
    }
  } catch (error) {
    console.error("No existing data found.");
  } finally {
    localLoading.value = false;
  }
}

onMounted(async () => { 
  if (!auth.user) await auth.getUser();
  await checkExistingStatus();
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-slate-800 p-6 lg:p-10">
    <div class="max-w-4xl mx-auto">
      
      <div v-if="localLoading" class="animate-pulse space-y-6">
        <div class="h-8 bg-gray-200 rounded w-1/4"></div>
        <div class="grid grid-cols-2 gap-4"><div class="h-12 bg-gray-200 rounded"></div><div class="h-12 bg-gray-200 rounded"></div></div>
        <div class="h-40 bg-gray-200 rounded"></div>
      </div>

      <div v-else>
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
          #default="{ value = {} as any }"
        >
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <FormKit type="text" name="reg_no" label="Reg No" :value="auth.user?.reg_no || ''" readonly disabled />
            <FormKit type="text" name="fullname" label="Name" :value="fullName" readonly disabled />
            <FormKit type="text" name="scanned_number" label="ID Number" :value="auth.user?.scanned_number || ''" readonly disabled />
          </div>
          
          <div class="bg-blue-50 p-6 rounded-xl border border-blue-100 grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <FormKit type="email" name="email" label="Invitation Delivery Email" validation="required|email" />
            <FormKit type="text" name="phonenumber" label="Phone Number" validation="required" />
            <FormKit type="text" name="city" label="Current Residence City" validation="required" />
            <FormKit type="radio" name="stay" label="Stay overnight?" :options="['Yes', 'No']" validation="required" />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
            <div class="space-y-4 p-4 border-l-4 border-blue-500 bg-white shadow-sm rounded-r-lg">
              <p class="font-bold text-sm uppercase text-blue-600">First Parent</p>
              <FormKit type="text" name="parent1_name" label="Full Name" />
              <FormKit type="text" name="parent1_id" label="ID Number" :validation="value?.parent1_name ? 'required' : ''" />
              <FormKit type="text" name="parent1_phone" label="Phone Number" :validation="value?.parent1_name ? 'required' : ''" />
              <FormKit type="text" name="parent1_province" label="Province" :validation="value?.parent1_name ? 'required' : ''" />
              <FormKit type="text" name="parent1_district" label="District" :validation="value?.parent1_name ? 'required' : ''" />
              <FormKit type="text" name="parent1_sector" label="Sector" :validation="value?.parent1_name ? 'required' : ''" />
              <FormKit type="text" name="parent1_cell" label="Cell" :validation="value?.parent1_name ? 'required' : ''" />
              <FormKit type="text" name="parent1_village" label="Village" :validation="value?.parent1_name ? 'required' : ''" />
              <FormKit type="radio" name="parent1_stay" label="Stay overnight?" :options="['Yes', 'No']" :validation="value?.parent1_name ? 'required' : ''" />
            </div>

            <div class="space-y-4 p-4 border-l-4 border-gray-300 bg-white shadow-sm rounded-r-lg">
              <p class="font-bold text-sm uppercase text-gray-500">Second Parent</p>
              <FormKit type="text" name="parent2_name" label="Full Name" />
              <FormKit type="text" name="parent2_id" label="ID Number" :validation="value?.parent2_name ? 'required' : ''" />
              <FormKit type="text" name="parent2_phone" label="Phone Number" :validation="value?.parent2_name ? 'required' : ''" />
              <FormKit type="text" name="parent2_province" label="Province" :validation="value?.parent2_name ? 'required' : ''" />
              <FormKit type="text" name="parent2_district" label="District" :validation="value?.parent2_name ? 'required' : ''" />
              <FormKit type="text" name="parent2_sector" label="Sector" :validation="value?.parent2_name ? 'required' : ''" />
              <FormKit type="text" name="parent2_cell" label="Cell" :validation="value?.parent2_name ? 'required' : ''" />
              <FormKit type="text" name="parent2_village" label="Village" :validation="value?.parent2_name ? 'required' : ''" />
              <FormKit type="radio" name="parent2_stay" label="Stay overnight?" :options="['Yes', 'No']" :validation="value?.parent2_name ? 'required' : ''" />
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
            <div v-for="invite in invitationsList" :key="invite.id" class="group p-5 border border-gray-100 rounded-xl bg-gray-50 hover:bg-blue-50 transition-all duration-300 flex flex-col items-center text-center">
              <div class="mb-3 p-3 bg-white rounded-full shadow-sm group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-file-pdf text-2xl" :class="invite.pdf ? 'text-red-500' : 'text-gray-300'"></i>
              </div>
              <span class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">{{ invite.type }}</span>
              <p class="text-sm font-bold text-gray-800 mb-4 line-clamp-1">{{ invite.fullname }}</p>
              <button @click="openPdf(invite.pdf)" :disabled="!invite.pdf" class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-xs font-bold transition-colors shadow-sm" :class="invite.pdf ? 'bg-gray-900 text-white hover:bg-blue-600 cursor-pointer' : 'bg-gray-200 text-gray-400 cursor-not-allowed'">
                <i class="fa-solid" :class="invite.pdf ? 'fa-up-right-from-square' : 'fa-lock'"></i>
                {{ invite.pdf ? 'VIEW / DOWNLOAD' : 'PENDING GOWN' }}
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>