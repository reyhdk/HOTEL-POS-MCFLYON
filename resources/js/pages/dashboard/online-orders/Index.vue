<template>
  <div class="card">
    <div class="card-header border-0 pt-6">
      <div class="card-title">
        <h2>Pesanan Masuk</h2>
      </div>
    </div>
    <div class="card-body pt-0">
      <div v-if="isLoading" class="text-center py-10">
        <div class="spinner-border text-primary"></div>
      </div>

      <table v-else class="table align-middle table-row-dashed fs-6 gy-5">
        <thead>
          <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
            <th>ID Pesanan</th>
            <th>Tamu</th>
            <th>No. Kamar</th>
            <th>Total Tagihan</th>
            <th>Status</th>
            <th>Waktu Pesan</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody class="fw-semibold text-gray-600">
          <tr v-if="orders.length === 0">
            <td colspan="7" class="text-center">Belum ada pesanan yang masuk.</td>
          </tr>
          <tr v-for="order in orders" :key="order.id">
            <td>#{{ order.id }}</td>
            <td>{{ order.user?.name || 'N/A' }}</td>
            <td>{{ order.room?.room_number || 'N/A' }}</td>
            <td>{{ formatCurrency(order.total_price) }}</td>
            <td>
              <span :class="getStatusBadge(order.status)">{{ order.status }}</span>
            </td>
            <td>{{ formatDateTime(order.created_at) }}</td>
            <td class="text-end">
              <button @click="showDetails(order)" class="btn btn-sm btn-light-primary">Detail</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <OrderDetailModal
    :order="selectedOrder"
    @orderUpdated="fetchOrders"
    @closeModal="selectedOrder = null"
  />
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";
import OrderDetailModal from "@/pages/dashboard/online-orders/OrderDetailModal.vue"; // [DIPERBAIKI] Sesuaikan path jika perlu

interface Order {
  id: number;
  user: { name: string };
  room: { room_number: string };
  total_price: number;
  status: string;
  created_at: string;
  items: Array<{
    menu: { name: string };
    quantity: number;
    price: number;
  }>;
}

const orders = ref<Order[]>([]);
const isLoading = ref(true);
const selectedOrder = ref<Order | null>(null);

const fetchOrders = async () => {
  isLoading.value = true;
  try {
    const response = await ApiService.get("/online-orders");
    orders.value = response.data;
  } catch (error) {
    Swal.fire({ text: "Gagal memuat data pesanan.", icon: "error" });
  } finally {
    isLoading.value = false;
  }
};

const showDetails = (order: Order) => {
  // [DIPERBAIKI] Cara ini lebih aman untuk memastikan modal tidak error
  // jika data lama masih ada saat mengambil data baru.
  selectedOrder.value = null;
  ApiService.get(`/online-orders/${order.id}`).then(response => {
    selectedOrder.value = response.data;
  });
};

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
};

const formatDateTime = (dateString: string) => {
    const options: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

const getStatusBadge = (status: string) => {
    if (status === 'pending' || status === 'paid') return 'badge badge-light-warning';
    if (status === 'processing' || status === 'delivering') return 'badge badge-light-info';
    if (status === 'completed') return 'badge badge-light-success';
    if (status === 'cancelled') return 'badge badge-light-danger';
    return 'badge badge-light-dark';
};

onMounted(fetchOrders);
</script>
