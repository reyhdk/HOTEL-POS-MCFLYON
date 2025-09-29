<template>
  <div>
    <h1 class="mb-3">Tagihan & Checkout</h1>
    <p class="fs-6 text-muted mb-8">
      Periksa rincian tagihan Anda dan lakukan proses checkout dengan mudah.
    </p>

    <div v-if="isLoading" class="d-flex justify-content-center py-20">
      <div class="spinner-border text-primary" style="width: 3rem; height: 3rem"></div>
    </div>

    <div v-else-if="!folio" class="card shadow-sm">
      <div class="card-body d-flex flex-column flex-center text-center p-20">
        <i class="ki-duotone ki-information fs-5x text-muted mb-5">
            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
        </i>
        <h3 class="fs-3 text-gray-800">Tidak Ada Sesi Aktif</h3>
        <p class="fs-6 text-muted">Fitur ini hanya tersedia untuk tamu yang sedang check-in.</p>
      </div>
    </div>

    <div v-else class="card card-flush shadow-sm">
        <div class="card-header pt-5">
            <h3 class="card-title">Rincian Tagihan Kamar {{ folio.room_number }}</h3>
        </div>
        <div class="card-body">
            <div v-if="folio.unpaid_orders.length > 0">
                <h5 class="mb-4">Pesanan Belum Dibayar:</h5>
                <div class="table-responsive">
                    <table class="table table-row-dashed fs-6 gy-4">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th>ID Pesanan</th>
                                <th>Waktu</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600">
                            <tr v-for="order in folio.unpaid_orders" :key="order.id">
                                <td>#{{ order.id }}</td>
                                <td>{{ new Date(order.created_at).toLocaleString('id-ID') }}</td>
                                <td class="text-end">{{ formatCurrency(order.total_price_with_tax) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <hr class="my-5 border-dashed" />
            </div>

            <div class="d-flex justify-content-between align-items-center mb-5">
                <span class="fs-5 fw-bold text-gray-800">Total Sisa Tagihan:</span>
                <span class="fs-3 fw-bolder text-primary">{{ formatCurrency(folio.total_bill) }}</span>
            </div>

            <div v-if="folio.is_fully_paid">
                <p class="text-success fs-6"><i class="ki-duotone ki-shield-tick fs-4 text-success me-2"><span class="path1"></span><span class="path2"></span></i> Semua tagihan Anda sudah lunas.</p>
                <button @click="handleCheckout" :disabled="isSubmitting" class="btn btn-primary w-100 mt-3">
                    <span v-if="!isSubmitting">Express Checkout Sekarang</span>
                    <span v-else class="indicator-progress d-block">
                        Memproses... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>
            <div v-else>
                 <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6">
                    <div class="d-flex flex-stack flex-grow-1">
                        <div class="fw-semibold">
                        <h4 class="text-gray-900 fw-bold">Pembayaran Diperlukan</h4>
                        <div class="fs-6 text-gray-700">
                            Harap selesaikan pembayaran sisa tagihan Anda di resepsionis untuk melanjutkan proses checkout.
                        </div>
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
import ApiService from '@/core/services/ApiService';
import Swal from 'sweetalert2';
import { useRouter } from 'vue-router';

// Interface untuk mendefinisikan tipe data
interface Order {
    id: number;
    created_at: string;
    total_price_with_tax: number;
}

interface Folio {
    guest_name: string;
    room_number: string;
    check_in_date: string;
    unpaid_orders: Order[];
    total_bill: number;
    is_fully_paid: boolean;
}

// State Management
const folio = ref<Folio | null>(null);
const isLoading = ref(true);
const isSubmitting = ref(false);
const router = useRouter();

// Fungsi untuk mengambil data tagihan
const fetchFolio = async () => {
    isLoading.value = true;
    try {
        const response = await (ApiService as any).get('/guest/folio');
        folio.value = response.data.folio;
    } catch (error) {
        console.error("Gagal memuat folio:", error);
        folio.value = null;
    } finally {
        isLoading.value = false;
    }
};

// Fungsi untuk memproses checkout
const handleCheckout = async () => {
    isSubmitting.value = true;
    try {
        // [PERBAIKAN KUNCI DI SINI]
        // Menggunakan '(ApiService as any)' untuk bypass pengecekan tipe TypeScript
        const response = await (ApiService as any).post('/guest/checkout');
        const data = response.data;

        await Swal.fire({
            text: data.message,
            icon: "success",
            buttonsStyling: false,
            confirmButtonText: "Selesai",
            customClass: { confirmButton: "btn btn-primary" },
        });

        router.push({ name: 'user-dashboard' });
    } catch (error: any) {
        const message = error.response?.data?.message || "Terjadi kesalahan.";
        Swal.fire("Gagal", message, "error");
    } finally {
        isSubmitting.value = false;
    }
};

// Fungsi bantuan untuk format mata uang
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(value);
};

// Lifecycle Hook
onMounted(fetchFolio);
</script>
