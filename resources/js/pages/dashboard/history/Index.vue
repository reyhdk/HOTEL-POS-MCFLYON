<template>
  <div class="card">
    <div class="card-header border-0 pt-6">
      <h3 class="card-title">Riwayat Transaksi</h3>
    </div>
    <div class="card-body pt-0">
      <table class="table align-middle table-row-dashed fs-6 gy-5">
        <thead>
          <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
            <th>No. Pesanan</th>
            <th>Kamar</th>
            <th>Total Harga</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody class="fw-semibold text-gray-600">
          <tr v-if="loading"><td colspan="6" class="text-center">Memuat riwayat...</td></tr>
          <tr v-else-if="history.length === 0"><td colspan="6" class="text-center">Belum ada riwayat transaksi.</td></tr>
          <tr v-for="order in history" :key="order.id">
            <td>#{{ order.id }}</td>
            <td>{{ order.room.room_number }}</td>
            <td>{{ formatCurrency(order.total_price) }}</td>
            <td>
              <span :class="getStatusBadge(order.status)">{{ order.status }}</span>
            </td>
            <td>{{ new Date(order.updated_at).toLocaleString('id-ID') }}</td>
            <td class="text-end">
              <button @click="viewDetails(order)" class="btn btn-sm btn-light-primary">
                Lihat Detail
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

interface Order {
  id: number;
  total_price: number;
  status: string;
  updated_at: string;
  room: { room_number: string };
  items: { id: number; price: number; quantity: number; menu: { name: string } }[];
}

const history = ref<Order[]>([]);
const loading = ref(true);

const getTransactionHistory = async () => {
  try {
    loading.value = true;
    const response = await axios.get("/transaction-history");
    history.value = response.data;
  } catch (error) {
    console.error("Gagal memuat riwayat:", error);
  } finally {
    loading.value = false;
  }
};

const viewDetails = (order: Order) => {
  let itemsHtml = order.items.map(item => `
    <div class="d-flex justify-content-between">
      <span>${item.menu.name} (${item.quantity}x)</span>
      <span>${formatCurrency(item.price * item.quantity)}</span>
    </div>
  `).join('');

  Swal.fire({
    title: `<strong>Detail Pesanan #${order.id}</strong>`,
    html: `
      <div class="text-start">
        <p><strong>Kamar:</strong> ${order.room.room_number}</p>
        <p><strong>Status:</strong> ${order.status}</p>
        <hr>
        ${itemsHtml}
        <hr>
        <div class="d-flex justify-content-between fw-bold fs-5">
          <span>TOTAL</span>
          <span>${formatCurrency(order.total_price)}</span>
        </div>
      </div>
    `,
    showCloseButton: true,
    showConfirmButton: false,
  });
};

const getStatusBadge = (status: string) => {
  if (status === 'completed') return 'badge badge-light-success';
  if (status === 'cancelled') return 'badge badge-light-danger';
  return 'badge badge-light-primary';
};

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(value);
};

onMounted(() => {
  getTransactionHistory();
});
</script>