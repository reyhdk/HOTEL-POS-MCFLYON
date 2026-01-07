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

        console.log(
            "🔧 ApiService initialized with baseURL:",
            import.meta.env.VITE_APP_API_URL
        );

        // --- INTERCEPTOR REQUEST ---
        ApiService.vueInstance.axios.interceptors.request.use(
            (config) => {
                const token = JwtService.getToken();

                // ✅ Debug: Log setiap request
                console.log("📤 API Request:", {
                    url: config.url,
                    method: config.method?.toUpperCase(),
                    hasToken: !!token,
                    tokenPreview: token ? token.substring(0, 20) + "..." : null,
                });

                // ✅ Attach token KECUALI untuk endpoint login/register
                if (
                    token &&
                    !config.url?.includes("/auth/login") &&
                    !config.url?.includes("/auth/register")
                ) {
                    config.headers["Authorization"] = `Bearer ${token}`;
                    console.log("✅ Authorization header attached");
                } else if (!token && !config.url?.includes("/auth/")) {
                    console.warn(
                        "⚠️ No token found for protected endpoint:",
                        config.url
                    );
                }

                return config;
            },
            (error) => {
                console.error("❌ Request Interceptor Error:", error);
                return Promise.reject(error);
            }
        );

        // --- INTERCEPTOR RESPONSE ---
        ApiService.vueInstance.axios.interceptors.response.use(
            (response) => {
                console.log("📥 API Response:", {
                    url: response.config.url,
                    status: response.status,
                    statusText: response.statusText,
                });
                return response;
            },
            (error) => {
                const status = error.response?.status;
                const url = error.config?.url;
                const message = error.response?.data?.message;

                console.error("❌ API Error:", {
                    url,
                    status,
                    message,
                    fullError: error.response?.data,
                });

                // ✅ Handle 401 Unauthorized
                if (status === 401) {
                    const currentRoute = router.currentRoute.value.name;

                    console.warn("🚫 401 Unauthorized:", {
                        url,
                        currentRoute,
                        isLoginRoute: currentRoute === "sign-in",
                    });

                    // ⚠️ JANGAN logout jika sedang di halaman login/register
                    // atau jika request adalah login/register yang gagal
                    const isAuthEndpoint =
                        url?.includes("/auth/login") ||
                        url?.includes("/auth/register");
                    const isAuthRoute =
                        currentRoute === "sign-in" ||
                        currentRoute === "sign-up";

                    if (!isAuthEndpoint && !isAuthRoute) {
                        console.warn("🔄 Triggering logout due to 401...");

                        // Gunakan setTimeout untuk menghindari race condition
                        setTimeout(() => {
                            const authStore = useAuthStore();
                            authStore.logout();
                        }, 100);
                    } else {
                        console.log(
                            "ℹ️ 401 on auth endpoint, not triggering logout"
                        );
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

    public static post(resource: string, params: any, config?: any) {
        return ApiService.vueInstance.axios.post(`${resource}`, params, config);
    }

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
