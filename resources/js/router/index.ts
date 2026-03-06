import {
    createRouter,
    createWebHistory,
    type RouteRecordRaw,
    RouterView,
} from "vue-router";
import { useAuthStore } from "@/stores/auth";
import JwtService from "@/core/services/JwtService";
import NProgress from "nprogress";
import "nprogress/nprogress.css";

declare module "vue-router" {
    interface RouteMeta {
        pageTitle?: string;
        breadcrumb?: string[];
        middleware?: "auth" | "guest";
        role?: string | string[];
    }
}

const routes: Array<RouteRecordRaw> = [
    // =======================================================
    // ▼▼▼ GRUP RUTE USER (TAMU) ▼▼▼
    // =======================================================
    {
        path: "/user",
        component: () => import("@/layouts/default-layout/DefaultLayout.vue"),
        meta: { middleware: "auth", role: "user" },
        children: [
            {
                path: "dashboard",
                name: "user-dashboard",
                component: () => import("@/pages/user-dashboard/Index.vue"),
                meta: { pageTitle: "Dasbor" },
            },
            {
                path: "profile",
                name: "user-profile",
                component: () => import("@/pages/dashboard/profile/Index.vue"),
                meta: {
                    pageTitle: "Profil Saya",
                    breadcrumbs: ["Dashboard", "Profil"],
                },
            },
            {
                path: "booking",
                name: "user-booking",
                component: () =>
                    import("@/pages/user-dashboard/bookings/Index.vue"),
                meta: {
                    pageTitle: "Booking Online",
                    breadcrumb: ["Dashboard", "Booking"],
                },
            },
            {
                path: "booking-history",
                name: "booking-history",
                component: () =>
                    import("@/pages/user-dashboard/booking-history/Index.vue"),
                meta: { pageTitle: "Riwayat Booking" },
            },
            {
                path: "food-order",
                name: "user-food-order",
                component: () =>
                    import("@/pages/user-dashboard/food-order/Index.vue"),
                meta: {
                    pageTitle: "Pesan Makanan",
                    breadcrumbs: ["Dashboard", "Pesan Makanan"],
                },
            },
            {
                path: "food-order-history",
                name: "user-food-order-history",
                component: () =>
                    import(
                        "@/pages/user-dashboard/food-order-history/Index.vue"
                    ),
                meta: { pageTitle: "Riwayat Pesanan Makanan" },
            },
            {
                path: "room-service",
                name: "user-room-service",
                component: () =>
                    import("@/pages/user-dashboard/room-service/Index.vue"),
                meta: { pageTitle: "Layanan Kamar" },
            },
            {
                path: "laundry-order",
                name: "user-laundry-order",
                component: () =>
                    import("@/pages/user-dashboard/laundry-order/LaundryOrder.vue"),
                meta: { pageTitle: "Pesan Laundry" },
            },
            {
                path: "request-history",
                name: "user-request-history",
                component: () =>
                    import("@/pages/user-dashboard/request-history/Index.vue"),
                meta: { pageTitle: "Riwayat Permintaan" },
            },
            {
                path: "checkout",
                name: "user-checkout",
                component: () =>
                    import("@/pages/user-dashboard/checkout/Index.vue"),
                meta: { pageTitle: "Tagihan & Checkout" },
            },
            {
                path: "payment/:orderId",
                name: "user-payment",
                component: () =>
                    import("@/pages/user-dashboard/payment-page/Index.vue"),
                meta: {
                    pageTitle: "Pembayaran",
                    breadcrumbs: ["Dashboard", "Pesan Makanan", "Pembayaran"],
                },
            },
            {
                path: "ktp",
                name: "user-ktp",
                component: () =>
                    import("@/pages/user-dashboard/ktp/Index.vue"),
                meta: { 
                    pageTitle: "Ktp",
                    breadcrumbs: ["Dashboard", "Ktp"],
                },
            },      
        ],
    },

    // =======================================================
    // ▼▼▼ GRUP RUTE ADMIN & STAF (OTOMATIS) ▼▼▼
    // =======================================================
    {
        path: "/admin",
        component: () => import("@/layouts/default-layout/DefaultLayout.vue"),
        meta: {
            middleware: "auth",
            // ✅ KITA HAPUS DAFTAR MANUALNYA
            // Role dicek secara dinamis di bawah: Bukan 'user' = Staff.
        },
        children: [
            {
                path: "profile",
                name: "admin-profile",
                component: () => import("@/pages/dashboard/profile/Index.vue"),
                meta: {
                    pageTitle: "Pengaturan Akun",
                    breadcrumbs: ["Admin", "Profile"],
                },
            },
            {
                path: "dashboard",
                name: "admin-dashboard",
                component: () => import("@/pages/dashboard/Index.vue"),
                meta: { pageTitle: "Dasbor Admin" },
            },
            {
                path: "pos",
                name: "admin-pos",
                component: () => import("@/pages/dashboard/pos/Index.vue"),
                meta: { pageTitle: "Point of Sale" },
            },
            {
                path: "kitchen",
                name: "admin-kitchen",
                component: () =>
                    import("@/pages/dashboard/kitchen/Index.vue"),
                meta: { pageTitle: "Kitchen" },
            },
            {
                path: "service-requests",
                name: "admin-service-requests",
                component: () =>
                    import("@/pages/dashboard/roomService/service-requests/Index.vue"),
                meta: { pageTitle: "Permintaan Layanan Pelanggan" },
            },
            {
                path: "cleaning",
                name: "admin-cleaning",
                component: () =>
                    import("@/pages/dashboard/roomService/cleaning/cleaning.vue"),
                meta: { pageTitle: "Kebersihan Kamar" },
            },
            {
                path: "setting-housekeeping",
                name: "admin-setting-housekeeping",
                component: () =>
                    import("@/pages/dashboard/roomService/settinghousekeeper/housekeepingSetting.vue"),
                meta: { pageTitle: "Pengaturan Housekeeping" },
            },
            {
                path: "service-settings",
                name: "admin-service-settings-housekeeping",
                component: () =>
                    import("@/pages/dashboard/roomService/serviceSetting/serviceSetting.vue"),
                meta: { pageTitle: "Pengaturan Layanan" },
            },
            {
                path: "receptionist/rooms",
                name: "admin-master-rooms",
                component: () =>
                    import("@/pages/dashboard/master/rooms/Index.vue"),
                meta: { pageTitle: "Kamar" },
            },
            {
                path: "receptionist/verification",
                name: "admin-verification",
                component: () =>
                    import("@/pages/dashboard/master/verification/Index.vue"),
                meta: {
                    pageTitle: "Verifikasi KTP",
                    breadcrumbs: ["Master Data", "Verifikasi KTP"],
                },
            },
            {
                path: "laundry",
                name: "admin-laundry",
                component: () =>
                    import("@/pages/dashboard/laundry/SettingLaundry.vue"),
                meta: { pageTitle: "Laundry" },
            },
            {
                path: "user-request-laundry",
                name: "admin-user-request-laundry",
                component: () =>
                    import("@/pages/dashboard/laundry/UserRequestLaundry.vue"),
                meta: { pageTitle: "Permintaan Laundry" },
            },
            {
                path: "master/warehouse",
                name: "admin-master-warehouse",
                component: () =>
                    import("@/pages/dashboard/master/warehouse/Index.vue"),
                meta: { pageTitle: "Gudang" },
            },
            {
                path: "master/facilities",
                name: "admin-master-facilities",
                component: () =>
                    import("@/pages/dashboard/master/facilities/Index.vue"),
                meta: { pageTitle: "Fasilitas" },
            },
            {
                path: "payment",
                name: "admin-payment",
                component: () => import("@/pages/dashboard/payment/Index.vue"),
                meta: { pageTitle: "Daftar Tagihan" },
            },
            {
                path: "folio",
                name: "admin-folio",
                component: () => import("@/pages/dashboard/folio/Index.vue"),
                meta: { pageTitle: "Folio Kamar" },
            },
            {
                path: "reports/transaction-history",
                name: "admin-reports",
                component: () => import("@/pages/dashboard/reports/Transaction-History.vue"),
                meta: { pageTitle: "Laporan" },
            },
            {
                path: "receptionist/guests",
                name: "admin-master-guests",
                component: () =>
                    import("@/pages/dashboard/master/guests/Index.vue"),
                meta: { pageTitle: "Tamu" },
            },
            {
                path: "menus",
                name: "admin-master-menus",
                component: () =>
                    import("@/pages/dashboard/master/menus/Index.vue"),
                meta: { pageTitle: "Menu" },
            },
            {
                path: "master/users",
                name: "admin-master-users",
                component: () =>
                    import("@/pages/dashboard/master/users/Index.vue"),
                meta: { pageTitle: "Users" },
            },
            {
                path: "master/roles",
                name: "admin-master-roles",
                component: () =>
                    import("@/pages/dashboard/master/users/roles/Index.vue"),
                meta: { pageTitle: "Roles" },
            },
            {
                path: "setting",
                name: "admin-setting",
                component: () => import("@/pages/dashboard/setting/Index.vue"),
                meta: { pageTitle: "Website Setting" },
            },
        ],
    },

    // =======================================================
    // ▼▼▼ GRUP RUTE AUTENTIKASI ▼▼▼
    // =======================================================
    {
        path: "/auth",
        component: RouterView,
        meta: { middleware: "guest" },
        children: [
            {
                path: "sign-in",
                name: "sign-in",
                component: () => import("@/pages/auth/sign-in/Index.vue"),
                meta: { pageTitle: "Sign In" },
            },
            {
                path: "sign-up",
                name: "sign-up",
                component: () => import("@/pages/auth/sign-up/Index.vue"),
                meta: { pageTitle: "Sign Up" },
            },
        ],
    },

    // =======================================================
    // ▼▼▼ LANDING PAGE ▼▼▼
    // =======================================================
    {
        path: "/",
        name: "landing-page",
        component: () => import("@/pages/landing-page/index.vue"),
        meta: {
            pageTitle: "Welcome to McFlyon Hotel",
        },
    },
    {
        path: "/:pathMatch(.*)*",
        name: "404",
        component: () => import("@/pages/errors/Error404.vue"),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0, left: 0, behavior: "smooth" };
    },
});

NProgress.configure({ showSpinner: false });

router.beforeEach(async (to, from, next) => {
    NProgress.start();

    const authStore = useAuthStore();
    const token = JwtService.getToken();

    // 1. Verifikasi auth jika ada token tapi user belum loaded
    if (token && !authStore.isUserLoaded) {
        try {
            await authStore.verifyAuth();
        } catch (error) {
            console.error("Auth verification failed");
        }
    }

    const isAuthenticated = authStore.isAuthenticated;
    const userRole = authStore.userRole;

    // 2. Logika untuk halaman guest (login, register)
    if (to.meta.middleware === "guest") {
        if (isAuthenticated) {
            // Redirect otomatis ke dashboard yang sesuai jika sudah login
            return (userRole === "user") 
                ? next({ name: "user-dashboard" }) 
                : next({ name: "admin-dashboard" });
        }
        return next();
    }

    // 3. Logika untuk halaman yang butuh login
    if (to.meta.middleware === "auth") {
        if (!isAuthenticated) {
            return next({ name: "sign-in" });
        }

        /**
         * ✅ LOGIKA DINAMIS AREA:
         * Memastikan User biasa tidak masuk area Admin, 
         * dan Staff tidak masuk area User Dashboard.
         */
        
        // Cek Area Admin
        if (to.path.startsWith("/admin")) {
            if (userRole === "user") {
                console.warn("🚫 Tamu dilarang masuk area admin");
                return next({ name: "404" });
            }
        }

        // Cek Area User
        if (to.path.startsWith("/user")) {
            if (userRole !== "user") {
                console.warn("🏢 Staff dilarang masuk area tamu, arahkan ke admin dashboard");
                return next({ name: "admin-dashboard" });
            }
        }

        /**
         * ✅ KHUSUS: Tetap izinkan meta.role spesifik jika ada (untuk fitur super sensitif)
         */
        if (to.meta.role) {
            const allowed = Array.isArray(to.meta.role) ? to.meta.role : [to.meta.role];
            if (!allowed.includes(userRole)) {
                return next({ name: "404" });
            }
        }
    }

    return next();
});

router.afterEach(() => {
    NProgress.done();
});

export default router;