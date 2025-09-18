<template>
  <div class="card shadow-sm">
    <div class="card-body p-lg-10">
      <div v-if="isLoading" class="text-center py-10">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
        <p class="mt-3">Memuat detail pesanan...</p>
      </div>

      <div v-else-if="order">
        <h1 class="mb-4">Konfirmasi Pembayaran</h1>
        
        <div class="d-flex justify-content-between align-items-center mb-5 p-4 bg-light rounded">
          <div>
            <p class="text-muted mb-1">Nomor Pesanan</p>
            <h3 class="fw-bold">#{{ order.id }}</h3>
          </div>
          <div class="text-end">
            <p class="text-muted mb-1">Status</p>
            <span class="badge badge-light-warning fs-6 text-capitalize">{{ order.status }}</span>
          </div>
        </div>

        <h3 class="mb-4">Rincian Tagihan</h3>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200">
                <th>Item</th>
                <th class="text-end">Jumlah</th>
                <th class="text-end">Harga Satuan</th>
                <th class="text-end">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in order.items" :key="item.id">
                <td>{{ item.menu.name }}</td>
                <td class="text-end">{{ item.quantity }}</td>
                <td class="text-end">{{ formatCurrency(item.price) }}</td>
                <td class="text-end">{{ formatCurrency(item.price * item.quantity) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="3" class="text-end fw-semibold text-muted">Total</td>
                <td class="text-end fw-bold fs-5">{{ formatCurrency(order.total_price) }}</td>
              </tr>
              </tfoot>
          </table>
        </div>

        <div class="separator separator-dashed my-8"></div>
        
        <div>
          <h3 class="mb-4">Metode Pembayaran</h3>
          <p class="text-muted">Untuk saat ini, semua pesanan akan ditagihkan langsung ke kamar Anda saat check-out.</p>
          
          <button @click="processPayment" class="btn btn-lg btn-success w-100 mt-5" :disabled="isProcessingPayment">
            <span v-if="!isProcessingPayment">
              <i class="ki-duotone ki-shield-tick fs-2"><span class="path1"></span><span class="path2"></span></i>
              Ya, Konfirmasi dan Bayar
            </span>
             <span v-else>
              <span class="spinner-border spinner-border-sm" role="status"></span>
              Memproses...
            </span>
          </button>
        </div>

      </div>
      
      <div v-else class="text-center py-10">
        <p>Pesanan tidak ditemukan.</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";

interface Order {
  id: number;
  total_price: number;
  status: string;
  items: Array<{
    id: number;
    quantity: number;
    price: number;
    menu: { name: string };
  }>;
}

const route = useRoute();
const router = useRouter();
const order = ref<Order | null>(null);
const isLoading = ref(true);
const isProcessingPayment = ref(false);

const orderId = route.params.orderId as string;

// Mengambil detail pesanan dari server
const fetchOrderDetails = async () => {
  try {
    isLoading.value = true;
    const { data } = await ApiService.get(`/guest/orders/${orderId}`);
    order.value = data;
  } catch (error) {
    Swal.fire("Error", "Gagal memuat detail pesanan.", "error");
    order.value = null;
  } finally {
    isLoading.value = false;
  }
};

// Memproses pembayaran (mengubah status ke 'paid')
const processPayment = async () => {
    isProcessingPayment.value = true;
    try {
        await ApiService.post(`/guest/orders/${orderId}/pay`, {});
        
        await Swal.fire({
            text: "Pembayaran berhasil dikonfirmasi!",
            icon: "success",
            timer: 2000,
            showConfirmButton: false
        });

        // Arahkan ke riwayat booking atau dashboard setelah berhasil
        router.push({ name: 'booking-history' });

    } catch (error) {
        Swal.fire("Error", "Gagal memproses pembayaran.", "error");
    } finally {
        isProcessingPayment.value = false;
    }
}

// Fungsi bantuan
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
};

onMounted(() => {
  fetchOrderDetails();
});
</script>