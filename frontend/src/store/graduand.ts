import { defineStore } from "pinia"
import { ref, reactive, watch } from "vue"
import type { GraduationList } from "@/types/index"
import router from "@/router"
import axiosInstance from "@/axios"
import { useToast } from 'vue-toastification'
import { useQuery } from '@tanstack/vue-query'

const toast = useToast();

export const useGraduandStore = defineStore("graduand", () => {
    // --- State ---
    const savedData = localStorage.getItem('graduand_data');
    const user = ref<GraduationList | null>(savedData ? JSON.parse(savedData) : null)
    const gownData = ref<any>(null) 
    const pickupData = ref<any>(null) // New: Stores campus selection
    const isLoggedIn = ref(!!savedData)
    const loading = ref(false)

    // --- Actions ---

    const setUser = (userData: GraduationList) => {
        user.value = userData
        isLoggedIn.value = true
        localStorage.setItem('graduand_data', JSON.stringify(userData));
    }

    const getUser = async () => {
        if (!user.value) loading.value = true
        try {
            const { data } = await axiosInstance.get('/graduandUser')
            const graduandData = data.graduand || data; 
            setUser(graduandData)
        } catch (error) {
            cleanState()
            router.push('/apply')
        } finally {
            loading.value = false
        }
    }

    /**
     * ✅ NEW: Get Pickup Location with "Gatekeeper" logic
     * This prevents redundant DB requests if pickupData is already set.
     */
    const getPickupLocation = async (force = false) => {
        if (!user.value?.reg_no) return;
        if (pickupData.value && !force) return; // Stop redundant requests

        try {
            const { data } = await axiosInstance.get(`/view-gown-pickup/${user.value.reg_no}`);
            pickupData.value = data;
        } catch (e) {
            pickupData.value = null;
        }
    }

    // --- TanStack Query for Gown Status (Live Polling) ---
    const { data: polledGown, isFetching: isGownLoading } = useQuery({
        queryKey: ['gownStatus', () => user.value?.reg_no],
        queryFn: async () => {
            if (!user.value?.reg_no) return null;
            try {
                const response = await axiosInstance.get(`/view-gown/${user.value.reg_no}`);
                if (!response.data || (Array.isArray(response.data) && response.data.length === 0)) {
                    return null;
                }
                return Array.isArray(response.data) ? response.data[0] : response.data;
            } catch (error: any) {
                if (error.response?.status === 404) return null;
                throw error;
            }
        },
        enabled: () => !!user.value?.reg_no && isLoggedIn.value,
        refetchInterval: 5000, // Increased slightly for better performance
    });

    watch(polledGown, (newValue) => {
        gownData.value = newValue || null;
    }, { immediate: true });

    const cleanState = () => {
        user.value = null;
        gownData.value = null;
        pickupData.value = null;
        isLoggedIn.value = false;
        localStorage.removeItem('token');
        localStorage.removeItem('graduand_data');
    }

    const logout = async () => {
        try {
            await axiosInstance.post('/GraduandLogout')
        } catch (error) {
            console.error("Logout Error:", error)
        } finally {
            cleanState()
            router.push('/apply')
        }
    }

    const handleSubmit = async (formData: any) => {
        loading.value = true;
        try {
            const { data } = await axiosInstance.post('/graduand/login', formData);

            if (data.status === 'success') {
                toast.success('Welcome!');
                localStorage.setItem('token', data.token);
                axiosInstance.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;
                setUser(data.graduand);
                router.push('/Graduand/dashboard');
            }
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Login failed');
        } finally {
            loading.value = false;
        }
    };

    // --- Reactive Forms ---
    const form = reactive({
        reg_no: '',
        scanned_number: ''
    })

    const errors = reactive({
        reg_no: [] as string[],
        scanned_number: [] as string[]
    })

    return {
        user,
        gownData,
        pickupData, // Exported
        isGownLoading,
        isLoggedIn,
        loading,
        setUser,
        logout,
        getUser,
        getPickupLocation, // Exported
        handleSubmit,
        form,
        errors,
        cleanState
    }
})