// File: resources/js/types/user.ts

// Definisikan tipe data untuk Role terlebih dahulu
export interface Role {
  id: number;
  name: string;
  full_name?: string;
}

// Perbarui interface User untuk menggunakan interface Role
export interface User {
  id: number; // Tipe data number lebih umum daripada BigInteger di frontend
  uuid: string;
  name: string;
  email: string;
  password?: string;
  phone?: string | number | null; // Lebih fleksibel
  photo?: string;
  permission: Array<string>;
  role?: Role; 
}