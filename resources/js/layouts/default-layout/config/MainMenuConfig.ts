import type { MenuItem } from "@/layouts/default-layout/config/types";

const MainMenuConfig: MenuItem[] = [
    // =======================================================
    // == GRUP 1: PANEL TAMU (USER) ==
    // =======================================================
    {
        heading: "Panel Tamu",
        route: "/user",
        pages: [
            {
                heading: "Dashboard Tamu",
                route: "/user/dashboard",
                keenthemesIcon: "element-11",
                roles: ["user"],
            },
            {
                sectionTitle: "Layanan Hotel",
                route: "/user/services",
                keenthemesIcon: "shop",
                roles: ["user"],
                sub: [
                    {
                        heading: "Booking Online",
                        route: "/user/booking",
                        keenthemesIcon: "calendar-add",
                        roles: ["user"],
                    },
                    {
                        heading: "Restaurant",
                        route: "/user/food-order",
                        keenthemesIcon: "coffee",
                        roles: ["user"],
                    },
                    {
                        heading: "Room Service",
                        route: "/user/room-service",
                        keenthemesIcon: "profile-circle",
                        roles: ["user"],
                    },
                ],
            },
            {
                sectionTitle: "Riwayat Aktivitas",
                route: "/user/history",
                keenthemesIcon: "time",
                roles: ["user"],
                sub: [
                    {
                        heading: "Riwayat Booking",
                        route: "/user/booking-history",
                        roles: ["user"],
                    },
                    {
                        heading: "Riwayat Pesanan",
                        route: "/user/food-order-history",
                        roles: ["user"],
                    },
                    {
                        heading: "Riwayat Permintaan",
                        route: "/user/request-history",
                        roles: ["user"],
                    },
                    {   heading: "Ktp",
                        route: "/user/ktp", 
                        roles: ["user"] 
                    },
                ],
            },
            {
                heading: "Tagihan & Checkout",
                route: "/user/checkout",
                keenthemesIcon: "exit-right",
                roles: ["user"],
            },
        ],
    },

    // =======================================================
    // == GRUP 2: ADMINISTRATOR UTAMA ==
    // =======================================================
    {
        heading: "Administrator",
        route: "/admin",
        pages: [
            {
                heading: "Dashboard Admin",
                route: "/admin/dashboard",
                name: "view dashboard",
                keenthemesIcon: "element-11",
                roles: ["admin"],
            },
        ],
    },

    // =======================================================
    // == GRUP 3: OPERASIONAL (POS & REQUEST) ==
    // =======================================================
    {
        heading: "Operasional",
        route: "/apps",
        pages: [
            {
                sectionTitle: "Point of Sale",
                route: "/pos",
                name: "pos",
                keenthemesIcon: "basket",
                roles: ["admin"],
                sub: [
                    {
                        heading: "Buat Pesanan Baru",
                        route: "/admin/pos",
                        name: "create pos_orders",
                        roles: ["admin"],
                    },
                    {
                        heading: "Pesanan Masuk",
                        route: "/admin/online-orders",
                        name: "view online_orders",
                        roles: ["admin"],
                    },
                    {
                        heading: "Daftar Tagihan",
                        route: "/admin/payment",
                        name: "manage payments",
                        roles: ["admin"],
                    },
                    {
                        heading: "Folio Kamar",
                        route: "/admin/folio",
                        name: "view folios",
                        roles: ["admin"],
                    },
                ],
            },
            {
                sectionTitle: "Permintaan Layanan",
                route: "/hotel-services",
                name: "manage service_requests",
                keenthemesIcon: "notification-status",
                roles: ["admin"],
                sub: [
                    {
                        heading: "Daftar Permintaan",
                        route: "/admin/service-requests",
                        name: "manage service_requests",
                    },
                ],
            },
        ],
    },

    // =======================================================
    // == GRUP 4: MANAJEMEN DATA (MASTER) ==
    // =======================================================
    {
        heading: "Manajemen Data",
        route: "/master",
        pages: [
            {
                sectionTitle: "Master Data",
                route: "/admin/master",
                name: "master",
                keenthemesIcon: "cube-3",
                roles: ["admin"],
                sub: [
                    {
                        heading: "Data Kamar",
                        route: "/admin/master/rooms",
                        name: "view rooms",
                        roles: ["admin"],
                    },
                    {
                        heading: "Fasilitas Hotel",
                        route: "/admin/master/facilities",
                        name: "view facilities",
                        roles: ["admin"],
                    },
                    {
                        heading: "Data Tamu",
                        route: "/admin/master/guests",
                        name: "view guests",
                        roles: ["admin"],
                    },
                    {
                        heading: "Verifikasi KTP",
                        route: "/admin/master/verification",
                        keenthemesIcon: "shield-tick",
                        roles: ["admin", "receptionist"],
                    },
                    {
                        heading: "Menu Makanan",
                        route: "/admin/master/menus",
                        name: "view menus",
                        roles: ["admin"],
                    },
                    {
                        heading: "Management User",
                        route: "/admin/master/users",
                        name: "view users",
                        roles: ["admin"],
                    },
                    {
                        heading: "Role & Permission",
                        route: "/admin/master/roles",
                        name: "view roles",
                        roles: ["admin"],
                    },
                ],
            },
        ],
    },

    // =======================================================
    // == GRUP 5: LAPORAN & PENGATURAN ==
    // =======================================================
    {
        heading: "System",
        route: "/system",
        pages: [
            {
                sectionTitle: "Laporan",
                route: "/reports",
                name: "reports",
                keenthemesIcon: "chart-simple",
                roles: ["admin"],
                sub: [
                    {
                        heading: "Riwayat Transaksi",
                        route: "/admin/history",
                        name: "view transaction_history",
                        roles: ["admin"],
                    },
                ],
            },
            {
                sectionTitle: "Pengaturan",
                route: "/pengaturan",
                name: "settings",
                keenthemesIcon: "setting-2",
                roles: ["admin"],
                sub: [
                    {
                        heading: "Website Setting",
                        route: "/admin/setting",
                        name: "edit settings",
                        roles: ["admin"],
                    },
                ],
            },
        ],
    },
];

export default MainMenuConfig;
