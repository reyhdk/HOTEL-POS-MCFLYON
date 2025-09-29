<template>
  <div>
    <h1 class="mb-3">Riwayat Permintaan Layanan</h1>
    <p class="fs-6 text-muted mb-8">
      Lacak status semua permintaan layanan kamar Anda di sini.
    </p>

    <div v-if="isLoading" class="d-flex justify-content-center py-20">
      <div class="spinner-border text-primary" style="width: 3rem; height: 3rem"></div>
    </div>

    <div v-else-if="requests.length === 0" class="card shadow-sm">
      <div class="card-body d-flex flex-column flex-center text-center p-20">
        <i class="ki-duotone ki-abstract-29 fs-5x text-muted mb-5">
            <span class="path1"></span><span class="path2"></span>
        </i>
        <h3 class="fs-3 text-gray-800">Belum Ada Riwayat</h3>
        <p class="fs-6 text-muted">Anda belum pernah membuat permintaan layanan kamar.</p>
      </div>
    </div>

    <div v-else>
      <TransitionGroup name="fade" tag="div" class="d-flex flex-column gap-6">
        <div v-for="req in requests" :key="req.id" class="card card-flush shadow-sm">
          <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
              <div class="d-flex align-items-center mb-3 mb-md-0">
                <div class="symbol symbol-60px symbol-2by3 me-4">
                    <div class="symbol-label rounded" :class="getServiceColor(req.service_name)">
                        <i :class="getServiceIcon(req.service_name)" class="fs-2x text-white"></i>
                    </div>
                </div>
                <div>
                  <div class="fs-5 fw-bold text-gray-800">{{ req.service_name }}</div>
                  <div class="fs-7 text-muted">{{ formatDateTime(req.created_at) }}</div>
                </div>
              </div>

              <div class="d-flex align-items-center mb-3 mb-md-0">
                <div v-if="req.cleaning_time" class="text-end text-md-center px-4">
                    <div class="fs-7 text-muted">Jadwal</div>
                    <div class="fs-6 fw-bold">{{ req.cleaning_time.substring(0, 5) }}</div>
                </div>
                <div v-else class="text-end text-md-center px-4">
                    <div class="fs-7 text-muted">Jumlah</div>
                    <div class="fs-6 fw-bold">x {{ req.quantity }}</div>
                </div>
              </div>

              <div class="d-flex align-items-center">
                <span :class="getStatusBadge(req.status)" class="badge-lg">{{ req.status }}</span>
              </div>
            </div>
            <div v-if="req.notes" class="notice d-flex bg-light-secondary rounded border-secondary border border-dashed p-4 mt-4">
                <i class="ki-duotone ki-information-2 fs-3 text-gray-700 me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                <div class="d-flex flex-stack flex-grow-1">
                    <div class="fw-semibold">
                        <div class="fs-7 text-gray-600">{{ req.notes }}</div>
                    </div>
                </div>
            </div>
          </div>
        </div>
      </TransitionGroup>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import ApiService from '@/core/services/ApiService';

interface ServiceRequest {
  id: number;
  room: { room_number: string };
  user: { name: string };
  service_name: string;
  quantity: number;
  notes: string | null;
  status: 'pending' | 'processing' | 'completed' | 'cancelled';
  cleaning_time: string | null;
  created_at: string;
}

const requests = ref<ServiceRequest[]>([]);
const isLoading = ref(true);

const fetchRequests = async () => {
  try {
    const { data } = await ApiService.get('/guest/service-requests');
    requests.value = data;
  } catch (error) {
    console.error("Gagal memuat riwayat permintaan:", error);
  } finally {
    isLoading.value = false;
  }
};

const formatDateTime = (datetime: string) => {
  return new Date(datetime).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getStatusBadge = (status: string) => {
  const statusMap = {
    pending: "badge-light-warning",
    processing: "badge-light-info",
    completed: "badge-light-success",
    cancelled: "badge-light-danger",
  };
  return `badge ${statusMap[status] || "badge-light-secondary"}`;
};

const getServiceIcon = (serviceName: string) => {
    const iconMap = {
        "Handuk Tambahan": "ki-duotone ki-menu",
        "Air Mineral": "ki-duotone ki-cup",
        "Pembersihan Kamar": "ki-duotone ki-abstract-29",
        "Layanan Laundry": "ki-duotone ki-bucket",
        "Peminjaman Setrika": "ki-duotone ki-element-9",
        "Lainnya": "ki-duotone ki-message-edit",
    };
    return iconMap[serviceName] || "ki-duotone ki-question";
};

const getServiceColor = (serviceName: string) => {
    if (serviceName === 'Pembersihan Kamar') return 'bg-primary';
    if (serviceName === 'Layanan Laundry') return 'bg-info';
    return 'bg-secondary';
}

onMounted(fetchRequests);
</script>
