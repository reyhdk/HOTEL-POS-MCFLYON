import type { MenuItem } from "@/layouts/default-layout/config/types";

const MainMenuConfig: Array<MenuItem> = [
  // =======================================================
  // == MENU UNTUK USER (TAMU) ==
  // =======================================================
  {
    pages: [
      {
        heading: "Dashboard",
        route: "/user/dashboard", // PERBAIKAN: Sesuaikan dengan path di router
        keenthemesIcon: "element-11",
        roles: ["user"],
      },
      {
        heading: "Booking Online",
        route: "/user/booking", // PERBAIKAN: Sesuaikan dengan path di router
        keenthemesIcon: "calendar-add",
        roles: ["user"],
      },
      {
        heading: "Riwayat Booking Saya",
        route: "/user/booking-history", // PERBAIKAN: Sesuaikan dengan path di router
        keenthemesIcon: "book-open",
        roles: ["user"],
      },


    ],
  },

  // =======================================================
  // == MENU KHUSUS UNTUK ADMIN ==
  // =======================================================
  {
    pages: [
      {
        heading: "Dashboard Admin",
        route: "/admin/dashboard",
        keenthemesIcon: "element-11",
        roles: ["admin"],
      },
    ],
  },
  {
    heading: "Aplikasi",
    route: "/apps",
    roles: ["admin"],
    pages: [
      {
        sectionTitle: "Point of Sale",
        route: "/pos",
        keenthemesIcon: "basket",
        roles: ["admin"],
        sub: [
          {
            heading: "Buat Pesanan",
            route: "/admin/pos",
            roles: ["admin"],
          },
          {
            heading: "Daftar Tagihan",
            route: "/admin/payment",
            roles: ["admin"],
          },
          {
            heading: "Folio Kamar",
            route: "/admin/folio",
            roles: ["admin"],
          },
        ],
      },
      {
        sectionTitle: "Laporan",
        route: "/reports",
        keenthemesIcon: "chart-simple",
        roles: ["admin"],
        sub: [
          {
            heading: "Riwayat Transaksi",
            route: "/admin/history",
            roles: ["admin"],
          },
        ],
      },
      {
        sectionTitle: "Master",
        route: "/master",
        keenthemesIcon: "cube-3",
        roles: ["admin"],
        sub: [
          {
            heading: "Kamar",
            route: "/admin/master/rooms",
            roles: ["admin"],
          },
           {
            heading: "Fasilitas",
            route: "/admin/master/facilities",
            keenthemesIcon: "element-11", 
            roles: ["admin"],
          },
          {
            heading: "Tamu",
            route: "/admin/master/guests",
            roles: ["admin"],
          },
          {
            heading: "Menu",
            route: "/admin/master/menus",
            roles: ["admin"],
          },
          {
            heading: "Users",
            route: "/admin/master/users",
            roles: ["admin"],
          },
          {
            heading: "Roles",
            route: "/admin/master/roles",
            roles: ["admin"],
          },
        ],
      },
      {
        sectionTitle: "Pengaturan",
        route: "/pengaturan",
        keenthemesIcon: "setting-2",
        roles: ["admin"],
        sub: [
          {
            heading: "Website Setting",
            route: "/admin/setting",
            roles: ["admin"],
          },
        ],
      },
    ],
  },
];

export default MainMenuConfig;