import type { App } from "vue";
import axios from "axios";
import VueAxios from "vue-axios";
import JwtService from "@/core/services/JwtService";
import { useAuthStore } from "@/stores/auth";
import router from "@/router";

class ApiService {
    public static vueInstance: App;

    public static init(app: App<Element>) {
        ApiService.vueInstance = app;
        ApiService.vueInstance.use(VueAxios, axios);
        ApiService.vueInstance.axios.defaults.baseURL =
            import.meta.env.VITE_APP_API_URL;

        // --- INTERCEPTOR REQUEST ---
        ApiService.vueInstance.axios.interceptors.request.use(
            (config) => {
                const token = JwtService.getToken();
                if (
                    token &&
                    !config.url?.includes("/auth/login") &&
                    !config.url?.includes("/auth/register")
                ) {
                    config.headers["Authorization"] = `Bearer ${token}`;
                }
                return config;
            },
            (error) => Promise.reject(error)
        );

        // --- INTERCEPTOR RESPONSE ---
        ApiService.vueInstance.axios.interceptors.response.use(
            (response) => response,
            (error) => {
                if (error.response && error.response.status === 401) {
                    const authStore = useAuthStore();
                    if (router.currentRoute.value.name !== "sign-in") {
                        authStore.logout();
                    }
                }
                return Promise.reject(error);
            }
        );
    }

    public static query(resource: string, params: any) {
        return ApiService.vueInstance.axios.get(resource, { params });
    }

    public static get(resource: string, slug = "") {
        const url = slug ? `${resource}/${slug}` : resource;
        return ApiService.vueInstance.axios.get(url);
    }

    // ✅ PERBAIKAN: Tambahkan parameter 'config' (AxiosRequestConfig)
    public static post(resource: string, params: any, config?: any) {
        return ApiService.vueInstance.axios.post(`${resource}`, params, config);
    }

    // ✅ PERBAIKAN: Tambahkan parameter 'config'
    public static update(
        resource: string,
        slug: string,
        params: any,
        config?: any
    ) {
        return ApiService.vueInstance.axios.put(
            `${resource}/${slug}`,
            params,
            config
        );
    }

    // ✅ PERBAIKAN: Tambahkan parameter 'config'
    public static put(resource: string, params: any, config?: any) {
        return ApiService.vueInstance.axios.put(`${resource}`, params, config);
    }

    public static patch(resource: string, params: any, config?: any) {
        return ApiService.vueInstance.axios.patch(resource, params, config);
    }

    public static delete(resource: string) {
        return ApiService.vueInstance.axios.delete(resource);
    }
}

export default ApiService;
