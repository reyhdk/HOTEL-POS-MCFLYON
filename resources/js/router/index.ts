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
        ],
    },

    // =======================================================
    // ▼▼▼ GRUP RUTE ADMIN & STAF LAINNYA ▼▼▼
    // =======================================================
    {
        path: "/admin",
        component: () => import("@/layouts/default-layout/DefaultLayout.vue"),
        meta: {
            middleware: "auth",
            role: ["admin", "receptionist", "chef", "cleaning-service"],
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
                path: "online-orders",
                name: "admin-online-orders",
                component: () =>
                    import("@/pages/dashboard/online-orders/Index.vue"),
                meta: { pageTitle: "Pesanan Online" },
            },
            {
                path: "service-requests",
                name: "admin-service-requests",
                component: () =>
                    import("@/pages/dashboard/service-requests/Index.vue"),
                meta: { pageTitle: "Permintaan Layanan" },
            },
            {
                path: "master/rooms",
                name: "admin-master-rooms",
                component: () =>
                    import("@/pages/dashboard/master/rooms/Index.vue"),
                meta: { pageTitle: "Kamar" },
            },
            {
                path: "master/verification",
                name: "admin-verification",
                component: () =>
                    import("@/pages/dashboard/master/verification/Index.vue"),
                meta: {
                    pageTitle: "Verifikasi KTP",
                    breadcrumbs: ["Master Data", "Verifikasi KTP"],
                    role: ["admin", "receptionist"],
                },
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
                path: "history",
                name: "admin-history",
                component: () => import("@/pages/dashboard/history/Index.vue"),
                meta: { pageTitle: "Riwayat Transaksi" },
            },
            {
                path: "master/guests",
                name: "admin-master-guests",
                component: () =>
                    import("@/pages/dashboard/master/guests/Index.vue"),
                meta: { pageTitle: "Tamu" },
            },
            {
                path: "master/menus",
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

    console.log("🧭 Navigation:", {
        from: from.name,
        to: to.name,
        meta: to.meta,
    });

    document.title = `${to.meta.pageTitle || "Welcome"} - ${
        import.meta.env.VITE_APP_NAME
    }`;

    const authStore = useAuthStore();
    const token = JwtService.getToken();

    console.log("🔑 Auth State:", {
        hasToken: !!token,
        isAuthenticated: authStore.isAuthenticated,
        isUserLoaded: authStore.isUserLoaded,
        userRole: authStore.userRole,
    });

    // ✅ Verifikasi auth jika ada token tapi user belum loaded
    if (token && !authStore.isUserLoaded) {
        console.log("🔄 Verifying auth...");
        try {
            await authStore.verifyAuth();
            console.log("✅ Auth verified:", {
                user: authStore.user?.name,
                role: authStore.userRole,
            });
        } catch (error) {
            console.error("❌ Auth verification failed:", error);
        }
    }

    const isAuthenticated = authStore.isAuthenticated;
    const userRole = authStore.userRole;
    const staffRoles = ["admin", "receptionist", "chef", "cleaning-service"];

    // 1. Logika untuk halaman guest (login, register)
    if (to.meta.middleware === "guest") {
        console.log("👤 Guest route detected");

        if (isAuthenticated) {
            console.log(
                "✅ User is authenticated, redirecting to dashboard..."
            );

            if (staffRoles.includes(userRole)) {
                console.log("🏢 Redirecting to admin dashboard");
                return next({ name: "admin-dashboard" });
            }

            console.log("👥 Redirecting to user dashboard");
            return next({ name: "user-dashboard" });
        }

        console.log("➡️ Allowing access to guest route");
        return next();
    }

    // 2. Logika untuk halaman yang butuh login
    if (to.meta.middleware === "auth") {
        console.log("🔒 Protected route detected");

        if (!isAuthenticated) {
            console.warn("🚫 User not authenticated, redirecting to login");
            return next({ name: "sign-in" });
        }

        // Cek otorisasi berdasarkan role
        if (to.meta.role) {
            const requiredRoles = Array.isArray(to.meta.role)
                ? to.meta.role
                : [to.meta.role];

            console.log("🎭 Checking role authorization:", {
                userRole,
                requiredRoles,
                hasAccess: requiredRoles.includes(userRole),
            });

            if (!requiredRoles.includes(userRole)) {
                console.warn("🚫 User role not authorized, redirecting to 404");
                return next({ name: "404" });
            }
        }

        console.log("✅ Authorization passed");
    }

    // 3. Jika semua kondisi lolos, izinkan akses
    console.log("✅ Navigation allowed");
    return next();
});

router.afterEach(() => {
    NProgress.done();
});

export default router;
