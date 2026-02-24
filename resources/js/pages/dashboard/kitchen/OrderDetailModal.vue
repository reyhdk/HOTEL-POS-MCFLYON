<template>
  <Teleport to="body">
    <!-- Tambahkan class kds-modal-container agar CSS unscoped tidak bocor -->
    <div class="modal fade kds-modal-container" id="kt_modal_order_detail" tabindex="-1" aria-hidden="true" ref="modalElRef" style="z-index: 1055;">
      <div class="modal-dialog modal-dialog-centered modal-lg"> 
        
        <div class="modal-content shadow-lg rounded-4 border-0 bg-surface">
          
          <div class="modal-header border-bottom border-gray-200 py-4">
              <h5 class="fw-bolder text-gray-900 m-0 d-flex align-items-center">
                 <span class="symbol symbol-30px me-3">
                    <span class="symbol-label bg-light-orange text-orange rounded-circle">
                      <i class="bi bi-eye fs-4"></i>
                    </span>
                 </span>
                 Detail Tampilan Pesanan #{{ currentOrder?.order_code || '-' }}
              </h5>
              <button type="button" class="btn btn-icon btn-sm btn-light-danger rounded-circle" data-bs-dismiss="modal" aria-label="Close">
                 <i class="bi bi-x fs-4"></i>
              </button>
          </div>

          <div class="modal-body p-4 p-md-5" v-if="currentOrder">
              
              <div class="row g-5">
                 <!-- KOLOM KIRI: INFO PEMESAN -->
                 <div class="col-12 col-md-5 border-md-end border-gray-200 pe-md-5">
                    
                    <div class="d-flex align-items-center mb-5 p-3 bg-light rounded-3 border border-dashed border-gray-300">
                       <div class="symbol symbol-50px me-4">
                          <div class="symbol-label bg-orange text-white fw-bold fs-3 rounded-3 shadow-sm">
                             {{ getLocationCode(currentOrder) }}
                          </div>
                       </div>
                       <div class="d-flex flex-column">
                          <span class="text-gray-500 fs-8 fw-bold text-uppercase mb-1">Tujuan / Pemesan</span>
                          <span class="fw-bolder text-gray-900 fs-5">{{ getLocationInfo(currentOrder) }}</span>
                          <span class="text-gray-600 fs-7 d-flex align-items-center">
                             <i class="bi bi-person-fill fs-8 me-1 text-gray-400"></i>
                             {{ getGuestName(currentOrder) }}
                          </span>
                       </div>
                    </div>

                    <!-- INFO ALOKASI OTOMATIS (MODE VIEW) -->
                    <div class="bg-light-primary rounded-4 p-4 border border-primary border-dashed mb-5">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                           <h6 class="fw-bolder text-primary m-0 text-uppercase fs-8 ls-1">Informasi Antrean</h6>
                           <span :class="getStatusBadge(currentOrder.status)" class="badge rounded-pill fw-bold">
                               {{ getStatusLabel(currentOrder.status) }}
                           </span>
                        </div>
                        
                        <div class="d-flex align-items-center mb-4">
                           <div class="symbol symbol-45px symbol-circle me-3">
                              <span class="symbol-label bg-primary text-white fs-4 fw-bolder">
                                 {{ currentOrder.assigned_chef_name ? currentOrder.assigned_chef_name.charAt(0).toUpperCase() : '?' }}
                              </span>
                           </div>
                           <div class="d-flex flex-column">
                              <span class="text-gray-900 fw-bolder fs-5">
                                 Koki: {{ currentOrder.assigned_chef_name || 'Sistem' }}
                              </span>
                              <span class="text-gray-500 fs-8 fw-semibold">Dialokasikan Otomatis</span>
                           </div>
                        </div>

                        <div class="d-flex align-items-center bg-white rounded p-3 border border-gray-200">
                           <i class="bi bi-stopwatch text-warning fs-1 me-3"></i>
                           <div class="d-flex flex-column">
                              <span class="text-gray-500 fs-9 fw-bold text-uppercase">Estimasi Waktu Tunggu</span>
                              <div class="d-flex align-items-baseline">
                                  <span class="text-gray-900 fw-bolder fs-3">{{ currentOrder.calculated_estimation || 0 }}</span>
                                  <span class="fs-7 text-gray-500 fw-normal ms-1">Menit</span>
                              </div>
                           </div>
                        </div>
                    </div>

                 </div>

                 <!-- KOLOM KANAN: DAFTAR MENU -->
                 <div class="col-12 col-md-7">
                    <h6 class="fw-bold text-gray-800 text-uppercase ls-1 fs-8 mb-3">Daftar Menu Dipesan:</h6>
                    
                    <div v-if="!currentOrder.items || currentOrder.items.length === 0" class="alert alert-warning">
                        Tidak ada item pesanan ditemukan.
                    </div>

                    <div v-else class="table-responsive rounded-3 border border-gray-200 custom-scroll" style="max-height: 350px; overflow-y: auto;">
                      <table class="table align-middle gs-0 gy-4 mb-0"> 
                        <thead class="bg-light sticky-top">
                           <tr class="fw-bold text-gray-500 fs-9 text-uppercase">
                              <th class="ps-4 min-w-150px">Menu</th>
                              <th class="text-center min-w-50px">Qty</th>
                              <th class="text-end pe-4 min-w-80px">Estimasi</th>
                           </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                          <tr v-for="item in currentOrder.items" :key="item.id" class="border-bottom border-gray-100">
                            <td class="ps-4">
                              <div class="d-flex align-items-center">
                                 <i class="bi bi-egg-fried text-warning me-2 fs-5"></i>
                                 <span class="text-gray-800 fw-bold fs-7">{{ item.menu?.name || 'Menu Tidak Diketahui' }}</span>
                              </div>
                            </td>
                            <td class="text-center">
                              <span class="badge badge-lg badge-light-danger fw-bolder fs-6 px-3">x{{ item.quantity }}</span>
                            </td>
                            <td class="text-end pe-4">
                              <span class="text-gray-500 fs-8">{{ item.menu?.cooking_estimation_time || 0 }}mnt/prs</span>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                 </div>

              </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { Modal } from "bootstrap";

// Karena mode View Only, kita hapus emits yang berhubungan dengan update backend
const modalElRef = ref<HTMLElement | null>(null);
const currentOrder = ref<any>(null); 
let modalInstance: Modal | null = null;

onMounted(() => {
    if (modalElRef.value) modalInstance = new Modal(modalElRef.value);
});

const open = (orderData: any) => {
    currentOrder.value = { ...orderData }; 
    modalInstance?.show(); 
};

defineExpose({ open });

// Utilities
const getGuestName = (order: any) => order?.guest?.name || order?.user?.name || 'Walk-in / POS';
const getLocationInfo = (o: any) => {
    if (o.room) return `Kamar ${o.room.room_number}`;
    if (o.table) return `Meja ${o.table.name}`;
    return 'Take Away / POS';
};
const getLocationCode = (o: any) => {
    if (o.room) return o.room.room_number;
    if (o.table) return o.table.name.substring(0,2).toUpperCase();
    return 'POS';
};

const getStatusLabel = (s: string) => {
    const map: Record<string, string> = { pending: 'Antrean Baru', processing: 'Sedang Dimasak', delivering: 'Diantar', completed: 'Selesai' };
    return map[s] || s;
};
const getStatusBadge = (s: string) => {
    const map: Record<string, string> = {
        pending: 'badge-light-danger text-danger', processing: 'badge-light-warning text-warning', delivering: 'badge-light-primary text-primary', completed: 'badge-light-success text-success',
    };
    return map[s] || 'badge-light-secondary';
};
</script>

<style scoped>
/* Hanya basic styles di dalam scoped block */
.bg-surface { background-color: #ffffff; }

.text-orange { color: #ff6b00 !important; }
.bg-orange { background-color: #ff6b00 !important; }
.bg-light-orange { background-color: #fff5f0 !important; }

.custom-scroll::-webkit-scrollbar { width: 5px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>

<style>
/* Dark Mode Overrides Tidak Menggunakan Scoped
   Penggunaan .kds-modal-container mencegah CSS bocor ke halaman lain 
*/
[data-bs-theme="dark"] .kds-modal-container .bg-surface { background-color: #1e1e2d !important; color: #ffffff !important; }
[data-bs-theme="dark"] .kds-modal-container .modal-content { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .kds-modal-container .modal-header { border-bottom-color: #2b2b40 !important; }
[data-bs-theme="dark"] .kds-modal-container .border-md-end { border-right-color: #2b2b40 !important; }

[data-bs-theme="dark"] .kds-modal-container .bg-light { background-color: #2b2b40 !important; }
[data-bs-theme="dark"] .kds-modal-container .bg-white { background-color: #1e1e2d !important; }

[data-bs-theme="dark"] .kds-modal-container .border-gray-100 { border-color: #2b2b40 !important; }
[data-bs-theme="dark"] .kds-modal-container .border-gray-200 { border-color: #2b2b40 !important; }
[data-bs-theme="dark"] .kds-modal-container .border-gray-300 { border-color: #323248 !important; }
[data-bs-theme="dark"] .kds-modal-container .border-dashed { border-style: dashed !important; }

[data-bs-theme="dark"] .kds-modal-container .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .kds-modal-container .text-gray-800 { color: #e4e6ef !important; }
[data-bs-theme="dark"] .kds-modal-container .text-gray-700 { color: #b5b5c3 !important; }
[data-bs-theme="dark"] .kds-modal-container .text-gray-600 { color: #a1a5b7 !important; }
[data-bs-theme="dark"] .kds-modal-container .text-gray-500 { color: #7e8299 !important; }
[data-bs-theme="dark"] .kds-modal-container .text-gray-400 { color: #565674 !important; }

[data-bs-theme="dark"] .kds-modal-container .bg-light-orange { background-color: rgba(255, 107, 0, 0.15) !important; }
[data-bs-theme="dark"] .kds-modal-container .custom-scroll::-webkit-scrollbar-thumb { background: #474761 !important; }
</style>