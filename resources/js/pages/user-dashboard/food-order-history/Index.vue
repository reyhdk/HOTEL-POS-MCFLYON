<template>
  <div>
    <h1 class="mb-5">Riwayat Pesanan Makanan</h1>

    <div v-if="isLoading" class="text-center py-10">
      <span class="spinner-border text-primary"></span>
      <p class="mt-4">Memuat riwayat pesanan...</p>
    </div>

    <div v-else-if="orders.length === 0" class="card shadow-sm">
      <div class="card-body text-center p-10">
        <i class="ki-duotone ki-files fs-5x text-muted mb-5"></i>
        <h3 class="fs-4 text-gray-800">Anda Belum Memiliki Riwayat Pesanan</h3>
        <p class="fs-6 text-muted">Semua pesanan makanan Anda akan tampil di sini.</p>
      </div>
    </div>

    <div v-else>
      <div class="card shadow-sm">
        <div class="card-body">
          <div v-for="order in orders" :key="order.id" class="mb-8 border-bottom pb-5">
            <div class="d-flex flex-wrap flex-stack mb-4">
              <div>
                <h4 class="fw-bold">Pesanan #{{ order.id }}</h4>
                <span class="text-muted fs-7">{{ formatDateTime(order.created_at) }}</span>
              </div>
              <div class="d-flex flex-column align-items-end">
                <span :class="getStatusBadgeClass(order.status)" class="fs-6 text-capitalize">{{ order.status }}</span>
                <span class="fw-bolder text-primary fs-5 mt-2">{{ formatCurrency(order.total_price * 1.1) }}</span>
              </div>
            </div>
            
            <div v-for="item in order.items" :key="item.id" class="d-flex align-items-center mb-3">
              <div class="symbol symbol-40px me-4">
                <img :src="item.menu.image_url || '/media/illustrations/blank.svg'" alt="" class="rounded-3"/>
              </div>
              <div class="flex-grow-1">
                <div class="fw-semibold text-gray-800">{{ item.menu.name }}</div>
                <div class="fs-7 text-muted">{{ item.quantity }} x {{ formatCurrency(item.price) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import ApiService from "@/core/services/ApiService"; // Menggunakan ApiService agar konsisten
import { toast } from 'vue3-toastify';

// State
const orders = ref<any[]>([]);
const isLoading = ref(true);

const fetchOrderHistory = async () => {
  try {
    isLoading.value = true;
    // [DIPERBAIKI] Menggunakan endpoint yang benar sesuai api.php
    const { data } = await ApiService.get('/guest/orders'); 
    orders.value = data;
  } catch (error) {
    toast.error("Gagal memuat riwayat pesanan.");
    console.error(error);
  } finally {
    isLoading.value = false;
  }
};

// Helper Functions
const formatCurrency = (value: number) => {
  if (!value) return "Rp 0";
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
};

// [PENYESUAIAN] Menggunakan fungsi format dari modal admin agar konsisten
const formatDateTime = (dateString: string) => {
  if (!dateString) return '';
  const options: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
  return new Date(dateString).toLocaleDateString('id-ID', options).replace('pukul', '');
};

// [DIPERBAIKI] Menyesuaikan nama status agar cocok dengan yang diupdate oleh Admin
const getStatusBadgeClass = (status: string) => {
  const statusMap = {
    pending: 'badge-light-warning',
    paid: 'badge-light-primary',
    processing: 'badge-light-primary',
    delivering: 'badge-light-info',   // Diubah dari 'delivered' menjadi 'delivering'
    completed: 'badge-light-success', // Ditambahkan status 'completed'
    cancelled: 'badge-light-danger',
  };
  // Pastikan ada 'badge' di depan agar styling dasar diterapkan
  return `badge ${statusMap[status] || 'badge-light-dark'}`;
};

// Lifecycle Hook
onMounted(() => {
  fetchOrderHistory();
});
</script>