<template>
  <div class="card">
    <div class="card-header border-0 pt-6">
      <div class="card-title">
        <div class="d-flex align-items-center position-relative my-1">
          <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
            <span class="path1"></span><span class="path2"></span>
          </i>
          <input
            v-model="search"
            type="text"
            class="form-control form-control-solid w-250px ps-13"
            placeholder="Cari Nama / HP..."
            @input="handleSearch"
          />
        </div>
      </div>
      
      <div class="card-toolbar">
        <!-- ✅ Filter Toggle -->
        <div class="btn-group me-3" role="group">
          <input type="radio" class="btn-check" id="filter_all" v-model="filterStatus" value="all" />
          <label class="btn btn-sm btn-outline btn-outline-primary" for="filter_all">Semua</label>
          
          <input type="radio" class="btn-check" id="filter_pending" v-model="filterStatus" value="pending" />
          <label class="btn btn-sm btn-outline btn-outline-warning" for="filter_pending">Pending</label>
          
          <input type="radio" class="btn-check" id="filter_verified" v-model="filterStatus" value="verified" />
          <label class="btn btn-sm btn-outline btn-outline-success" for="filter_verified">Verified</label>
        </div>

        <button type="button" class="btn btn-light-primary" @click="fetchData">
          <i class="ki-duotone ki-arrows-circle fs-2"><span class="path1"></span><span class="path2"></span></i>
          Refresh
        </button>
      </div>
    </div>

    <div class="card-body py-4">
      <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5">
          <thead>
            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
              <th class="min-w-200px">Tamu</th>
              <th class="min-w-150px">Status Kamar</th>
              <th class="min-w-100px">Kontak</th>
              <th class="min-w-150px">Foto KTP</th>
              <th class="min-w-100px">Status Verifikasi</th>
              <th class="text-end min-w-100px">Aksi</th>
            </tr>
          </thead>
          <tbody class="text-gray-600 fw-semibold">
            <tr v-if="loading">
              <td colspan="6" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="text-muted mt-2">Memuat data verifikasi...</div>
              </td>
            </tr>
            
            <tr v-else-if="filteredList.length === 0">
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="ki-duotone ki-folder-check fs-1 d-block mb-2 text-gray-400"><span class="path1"></span><span class="path2"></span></i>
                {{ getEmptyMessage }}
              </td>
            </tr>

            <tr v-for="(item, index) in filteredList" :key="index" :class="{ 'opacity-50': isVerified(item) }">
              
              <td>
                <div class="d-flex align-items-center">
                  <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                    <div class="symbol-label fs-3 bg-light-primary text-primary">
                      {{ item.name ? item.name.charAt(0).toUpperCase() : '?' }}
                    </div>
                  </div>
                  <div class="d-flex flex-column">
                    <span class="text-gray-800 text-hover-primary mb-1 fw-bold">{{ item.name }}</span>
                    <span class="text-gray-500 fs-7">{{ item.email || 'Tidak ada email' }}</span>
                  </div>
                </div>
              </td>

              <td>
                <div v-if="item.check_ins && item.check_ins.length > 0">
                   <div class="d-flex flex-column">
                       <span class="badge badge-light-success fw-bold fs-7 mb-1">
                          <i class="ki-duotone ki-key fs-7 me-1 text-success"><span class="path1"></span><span class="path2"></span></i>
                          Room {{ item.check_ins[0].room?.room_number || '?' }}
                       </span>
                       <span class="text-muted fs-9 fst-italic">Sedang Menginap (In-House)</span>
                   </div>
                </div>

                <div v-else-if="item.bookings && item.bookings.length > 0">
                   <div v-for="(booking, idx) in item.bookings" :key="idx" class="mb-2">
                       <span class="badge badge-light-primary fw-bold fs-7 mb-1">
                          <i class="ki-duotone ki-calendar-tick fs-7 me-1 text-primary"><span class="path1"></span><span class="path2"></span></i>
                          Booking: Room {{ booking.room?.room_number || 'Random' }}
                       </span>
                       <div class="d-flex align-items-center text-gray-600 fs-8">
                           <i class="ki-duotone ki-time fs-8 me-1"><span class="path1"></span><span class="path2"></span></i>
                           {{ formatDate(booking.check_in_date) }}
                       </div>
                   </div>
                </div>

                <div v-else>
                   <span class="badge badge-light-secondary text-gray-400 fs-8">Belum Check-in / Booking</span>
                </div>
              </td>

              <td>
                <div class="fw-bold">{{ item.phone_number }}</div>
              </td>

              <td>
                <div 
                  v-if="item.ktp_image_url"
                  class="symbol symbol-75px symbol-2by3 cursor-pointer shadow-sm border border-gray-200"
                  @click="openImageModal(item.ktp_image_url)"
                >
                  <img :src="item.ktp_image_url" alt="KTP" style="object-fit: cover" />
                  <div class="symbol-badge bg-dark bg-opacity-50 top-50 start-50 translate-middle w-100 h-100 d-flex align-items-center justify-content-center opacity-0 hover-opacity-100 transition-opacity rounded">
                    <i class="ki-duotone ki-eye fs-2 text-white"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                  </div>
                </div>
                <span v-else class="text-muted fs-8 fst-italic">Gambar rusak / tidak ada</span>
              </td>

              <!-- ✅ Status Badge Dinamis -->
              <td>
                <span 
                  v-if="isVerified(item)" 
                  class="badge badge-light-success fw-bold border border-success">
                    <i class="ki-duotone ki-check-circle fs-5 me-1 text-success">
                      <span class="path1"></span><span class="path2"></span>
                    </i>
                    Terverifikasi
                </span>
                <span 
                  v-else 
                  class="badge badge-light-warning fw-bold border border-warning border-dashed">
                    <i class="ki-duotone ki-time fs-5 me-1 text-warning">
                      <span class="path1"></span><span class="path2"></span>
                    </i>
                    Menunggu Verifikasi
                </span>
              </td>

              <!-- ✅ Tombol Aksi Conditional -->
              <td class="text-end">
                <template v-if="!isVerified(item)">
                  <button 
                    class="btn btn-sm btn-success me-2"
                    @click="verifyGuest(item.id)"
                    :disabled="processing === item.id"
                    title="Terima & Verifikasi"
                  >
                    <span v-if="processing === item.id" class="spinner-border spinner-border-sm"></span>
                    <span v-else><i class="ki-duotone ki-check fs-2"></i> Terima</span>
                  </button>
                  
                  <button 
                    class="btn btn-sm btn-icon btn-light-danger"
                    @click="rejectGuest(item.id)"
                    title="Tolak & Hapus Foto"
                  >
                    <i class="ki-duotone ki-trash fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                  </button>
                </template>

                <template v-else>
                  <span class="badge badge-light-success fs-8 px-3 py-2">
                    <i class="ki-duotone ki-check-circle fs-5 me-1">
                      <span class="path1"></span><span class="path2"></span>
                    </i>
                    Sudah Diverifikasi
                  </span>
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Preview -->
    <div v-if="showModal" class="modal-backdrop fade show" style="z-index: 1050;"></div>
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1" style="z-index: 1055;" @click.self="closeModal">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg">
          <div class="modal-header border-0 pb-0 justify-content-between">
            <h5 class="modal-title">Preview Identitas</h5>
            <div class="btn btn-icon btn-sm btn-light-danger" @click="closeModal">
              <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
            </div>
          </div>
          <div class="modal-body text-center p-5 bg-light-dark rounded-bottom">
            <img :src="selectedImage" class="img-fluid rounded shadow" style="max-height: 80vh;" />
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import ApiService from "@/core/services/ApiService"; 
import { ElMessage, ElMessageBox } from "element-plus";

const loading = ref(false);
const processing = ref<number | null>(null);
const search = ref("");
const filterStatus = ref("all"); // ✅ Filter State
const listData = ref<any[]>([]);
const showModal = ref(false);
const selectedImage = ref("");

// ✅ Helper: Cek apakah tamu sudah verified
const isVerified = (item: any): boolean => {
  // Cek dari Guest Profile
  if (item.is_verified === true) return true;
  
  // Cek dari Booking (jika ada booking yang statusnya verified)
  if (item.bookings && item.bookings.length > 0) {
    return item.bookings.some((b: any) => b.verification_status === 'verified');
  }
  
  return false;
};

// ✅ Filter berdasarkan search & status
const filteredList = computed(() => {
  let result = listData.value;

  // Filter by search
  if (search.value) {
    const lowerSearch = search.value.toLowerCase();
    result = result.filter(item => 
      (item.name && item.name.toLowerCase().includes(lowerSearch)) ||
      (item.phone_number && item.phone_number.includes(lowerSearch))
    );
  }

  // Filter by verification status
  if (filterStatus.value === 'pending') {
    result = result.filter(item => !isVerified(item));
  } else if (filterStatus.value === 'verified') {
    result = result.filter(item => isVerified(item));
  }

  return result;
});

// ✅ Empty state message
const getEmptyMessage = computed(() => {
  if (filterStatus.value === 'pending') {
    return 'Tidak ada permintaan verifikasi pending saat ini.';
  } else if (filterStatus.value === 'verified') {
    return 'Belum ada tamu yang diverifikasi.';
  }
  return 'Tidak ada data tamu dengan foto KTP.';
});

onMounted(() => {
  fetchData();
});

const formatDate = (dateString: string) => {
    if(!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', { 
        day: 'numeric', 
        month: 'long', 
        year: 'numeric' 
    }).format(date);
};

// ✅ Fetch tanpa filter backend (ambil semua, filter di frontend)
const fetchData = async () => {
  loading.value = true;
  try {
    const response = await ApiService.query("guests", { 
        verification_needed: 1 // Ambil semua yang punya KTP
    });
    listData.value = response.data.data || [];
    
    console.log("📋 Total guests with KTP:", listData.value.length);
    
  } catch (error) {
    console.error("Fetch Error:", error);
    ElMessage.error("Gagal memuat data verifikasi.");
  } finally {
    loading.value = false;
  }
};

const openImageModal = (url: string) => {
  if(!url) return;
  selectedImage.value = url;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  selectedImage.value = "";
};

const handleSearch = () => {
  // Triggered by computed property
};

// ✅ Setelah verify, refresh data (tidak remove dari list)
const verifyGuest = async (id: number) => {
  processing.value = id;
  try {
    await ApiService.post(`guests/${id}/verify`, {});
    ElMessage.success("Identitas berhasil diverifikasi!");
    
    // ✅ Refresh data untuk update status
    await fetchData();
    
  } catch (error: any) {
    const msg = error.response?.data?.message || "Gagal memverifikasi tamu.";
    ElMessage.error(msg);
  } finally {
    processing.value = null;
  }
};

const rejectGuest = (id: number) => {
  ElMessageBox.confirm(
    'Tindakan ini akan menghapus foto KTP dari database dan tamu harus mengupload ulang. Lanjutkan?',
    'Tolak Verifikasi?',
    {
      confirmButtonText: 'Ya, Tolak',
      cancelButtonText: 'Batal',
      type: 'warning',
      center: true
    }
  ).then(async () => {
    loading.value = true;
    try {
        await ApiService.post(`guests/${id}/reject-ktp`, {});
        ElMessage.success("Verifikasi ditolak. Foto dihapus.");
        await fetchData(); 
    } catch (error: any) {
        const msg = error.response?.data?.message || "Gagal menolak data.";
        ElMessage.error(msg);
        loading.value = false;
    }
  }).catch(() => {});
};
</script>

<style scoped>
.symbol-2by3 {
    aspect-ratio: 3/2;
    background-color: #f1f1f4;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

/* ✅ Styling untuk filter buttons */
.btn-check:checked + .btn-outline-primary { background-color: #009ef7; color: white; }
.btn-check:checked + .btn-outline-warning { background-color: #ffc700; color: white; }
.btn-check:checked + .btn-outline-success { background-color: #17c653; color: white; }

/* ✅ Efek opacity untuk row yang sudah verified */
.opacity-50 { opacity: 0.6; transition: opacity 0.3s; }
.opacity-50:hover { opacity: 1; }
</style>