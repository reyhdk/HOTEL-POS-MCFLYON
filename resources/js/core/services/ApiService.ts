import type { App } from "vue";
import axios from "axios";
import VueAxios from "vue-axios";
import JwtService from "@/core/services/JwtService";
import { useAuthStore } from "@/stores/auth";
import router from "@/router"; // ▼▼▼ [DIBENARKAN] TAMBAHKAN IMPORT INI ▼▼▼

class ApiService {
  public static vueInstance: App;

  public static init(app: App<Element>) {
    ApiService.vueInstance = app;
    ApiService.vueInstance.use(VueAxios, axios);
    ApiService.vueInstance.axios.defaults.baseURL = import.meta.env.VITE_APP_API_URL;

    // --- INTERCEPTOR UNTUK REQUEST ---
    ApiService.vueInstance.axios.interceptors.request.use(
      (config) => {
        const token = JwtService.getToken();

        if (token && !config.url?.includes('/auth/login') && !config.url?.includes('/auth/register')) {
          config.headers["Authorization"] = `Bearer ${token}`;
        }

        return config;
      },
      (error) => Promise.reject(error)
    );

    // --- INTERCEPTOR UNTUK RESPONSE ---
    ApiService.vueInstance.axios.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response && error.response.status === 401) {
          const authStore = useAuthStore();
          // [FIX] 'router' sekarang sudah dikenali setelah di-import
          if (router.currentRoute.value.name !== "sign-in") {
            authStore.logout();
          }
        }
        return Promise.reject(error);
      }
    );
  }

  // ... (sisa metode: query, get, post, dll. tidak berubah) ...
  public static query(resource: string, params: any) {
    return ApiService.vueInstance.axios.get(resource, { params });
  }

  public static get(resource: string, slug = "") {
    const url = slug ? `${resource}/${slug}` : resource;
    return ApiService.vueInstance.axios.get(url);
  }

  public static post(resource: string, params: any) {
    return ApiService.vueInstance.axios.post(`${resource}`, params);
  }

  public static update(resource: string, slug: string, params: any) {
    return ApiService.vueInstance.axios.put(`${resource}/${slug}`, params);
  }

  public static put(resource: string, params: any) {
    return ApiService.vueInstance.axios.put(`${resource}`, params);
  }

  public static patch(resource: string, params: any) {
    return ApiService.vueInstance.axios.patch(resource, params);
  }

  public static delete(resource: string) {
    return ApiService.vueInstance.axios.delete(resource);
  }
}

export default ApiService;