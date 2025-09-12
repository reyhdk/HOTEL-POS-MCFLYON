<template>
  <div class="card">
    <div class="card-header border-0 pt-6">
      <h3 class="card-title">Daftar Tagihan (Belum Lunas)</h3>
    </div>
    <div class="card-body pt-0">
      <div v-if="loading" class="text-center text-muted">Memuat data tagihan...</div>
      <div v-else-if="pendingOrders.length === 0" class="text-center text-muted">
        Tidak ada tagihan yang perlu dibayar.
      </div>
      <div v-else>
        <div class="accordion" id="kt_accordion_orders">
          <div v-for="order in pendingOrders" :key="order.id" class="accordion-item">
            <h2 class="accordion-header" :id="`header_${order.id}`">
              <button class="accordion-button fs-4 fw-semibold collapsed" type="button" data-bs-toggle="collapse" :data-bs-target="`#body_${order.id}`">
                Pesanan #{{ order.id }} - Kamar {{ order.room.room_number }} | Total: {{ formatCurrency(order.total_price) }}
              </button>
            </h2>
            <div :id="`body_${order.id}`" class="accordion-collapse collapse" data-bs-parent="#kt_accordion_orders">
              <div class="accordion-body">
                <ul class="list-group list-group-flush">
                  <li v-for="item in order.items" :key="item.id" class="list-group-item d-flex justify-content-between align-items-center">
                    {{ item.menu.name }} ({{ formatCurrency(item.price) }})
                    <span class="badge badge-primary rounded-pill">{{ item.quantity }}x</span>
                  </li>
                </ul>
                <div class="mt-5 text-end">
                  <button @click="cancelOrder(order)" class="btn btn-sm btn-light-danger me-2">
                    <i class="ki-duotone ki-cross-square fs-2"></i> Batalkan
                  </button>
                  <button @click="processPayment(order)" class="btn btn-sm btn-success">
                    <i class="ki-duotone ki-dollar fs-2"></i> Bayar Tunai
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <ReceiptModal :order="selectedOrderForReceipt" />
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import ReceiptModal from "./ReceiptModal.vue";

interface Order {
  id: number;
  total_price: number;
  room: { room_number: string };
  items: { id: number; price: number; quantity: number; menu: { name: string } }[];
}

const pendingOrders = ref<Order[]>([]);
const loading = ref(true);
const selectedOrderForReceipt = ref<Order | null>(null);

const getPendingOrders = async () => {
  try {
    loading.value = true;
    const response = await axios.get("/pending-orders");
    pendingOrders.value = response.data;
  } catch (error) {
    console.error("Gagal memuat tagihan:", error);
    // Tambahkan notifikasi error jika diperlukan
    Swal.fire("Error!", "Gagal memuat data tagihan.", "error");
  } finally {
    loading.value = false;
  }
};

const processPayment = (order: Order) => {
  Swal.fire({
    text: `Proses pembayaran untuk Pesanan #${order.id}?`,
    icon: "question",
    showCancelButton: true,
    buttonsStyling: false,
    confirmButtonText: "Ya, Proses!",
    cancelButtonText: "Batal",
    customClass: {
      confirmButton: "btn fw-bold btn-success",
      cancelButton: "btn fw-bold btn-active-light-primary",
    },
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await axios.post(`/orders/${order.id}/pay`, { payment_method: 'cash' });
        
        selectedOrderForReceipt.value = order;
        const modalEl = document.getElementById("kt_modal_receipt");
        if (modalEl) {
            const modal = new Modal(modalEl);
            modal.show();
        }

        getPendingOrders();

      } catch (error) {
        Swal.fire("Error!", "Gagal memproses pembayaran.", "error");
        console.error(error);
      }
    }
  });
};

const cancelOrder = (order: Order) => {
  Swal.fire({
    text: `Anda yakin ingin membatalkan Pesanan #${order.id}?`,
    icon: "warning",
    showCancelButton: true,
    buttonsStyling: false,
    confirmButtonText: "Ya, Batalkan!",
    cancelButtonText: "Tidak",
    customClass: {
      confirmButton: "btn fw-bold btn-danger",
      cancelButton: "btn fw-bold btn-active-light-primary",
    },
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await axios.post(`/orders/${order.id}/cancel`);
        Swal.fire("Dibatalkan!", "Pesanan telah berhasil dibatalkan.", "success");
        getPendingOrders();
      } catch (error) {
        Swal.fire("Error!", "Gagal membatalkan pesanan.", "error");
        console.error(error);
      }
    }
  });
};

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(value);
};

onMounted(() => {
  getPendingOrders();
});
</script>