<template>
  <div>
    <div class="card mb-5">
      <div class="card-body">
        <h1 class="card-title">Dasbor Pengguna</h1>
        <p v-if="store.user" class="fs-5">
          Selamat datang kembali, <strong>{{ store.user?.name }}</strong>!
        </p>
        <p class="text-muted">
          Dari sini Anda dapat melihat riwayat pesanan dan mengelola akun Anda.
        </p>
      </div>
    </div>

    <div class="card bg-light-primary border-primary">
      <div class="card-body d-flex flex-column flex-md-row align-items-center p-10">
        <div class="me-md-10 mb-5 mb-md-0">
          <i class="ki-duotone ki-calendar-8 fs-5x text-primary">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
            <span class="path4"></span>
            <span class="path5"></span>
            <span class="path6"></span>
          </i>
        </div>

        <div class="d-flex flex-column">
          <h2 class="fw-bold">Siap untuk Liburan Anda Berikutnya?</h2>
          <p class="text-gray-700 fs-6">
            Temukan kamar terbaik yang tersedia dan pesan tempat Anda sekarang juga. Prosesnya cepat dan mudah!
          </p>
          
          <router-link :to="'/booking'" class="btn btn-primary mt-3 align-self-start">
            Pesan Kamar Sekarang
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from "@/stores/auth";

// Akses user store
const store = useAuthStore();
</script>