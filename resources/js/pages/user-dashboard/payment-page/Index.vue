<template>
  <div class="d-flex flex-column flex-center text-center h-100">
    <div v-if="isLoading">
      <h1 class="mb-4">Mempersiapkan Pembayaran...</h1>
      <p class="fs-4 text-muted mb-5">Harap tunggu sebentar, kami sedang menghubungkan Anda ke gerbang pembayaran.</p>
      <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
    </div>

    <div v-if="error">
      <i class="ki-duotone ki-shield-cross fs-5x text-danger mb-5">
        <span class="path1"></span><span class="path2"></span>
      </i>
      <h1 class="mb-4">Gagal Memuat Pembayaran</h1>
      <p class="fs-4 text-muted mb-5">{{ error }}</p>
      <router-link to="/user/dashboard" class="btn btn-primary">Kembali ke Dashboard</router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ApiService from '@/core/services/ApiService';
import Swal from 'sweetalert2';

// Deklarasikan window.snap agar TypeScript tidak error
declare global {
  interface Window {
    snap: any;
  }
}

const route = useRoute();
const router = useRouter();
const isLoading = ref(true);
const error = ref<string | null>(null);

const processPayment = async () => {
  const orderId = route.params.orderId;

  if (!orderId) {
    error.value = "ID Pesanan tidak ditemukan.";
    isLoading.value = false;
    return;
  }

  try {
    // 1. Minta Snap Token dari backend
    const response = await ApiService.post('/midtrans/create-transaction', { order_id: orderId });
    const snapToken = response.data.snap_token;

    // 2. Buka popup pembayaran Midtrans
    window.snap.pay(snapToken, {
      onSuccess: function(result){
        Swal.fire('Pembayaran Berhasil!', 'Terima kasih, pesanan Anda sedang diproses.', 'success');
        router.push({ name: 'user-dashboard' });
      },
      onPending: function(result){
        Swal.fire('Pembayaran Tertunda', 'Harap selesaikan pembayaran Anda.', 'warning');
        router.push({ name: 'user-dashboard' });
      },
      onError: function(result){
        Swal.fire('Pembayaran Gagal', 'Silakan coba lagi atau gunakan metode pembayaran lain.', 'error');
        isLoading.value = false;
        error.value = "Gagal memproses pembayaran.";
      },
      onClose: function(){
        // Pengguna menutup popup tanpa menyelesaikan pembayaran
        Swal.fire({
          text: "Anda menutup jendela pembayaran. Apakah Anda ingin mencoba lagi?",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: "Ya",
          cancelButtonText: "Tidak, Kembali"
        }).then((result) => {
          if (result.isConfirmed) {
            processPayment(); // Coba lagi
          } else {
            router.push({ name: 'user-dashboard' });
          }
        });
      }
    });

  } catch (err: any) {
    isLoading.value = false;
    error.value = err.response?.data?.message || "Gagal membuat transaksi pembayaran.";
  }
};

onMounted(() => {
  processPayment();
});
</script>