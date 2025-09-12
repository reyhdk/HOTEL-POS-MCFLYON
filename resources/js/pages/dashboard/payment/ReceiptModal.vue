<template>
  <div class="modal fade" id="kt_modal_receipt" ref="modalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-450px">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="fw-bold">Struk Pembayaran</h2>
          <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
            <i class="ki-duotone ki-cross fs-1"></i>
          </div>
        </div>
        <div class="modal-body py-10 px-lg-17">
          <div id="receipt-content">
            <div class="text-center mb-10">
              <h3 class="fs-2x">MCFLYON HOTEL</h3>
              <p class="text-muted">Jl. Raya Bungkal Jl. Bungkal Gg. II No.25 B, RT.010/RW.003, Sambikerep, Kec. Sambikerep, Surabaya, Jawa Timur</p>
            </div>
            <div class="separator separator-dashed mb-5"></div>
            <div v-if="order">  
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">No. Pesanan:</span>
                <span class="fw-bold">#{{ order.id }}</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Kamar:</span>
                <span class="fw-bold">{{ order.room.room_number }}</span>
              </div>
              <div class="d-flex justify-content-between mb-10">
                <span class="text-muted">Tanggal:</span>
                <span class="fw-bold">{{ new Date().toLocaleString('id-ID') }}</span>
              </div>
              <div v-for="item in order.items" :key="item.id" class="d-flex justify-content-between mb-4">
                <div>
                  <div class="fw-bold">{{ item.menu.name }}</div>
                  <div class="text-muted fs-7">{{ item.quantity }} x {{ formatCurrency(item.price) }}</div>
                </div>
                <div class="fw-bold">{{ formatCurrency(item.quantity * item.price) }}</div>
              </div>
              <div class="separator separator-dashed my-5"></div>
              <div class="d-flex justify-content-between">
                <span class="fw-bold fs-5">TOTAL</span>
                <span class="fw-bold fs-5">{{ formatCurrency(order.total_price) }}</span>
              </div>
            </div>
            <div class="text-center mt-10 text-muted fs-7">
              Terima kasih atas kunjungan Anda!
            </div>
          </div>
        </div>
        <div class="modal-footer flex-center">
          <button class="btn btn-light me-3" data-bs-dismiss="modal">Tutup</button>
          <button @click="printReceipt" class="btn btn-primary">
            <i class="ki-duotone ki-printer fs-2"></i> Cetak Struk
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineProps } from "vue";

// Menerima data pesanan dari parent component
const props = defineProps<{
  order: any;
}>();

const formatCurrency = (value: number) => {
  if (isNaN(value)) return "Rp 0";
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(value);
};

const printReceipt = () => {
  const printContents = document.getElementById('receipt-content')?.innerHTML;
  const originalContents = document.body.innerHTML;

  if (printContents) {
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    // Kita perlu me-reload untuk mengembalikan fungsionalitas Vue
    window.location.reload(); 
  }
};
</script>