import type { App } from "vue";
import axios from "axios";
import VueAxios from "vue-axios";
import JwtService from "@/core/services/JwtService";
import { useAuthStore } from "@/stores/auth";

class ApiService {
  public static vueInstance: App;

  public static init(app: App<Element>) {
    ApiService.vueInstance = app;
    ApiService.vueInstance.use(VueAxios, axios);
    ApiService.vueInstance.axios.defaults.baseURL = import.meta.env.VITE_APP_API_URL;

    // --- INTERCEPTOR UNTUK REQUEST ---
    // Menempelkan token ke setiap request yang memerlukan autentikasi
    ApiService.vueInstance.axios.interceptors.request.use(
      (config) => {
        const token = JwtService.getToken();
        if (token) {
          config.headers["Authorization"] = `Bearer ${token}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // --- INTERCEPTOR UNTUK RESPONSE (PENYESUAIAN PENTING) ---
    // Menangani error 401 (Unauthorized) secara global
    ApiService.vueInstance.axios.interceptors.response.use(
      (response) => response, // Jika berhasil, teruskan response
      (error) => {
        // Jika error adalah 401 (token tidak valid/kedaluwarsa)
        if (error.response && error.response.status === 401) {
          const authStore = useAuthStore();
          authStore.logout(); // Panggil aksi logout dari Pinia store
        }
        return Promise.reject(error);
      }
    );
  }

  // Metode untuk melakukan query (GET dengan parameter)
  public static query(resource: string, params: any) {
    return ApiService.vueInstance.axios.get(resource, { params });
  }

  // Metode untuk GET request standar
  public static get(resource: string, slug = "") {
    const url = slug ? `${resource}/${slug}` : resource;
    return ApiService.vueInstance.axios.get(url);
  }

  // Metode untuk POST request
  public static post(resource: string, params: any) {
    return ApiService.vueInstance.axios.post(`${resource}`, params);
  }

  // Metode untuk UPDATE request (menggunakan slug)
  public static update(resource: string, slug: string, params: any) {
    return ApiService.vueInstance.axios.put(`${resource}/${slug}`, params);
  }

  // Metode untuk PUT request standar
  public static put(resource: string, params: any) {
    return ApiService.vueInstance.axios.put(`${resource}`, params);
  }

  // Metode untuk DELETE request
  public static delete(resource: string) {
    return ApiService.vueInstance.axios.delete(resource);
  }
}

export default ApiService;