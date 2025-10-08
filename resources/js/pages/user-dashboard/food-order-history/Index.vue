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
                <span class="text-muted fs-7">{{ formatDate(order.created_at) }}</span>
              </div>
              <div class="d-flex flex-column align-items-end">
                <span :class="getStatusBadgeClass(order.status)" class="badge fs-6">{{ order.status }}</span>
                <span class="fw-bolder text-primary fs-5 mt-2">{{ formatCurrency(order.total_price) }}</span>
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
import axios from '@/libs/axios'; // Pastikan path ini benar
import { toast } from 'vue3-toastify';

// State
const orders = ref<any[]>([]);
const isLoading = ref(true);

// API Call
const fetchOrderHistory = async () => {
  try {
    isLoading.value = true;
    const response = await axios.get('/guest/orders');
    orders.value = response.data;
  } catch (error) {
    toast.error("Gagal memuat riwayat pesanan.");
  } finally {
    isLoading.value = false;
  }
};

// Helper Functions
const formatCurrency = (value: number) => {
  if (!value) return "Rp 0";
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
};

const formatDate = (dateString: string) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' });
};

const getStatusBadgeClass = (status: string) => {
  const statusMap = {
    pending: 'badge-light-warning',
    paid: 'badge-light-info',
    processing: 'badge-light-primary',
    delivered: 'badge-light-success',
    cancelled: 'badge-light-danger',
  };
  return statusMap[status] || 'badge-light-secondary';
};

// Lifecycle Hook
onMounted(() => {
  fetchOrderHistory();
});
</script>