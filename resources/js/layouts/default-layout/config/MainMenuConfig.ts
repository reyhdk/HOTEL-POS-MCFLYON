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
                name: "view dashboard", // Pastikan Role Chef punya permission 'view dashboard' agar ini muncul
                keenthemesIcon: "element-11",
                // roles: ["admin"], // Dihapus agar logic permission 'name' yang bekerja
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
                // name: "pos", <--- DIHAPUS: Agar Staff Kasir/Chef bisa lihat jika punya akses submenu
                keenthemesIcon: "basket",
                // roles: ["admin"], <--- DIHAPUS: Tidak relevan untuk staff
                sub: [
                    {
                        heading: "Buat Pesanan Baru",
                        route: "/admin/pos",
                        name: "create pos_orders",
                    },
                    {
                        heading: "Pesanan Masuk",
                        route: "/admin/online-orders",
                        name: "view online_orders",
                    },
                    {
                        heading: "Daftar Tagihan",
                        route: "/admin/payment",
                        name: "manage payments",
                    },
                    {
                        heading: "Folio Kamar",
                        route: "/admin/folio",
                        name: "view folios",
                    },
                ],
            },
            {
                sectionTitle: "Permintaan Layanan",
                route: "/hotel-services",
                // name: "manage service_requests", <--- DIHAPUS: Agar staff HK bisa lihat tanpa permission parent
                keenthemesIcon: "notification-status",
                // roles: ["admin"],
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
                // name: "master", <--- DIHAPUS: INI PENYEBAB UTAMA CHEF TIDAK BISA LIHAT MENU MAKANAN
                keenthemesIcon: "cube-3",
                // roles: ["admin"], <--- DIHAPUS
                sub: [
                    {
                        heading: "Data Kamar",
                        route: "/admin/master/rooms",
                        name: "view rooms",
                    },
                    {
                        heading: "Fasilitas Hotel",
                        route: "/admin/master/facilities",
                        name: "view facilities",
                    },
                    {
                        heading: "Data Tamu",
                        route: "/admin/master/guests",
                        name: "view guests",
                    },
                    {
                        heading: "Verifikasi KTP",
                        route: "/admin/master/verification",
                        keenthemesIcon: "shield-tick",
                        name: "view guests", // Asumsi pakai permission view guests atau admin
                    },
                    {
                        heading: "Menu Makanan",
                        route: "/admin/master/menus",
                        name: "view menus", // Chef harus punya permission ini
                    },
                    {
                        heading: "Management User",
                        route: "/admin/master/users",
                        name: "view users",
                    },
                    {
                        heading: "Role & Permission",
                        route: "/admin/master/roles",
                        name: "view roles",
                    },
                    {
                        heading: "Gudang",
                        route: "/admin/master/warehouse",
                        name: "view warehouse",
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
                // name: "reports", <--- DIHAPUS
                keenthemesIcon: "chart-simple",
                // roles: ["admin"],
                sub: [
                    {
                        heading: "Riwayat Transaksi",
                        route: "/admin/history",
                        name: "view transaction_history",
                    },
                ],
            },
            {
                sectionTitle: "Pengaturan",
                route: "/pengaturan",
                // name: "settings", <--- DIHAPUS
                keenthemesIcon: "setting-2",
                // roles: ["admin"],
                sub: [
                    {
                        heading: "Website Setting",
                        route: "/admin/setting",
                        name: "edit settings",
                    },
                ],
            },
        ],
    },
];

export default MainMenuConfig;