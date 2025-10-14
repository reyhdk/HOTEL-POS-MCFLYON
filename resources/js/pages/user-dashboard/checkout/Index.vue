<template>
  <div>
    <h1 class="mb-3">Tagihan & Checkout</h1>
    <p class="fs-6 text-muted mb-8">
      Periksa rincian tagihan Anda dan lakukan proses checkout dengan mudah.
    </p>

    <div v-if="isLoading" class="text-center py-20">
      <span class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></span>
    </div>

    <div v-else-if="!folio" class="card shadow-sm">
        <div class="card-body text-center d-flex flex-column justify-content-center p-10" style="min-height: 400px;">
            <i class="ki-duotone ki-information fs-5x text-muted mb-5">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            <h3 class="fs-4 text-gray-800">Tidak Ada Sesi Aktif</h3>
            <p class="fs-6 text-muted">Fitur ini hanya tersedia untuk tamu yang sedang check-in.</p>
        </div>
    </div>

    <div v-else class="card card-flush shadow-sm">
      <div class="card-header pt-7">
        <h3 class="card-title">Rincian Tagihan Kamar {{ folio.room?.room_number }}</h3>
      </div>
      <div class="card-body">
        <div v-if="folio.unpaid_orders && folio.unpaid_orders.length > 0">
          <h5 class="mb-4 text-muted">Pesanan Belum Dibayar:</h5>
          <div class="table-responsive">
            <table class="table table-row-dashed fs-6 gy-4">
              <thead>
                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                  <th>Deskripsi</th>
                  <th class="text-end">Total</th>
                </tr>
              </thead>
              <tbody class="fw-semibold text-gray-600">
                <tr v-for="order in folio.unpaid_orders" :key="order.id">
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="me-5">
                        <div class="fw-bold">Pesanan Makanan #{{ order.id }}</div>
                        <div class="fs-7 text-muted">{{ formatDate(order.created_at) }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="text-end fw-bold">{{ formatCurrency(order.total_price) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="separator separator-dashed my-5"></div>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-5">
          <span class="fs-5 fw-bold text-gray-800">Total Sisa Tagihan:</span>
          <span class="fs-3 fw-bolder text-primary">{{ formatCurrency(folio.total_unpaid) }}</span>
        </div>
        
        <div v-if="folio.total_unpaid <= 0" class="notice d-flex bg-light-success rounded border-success border border-dashed p-6 mt-6">
          <i class="ki-duotone ki-shield-tick fs-2tx text-success me-4"><span class="path1"></span><span class="path2"></span></i>
          <div class="d-flex flex-stack flex-grow-1">
            <div class="fw-semibold">
              <div class="fs-6 text-gray-700">Semua tagihan Anda sudah lunas. Anda dapat melanjutkan proses checkout.</div>
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer text-end">
        <button @click="submitCheckout" class="btn btn-primary btn-lg" :disabled="isProcessing">
          <span v-if="!isProcessing">
            {{ folio.total_unpaid > 0 ? 'Bayar Tagihan & Checkout' : 'Express Checkout Sekarang' }}
          </span>
          <span v-else class="indicator-progress d-block">
            Memproses... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import ApiService from "@/core/services/ApiService";
import { toast } from 'vue3-toastify';
import Swal from "sweetalert2";
import { useRouter } from "vue-router";

declare const snap: any;

// Interface yang disesuaikan dengan respons backend baru
interface Order {
    id: number;
    created_at: string;
    total_price: number;
}
interface Folio {
    room: { room_number: string };
    booking: any;
    unpaid_orders: Order[];
    total_unpaid: number;
}

const folio = ref<Folio | null>(null);
const isLoading = ref(true);
const isProcessing = ref(false);
const router = useRouter();

const fetchFolio = async () => {
  isLoading.value = true;
  try {
    // [PERBAIKAN] Langsung ambil dari 'data', bukan 'data.folio'
    const { data } = await ApiService.get('/guest/folio');
    folio.value = data;
  } catch (error: any) {
    if (error.response?.status !== 404) {
        toast.error("Gagal memuat data folio.");
    }
    folio.value = null;
  } finally {
    isLoading.value = false;
  }
};

const submitCheckout = async () => {
    isProcessing.value = true;
    try {
        // === PERBAIKAN DI SINI ===
        // Tambahkan objek kosong {} sebagai argumen kedua
        const { data } = await ApiService.post("/guest/checkout", {});
        // =========================

        if (data.snap_token) {
            isProcessing.value = false;
            snap.pay(data.snap_token, {
                onSuccess: () => {
                    Swal.fire("Berhasil!", "Pembayaran berhasil dan Anda telah check-out.", "success")
                    .then(() => router.push({ name: 'user-dashboard' }));
                },
                onClose: () => {
                    toast.warn("Proses checkout dibatalkan.");
                }
            });
        } else {
            Swal.fire("Berhasil!", data.message || "Anda telah berhasil check-out.", "success")
            .then(() => router.push({ name: 'user-dashboard' }));
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || "Gagal memproses checkout.");
        isProcessing.value = false;
    }
};

const formatCurrency = (value: number | undefined) => {
  if (value === undefined || isNaN(value)) return "Rp 0";
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
};
const formatDate = (dateString: string) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' });
};

onMounted(fetchFolio);
</script>