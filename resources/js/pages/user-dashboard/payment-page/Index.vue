<template>
  <div class="d-flex flex-column flex-center text-center h-100">
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '@/libs/axios';
import { toast } from 'vue3-toastify';

// ▼▼▼ TAMBAHKAN BARIS INI ▼▼▼
declare const snap: any;

const route = useRoute();
const router = useRouter();
const isLoading = ref(true);
const error = ref<string | null>(null);

const processPayment = async () => {
  const orderId = route.params.orderId;

  try {
    const payload = {
      order_id: orderId,
      order_type: 'FOOD_ORDER',
    };

    const response = await axios.post('/midtrans/create-transaction', payload);
    const snapToken = response.data.snap_token;

    isLoading.value = false;

    // Baris ini tidak akan error lagi
    snap.pay(snapToken, {
      onSuccess: () => {
        toast.success("Pembayaran berhasil!");
        router.push('/user/food-order');
      },
      onPending: () => {
        toast.info("Menunggu pembayaran Anda.");
        router.push('/user/food-order');
      },
      onError: () => {
        toast.error("Pembayaran gagal.");
        error.value = "Proses pembayaran gagal atau dibatalkan.";
      },
      onClose: () => {
        if (!error.value) {
          toast.warn("Anda menutup jendela pembayaran.");
          router.back();
        }
      }
    });

  } catch (err: any) {
    isLoading.value = false;
    error.value = err.response?.data?.message || "Terjadi kesalahan yang tidak diketahui.";
    console.error("Gagal memulai pembayaran:", err);
  }
};

onMounted(() => {
  processPayment();
});
</script>
