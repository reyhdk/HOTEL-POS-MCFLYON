// resources/js/types/index.d.ts

declare module "@/types" {
  // Definisi untuk Tipe Data Role
  export interface Role {
    id: number | null;
    name: string;
    full_name: string;
    permissions: string[];
  }

  // Definisi untuk Tipe Data User (dari file Index.vue Anda)
  export interface User {
    id: number;
    no: number; // asumsikan 'no' adalah nomor urut
    name: string;
    full_name: string;
  }
}
