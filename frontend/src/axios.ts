import axios from "axios";
import { useAuthStore } from "./store/auth";
import router from "./router";

axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true; // ⭐ IMPORTANT

const axiosInstance = axios.create({
    baseURL: "http://localhost:8000/api",
    withCredentials: true,
    headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
    }
});

export const getCsrfCookie = async () => {
    return axios.get("http://localhost:8000/sanctum/csrf-cookie", {
        withCredentials: true
    });
};

// ⭐ Add Token Automatically
axiosInstance.interceptors.request.use(config => {

 const token = localStorage.getItem("token");

 if (token) {
   config.headers.Authorization = `Bearer ${token}`;
 }

 return config;
});


// axiosInstance.interceptors.response.use((response)=> {
//         return response;
//     },
//     async(error) => {
//         const auth = useAuthStore();
//         switch(error.response.status){
//             case 401:
//                 auth.cleanState();
//                 router.push('/login');
//                 break;
//             case 404:
//                 router.push('/404');
//                 break;
//             case 419:
//                 auth.cleanState();
//                 router.push('/login');
//                 break;
//             // case 500:
//             //     router.push('/500');
//             //     break;
//         }

//         return Promise.reject(error);
//     }
// );




export default axiosInstance;