<template>
  <div class="d-flex flex-column gap-5">
    
    <div class="row g-5 g-xl-8">
      <div class="col-xl-3 col-6 animate-item" style="--delay: 0s">
        <div class="card bg-body hover-elevate-up border-0 h-100 card-stat-orange theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-orange text-orange rounded-4">
                <i class="ki-duotone ki-home fs-2x text-orange"><span class="path1"></span><span class="path2"></span></i>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ rooms.length }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Total Kamar</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-6 animate-item" style="--delay: 0.1s">
        <div class="card bg-body hover-elevate-up border-0 h-100 theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
             <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-success text-success rounded-4">
                <i class="ki-duotone ki-check-circle fs-2x text-success"><span class="path1"></span><span class="path2"></span></i>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ countStatus('available') }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Tersedia</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-6 animate-item" style="--delay: 0.2s">
         <div class="card bg-body hover-elevate-up border-0 h-100 theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
             <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-danger text-danger rounded-4">
                <i class="ki-duotone ki-user fs-2x text-danger"><span class="path1"></span><span class="path2"></span></i>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ countStatus('occupied') }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Terisi</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-6 animate-item" style="--delay: 0.3s">
         <div class="card bg-body hover-elevate-up border-0 h-100 theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
             <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-warning text-warning rounded-4">
                <i class="ki-duotone ki-brush fs-2x text-warning"><span class="path1"></span><span class="path2"></span></i>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ countDirty }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Perawatan</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm theme-card animate-item position-relative" style="--delay: 0.4s; z-index: 99;">
        <div class="card-body py-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                
                <div class="d-flex align-items-center position-relative w-100 w-lg-300px">
                    <i class="ki-duotone ki-magnifier fs-2 position-absolute ms-4 text-gray-500"><span class="path1"></span><span class="path2"></span></i>
                    <input type="text" v-model="searchQuery" class="form-control form-control-solid ps-12 search-input" placeholder="Cari nomor kamar..." />
                </div>

                <div class="d-flex flex-wrap gap-3 align-items-center">
                    
                    <div class="dropdown-wrapper position-relative w-150px" v-click-outside="() => closeDropdown('type')">
                        <button 
                            class="btn btn-custom-select w-100 d-flex align-items-center justify-content-between px-4" 
                            type="button" 
                            @click="toggleDropdown('type')"
                            :class="{ 'active': activeDropdown === 'type' }"
                        >
                            <div class="d-flex align-items-center text-truncate">
                                <i class="ki-duotone ki-category fs-2 me-2 text-gray-500"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                <span class="fw-bold text-gray-700 fs-7">{{ activeTypeLabel }}</span>
                            </div>
                            <i class="ki-duotone ki-down fs-5 text-gray-500 ms-2 transition-icon" :class="{ 'rotate-180': activeDropdown === 'type' }"></i>
                        </button>

                        <transition name="dropdown-anim">
                            <div v-if="activeDropdown === 'type'" class="custom-dropdown-menu shadow-lg border-0 p-2 rounded-3 theme-dropdown">
                                <ul class="list-unstyled m-0">
                                    <li>
                                        <a href="#" class="dropdown-item-custom rounded px-3 py-2 fw-semibold mb-1" 
                                           :class="{ 'selected': filterType === 'all' }" 
                                           @click.prevent="setFilterType('all')">
                                           Semua Tipe
                                        </a>
                                    </li>
                                    <li v-for="type in uniqueRoomTypes" :key="type">
                                        <a href="#" class="dropdown-item-custom rounded px-3 py-2 fw-semibold mb-1" 
                                           :class="{ 'selected': filterType === type }" 
                                           @click.prevent="setFilterType(type)">
                                            {{ type }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </transition>
                    </div>

                    <div class="dropdown-wrapper position-relative w-175px" v-click-outside="() => closeDropdown('status')">
                        <button 
                            class="btn btn-custom-select w-100 d-flex align-items-center justify-content-between px-4" 
                            type="button" 
                            @click="toggleDropdown('status')"
                            :class="{ 'active': activeDropdown === 'status' }"
                        >
                            <div class="d-flex align-items-center text-truncate">
                                <i class="ki-duotone ki-flag fs-2 me-2 text-gray-500"><span class="path1"></span><span class="path2"></span></i>
                                <span class="fw-bold text-gray-700 fs-7">{{ activeStatusLabel }}</span>
                            </div>
                            <i class="ki-duotone ki-down fs-5 text-gray-500 ms-2 transition-icon" :class="{ 'rotate-180': activeDropdown === 'status' }"></i>
                        </button>

                        <transition name="dropdown-anim">
                            <div v-if="activeDropdown === 'status'" class="custom-dropdown-menu shadow-lg border-0 p-2 rounded-3 theme-dropdown">
                                <ul class="list-unstyled m-0">
                                    <li><a href="#" class="dropdown-item-custom rounded px-3 py-2 fw-semibold mb-1" :class="{ 'selected': filterStatus === 'all' }" @click.prevent="setFilterStatus('all')">Semua Status</a></li>
                                    <li><a href="#" class="dropdown-item-custom rounded px-3 py-2 fw-semibold mb-1" :class="{ 'selected': filterStatus === 'available' }" @click.prevent="setFilterStatus('available')">Tersedia</a></li>
                                    <li><a href="#" class="dropdown-item-custom rounded px-3 py-2 fw-semibold mb-1" :class="{ 'selected': filterStatus === 'occupied' }" @click.prevent="setFilterStatus('occupied')">Terisi</a></li>
                                    <li><a href="#" class="dropdown-item-custom rounded px-3 py-2 fw-semibold mb-1" :class="{ 'selected': filterStatus === 'dirty' }" @click.prevent="setFilterStatus('dirty')">Perawatan</a></li>
                                </ul>
                            </div>
                        </transition>
                    </div>

                    <button v-if="userHasPermission('create rooms')" class="btn btn-sm btn-orange fw-bold hover-scale ms-lg-2 box-shadow-orange" @click="openAddRoomModal">
                        <i class="ki-duotone ki-plus fs-2 text-white"></i> Tambah
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="position-relative min-h-300px" style="z-index: 1;">
        
        <div v-if="loading" class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center z-index-10 bg-body bg-opacity-75 rounded-3">
             <div class="d-flex flex-column align-items-center animate-pulse">
                 <span class="spinner-border text-orange mb-3 w-40px h-40px"></span>
                 <span class="text-gray-500 fw-bold">Memuat data...</span>
             </div>
        </div>

        <div v-else-if="filteredRooms.length === 0" class="d-flex flex-column align-items-center justify-content-center py-15 text-muted animate-fade-in">
             <div class="symbol symbol-100px mb-5 bg-light-orange rounded-circle d-flex align-items-center justify-content-center">
                <i class="ki-duotone ki-file-sheet fs-4x text-orange"><span class="path1"></span><span class="path2"></span></i>
             </div>
             <span class="fs-4 fw-bold text-gray-800">Tidak ada kamar ditemukan.</span>
             <span class="fs-7">Coba sesuaikan kata kunci atau filter Anda.</span>
        </div>

        <div v-if="!loading && filteredRooms.length > 0">
            <TransitionGroup name="list-shuffle" tag="div" class="row g-6">
                <div class="col-md-6 col-lg-4 col-xxl-3 room-item" v-for="room in filteredRooms" :key="room.id">
                    
                    <div class="card h-100 border-0 shadow-sm theme-card hover-elevate-up transition-300 group-card">
                        
                        <div class="position-relative h-200px bg-secondary rounded-top-3 overflow-hidden room-image-container">
                             <img :src="room.image_url || '/media/svg/files/blank-image.svg'" class="w-100 h-100 object-fit-cover room-img" alt="Room Image" />
                             <div class="overlay-layer position-absolute w-100 h-100 transition-300 z-index-1"></div>
                             <div class="position-absolute top-0 start-0 w-100 h-4px status-border transition-300" :class="getStatusColor(room.status, 'bg')"></div>

                             <div class="position-absolute bottom-0 end-0 m-3 z-index-2">
                                <div class="backdrop-blur px-3 py-1 rounded-pill shadow-sm price-badge">
                                    <span class="fw-bolder fs-7 text-white">Rp {{ formatPriceNumber(room.price_per_night) }}</span>
                                </div>
                             </div>

                             <div class="position-absolute top-0 start-0 m-3 z-index-2 status-badge-hover">
                                <div class="backdrop-blur px-3 py-2 rounded-pill shadow-sm">
                                    <span class="fw-bold fs-8 text-white text-uppercase">{{ getStatusLabel(room.status) }}</span>
                                </div>
                             </div>
                        </div>

                        <div class="card-body p-5 d-flex flex-column">
                            
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="text-gray-500 fs-9 fw-bold text-uppercase ls-1 mb-1">{{ room.type }}</div>
                                    <h3 class="fs-2 fw-bolder text-gray-900 hover-text-orange cursor-pointer transition-200 m-0" @click="openEditRoomModal(room)">
                                        No. {{ room.room_number }}
                                    </h3>
                                </div>
                                <button class="btn btn-icon btn-sm btn-light-primary w-30px h-30px rounded-circle hover-scale hover-rotate" @click="openEditRoomModal(room)">
                                    <i class="ki-duotone ki-pencil fs-6"><span class="path1"></span><span class="path2"></span></i>
                                </button>
                            </div>

                            <div class="d-flex align-items-center p-3 rounded mb-4 bg-light-subtle border border-dashed border-gray-300 status-info-card">
                                <span class="bullet bullet-dot h-10px w-10px me-3 pulse-dot" :class="getStatusColor(room.status, 'bg')"></span>
                                <div class="d-flex flex-column w-100">
                                    <span class="fs-7 fw-bold" :class="getStatusColor(room.status, 'text')">{{ getStatusLabel(room.status) }}</span>
                                    
                                    <div v-if="room.status === 'occupied' && getActiveGuestName(room)" class="d-flex flex-column mt-1">
                                        <div v-if="isGuestIncognito(room)" class="d-flex align-items-center mb-1">
                                            <span class="badge badge-light-danger fw-bold fs-9 d-flex align-items-center px-2 py-1 border border-danger border-dashed">
                                                <i class="ki-duotone ki-eye-slash fs-8 me-1 text-danger">
                                                    <span class="path1"></span><span class="path2"></span>
                                                    <span class="path3"></span><span class="path4"></span>
                                                </i>
                                                INCOGNITO
                                            </span>
                                        </div>
                                        
                                        <span class="fs-8 fw-bolder text-truncate" 
                                              :class="isGuestIncognito(room) ? 'text-gray-600 fst-italic' : 'text-gray-800'">
                                            {{ getActiveGuestName(room) }}
                                        </span>
                                        
                                        <div class="d-flex align-items-center mt-1">
                                            <i class="ki-duotone ki-calendar-8 fs-9 me-1 text-gray-400">
                                                <span class="path1"></span><span class="path2"></span>
                                                <span class="path3"></span><span class="path4"></span>
                                                <span class="path5"></span><span class="path6"></span>
                                            </i>
                                            <span class="fs-9 fw-semibold text-gray-500">{{ getGuestDates(room) }}</span>
                                        </div>
                                    </div>

                                    <span v-else class="fs-9 text-gray-500">
                                        {{ room.status === 'available' ? 'Siap digunakan' : 'Dalam proses' }}
                                    </span>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mb-4 flex-wrap">
                                 <template v-if="room.facilities?.length">
                                    <span v-for="(fac, i) in room.facilities.slice(0, 3)" :key="i" class="badge badge-light-dark fs-9 fw-bold facility-badge">{{ fac.name }}</span>
                                    <span v-if="room.facilities.length > 3" class="badge badge-light-dark fs-9 fw-bold facility-badge">+{{ room.facilities.length - 3 }}</span>
                                </template>
                                <span v-else class="text-gray-400 fs-9 fst-italic">Standard Facility</span>
                            </div>

                            <div class="mt-auto pt-4 border-top border-gray-200 border-opacity-50 d-flex gap-2">
                                <div class="flex-grow-1">
                                    <button v-if="room.status === 'available'" @click="openCheckInModal(room)" class="btn btn-sm btn-success w-100 fw-bold hover-scale action-btn">Check-in</button>
                                    <button v-if="room.status === 'occupied'" @click="processCheckout(room)" class="btn btn-sm btn-danger w-100 fw-bold hover-scale action-btn">Check-out</button>
                                    <button v-if="['dirty','needs cleaning'].includes(room.status)" @click="markAsClean(room)" class="btn btn-sm btn-info w-100 fw-bold text-white hover-scale action-btn">Selesai</button>
                                </div>
                                
                                <button 
                                    class="btn btn-sm btn-icon btn-light-warning h-100 w-35px rounded hover-scale" 
                                    type="button" 
                                    @click="openScheduleModal(room)"
                                    title="Lihat Jadwal Booking">
                                    <i class="ki-duotone ki-calendar-tick fs-3 text-warning">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span>
                                    </i>
                                </button>

                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon btn-light h-100 w-35px rounded hover-bg-light-primary dropdown-toggle-custom" 
                                            type="button" 
                                            data-bs-toggle="dropdown" 
                                            aria-expanded="false">
                                        <i class="ki-duotone ki-dots-square fs-3 text-gray-600 icon-dots">
                                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                                        </i>
                                    </button>
                                    
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2 w-200px theme-dropdown z-index-dropdown dropdown-animated">
                                        <li><div class="dropdown-header text-uppercase fs-9 fw-bold text-muted px-3">Opsi Menu</div></li>
                                        <li v-if="userHasPermission('edit rooms')" class="dropdown-item-wrapper">
                                            <a href="#" class="dropdown-item rounded px-3 py-2 fw-semibold text-gray-700 hover-text-primary dropdown-item-animated" @click.prevent="openEditRoomModal(room)">
                                                <i class="ki-duotone ki-pencil fs-5 me-2"><span class="path1"></span><span class="path2"></span></i> Edit Detail
                                            </a>
                                        </li>
                                        <li v-if="room.status === 'occupied'" class="dropdown-item-wrapper">
                                            <a href="#" class="dropdown-item rounded px-3 py-2 fw-semibold text-gray-700 hover-text-warning dropdown-item-animated" @click.prevent="requestCleaning(room)">
                                                <i class="ki-duotone ki-broom fs-5 me-2"><span class="path1"></span><span class="path2"></span></i> Request Cleaning
                                            </a>
                                        </li>
                                        <li v-if="userHasPermission('delete rooms')"><div class="dropdown-divider my-2 border-gray-200"></div></li>
                                        <li v-if="userHasPermission('delete rooms')" class="dropdown-item-wrapper">
                                            <a href="#" class="dropdown-item rounded px-3 py-2 fw-semibold text-danger hover-bg-light-danger dropdown-item-animated" @click.prevent="deleteRoom(room.id)">
                                                <i class="ki-duotone ki-trash fs-5 me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i> Hapus Data
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </TransitionGroup>
        </div>

    </div>

    <RoomModal :room-data="selectedRoom" @room-updated="refreshData" @close-modal="selectedRoom = null" />
    
    <CheckInModal 
        ref="checkInModalRef" 
        :room-data="selectedRoom" 
        @checkin-success="refreshData" 
        @close-modal="selectedRoom = null" 
    />

    <RoomScheduleModal ref="scheduleModalRef" />

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import RoomModal from "./RoomModal.vue";
import CheckInModal from "./CheckInModal.vue";
import RoomScheduleModal from "./RoomScheduleModal.vue"; // [BARU] Import Component

// ===== INTERFACES =====
interface Facility { id: number; name: string; }
interface Room { 
  id: number; 
  room_number: string; 
  type: string; 
  status: string; 
  price_per_night: number; 
  description: string | null; 
  image_url: string | null; 
  facilities: Facility[]; 
  check_ins?: any[]; 
}

// ===== STATE =====
const authStore = useAuthStore();
const rooms = ref<Room[]>([]);
const loading = ref(true);
const selectedRoom = ref<any>(null);
const searchQuery = ref("");
const filterStatus = ref('all');
const filterType = ref('all');
const activeDropdown = ref<string | null>(null);

// Refs Components
const checkInModalRef = ref<any>(null);
const scheduleModalRef = ref<any>(null); // [BARU] Ref untuk modal jadwal

// ===== DROPDOWN LOGIC =====
const toggleDropdown = (name: string) => { 
    activeDropdown.value = activeDropdown.value === name ? null : name; 
};

const closeDropdown = (name: string) => { 
    if (activeDropdown.value === name) activeDropdown.value = null; 
};

const setFilterType = (type: string) => { 
    filterType.value = type; 
    activeDropdown.value = null; 
};

const setFilterStatus = (status: string) => { 
    filterStatus.value = status; 
    activeDropdown.value = null; 
};

const vClickOutside = {
    mounted(el: any, binding: any) {
        el.clickOutsideEvent = function(event: Event) { 
            if (!(el === event.target || el.contains(event.target))) binding.value(event, el); 
        };
        document.body.addEventListener('click', el.clickOutsideEvent);
    },
    unmounted(el: any) { 
        document.body.removeEventListener('click', el.clickOutsideEvent); 
    },
};

// ===== COMPUTED PROPERTIES =====
const activeTypeLabel = computed(() => 
    filterType.value === 'all' ? 'Semua Tipe' : filterType.value
);

const activeStatusLabel = computed(() => {
    const map: any = { 
        'all': 'Semua Status', 
        'available': 'Tersedia', 
        'occupied': 'Terisi', 
        'dirty': 'Perawatan' 
    };
    return map[filterStatus.value] || 'Status';
});

const uniqueRoomTypes = computed(() => {
    const types = rooms.value.map(r => r.type);
    return [...new Set(types)].filter(Boolean);
});

const filteredRooms = computed(() => {
    let result = rooms.value;
    
    if (searchQuery.value) {
        result = result.filter(room => 
            room.room_number.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }
    
    if (filterStatus.value !== 'all') {
        if (filterStatus.value === 'dirty') {
            result = result.filter(r => 
                ['dirty', 'needs cleaning', 'request cleaning'].includes(r.status)
            );
        } else {
            result = result.filter(r => r.status === filterStatus.value);
        }
    }
    
    if (filterType.value !== 'all') {
        result = result.filter(r => r.type === filterType.value);
    }
    
    return result;
});

const countStatus = (status: string) => 
    rooms.value.filter(r => r.status === status).length;

const countDirty = computed(() => 
    rooms.value.filter(r => 
        ['dirty', 'needs cleaning', 'request cleaning'].includes(r.status)
    ).length
);

// ===== HELPER FUNCTIONS =====
const userHasPermission = (permission: string) => 
    authStore.user?.all_permissions?.includes(permission) ?? false;

const formatPriceNumber = (value: number) => 
    new Intl.NumberFormat("id-ID").format(value);

const getActiveGuestName = (room: Room) => {
    if (room.check_ins && room.check_ins.length > 0) {
        return room.check_ins[0].guest?.name || 'Tamu';
    }
    return null;
};

const isGuestIncognito = (room: Room): boolean => {
    if (room.status !== 'occupied') return false;
    if (!room.check_ins || room.check_ins.length === 0) return false;
    
    const checkIn = room.check_ins[0];
    
    if (checkIn.is_incognito === true || 
        checkIn.is_incognito === 1 || 
        checkIn.is_incognito === "1") {
        return true;
    }
    
    if (checkIn.booking && (
        checkIn.booking.is_incognito === true || 
        checkIn.booking.is_incognito === 1 || 
        checkIn.booking.is_incognito === "1"
    )) {
        return true;
    }
    
    return false;
};

const getGuestDates = (room: Room) => {
    if (room.status === 'occupied' && room.check_ins && room.check_ins.length > 0) {
        const checkIn = room.check_ins[0];
        const start = new Date(checkIn.check_in_time);
        const end = new Date(checkIn.check_out_time);
        const options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short' };
        return `${start.toLocaleDateString('id-ID', options)} - ${end.toLocaleDateString('id-ID', options)}`;
    }
    return '';
};

const getStatusLabel = (status: string) => {
    if (status === 'available') return 'Tersedia';
    if (status === 'occupied') return 'Terisi';
    if (['dirty', 'needs cleaning', 'request cleaning'].includes(status)) return 'Perlu Dibersihkan';
    return status;
};

const getStatusColor = (status: string, type: 'text' | 'bg') => {
    const isDirty = ['dirty', 'needs cleaning', 'request cleaning'].includes(status);
    const map: any = {
        available: { text: 'text-success', bg: 'bg-success' },
        occupied: { text: 'text-danger', bg: 'bg-danger' },
        dirty: { text: 'text-warning', bg: 'bg-warning' }
    };
    const key = isDirty ? 'dirty' : (map[status] ? status : 'available');
    return map[key][type];
};

// ===== API CALLS =====
const getRooms = async () => {
    loading.value = true;
    try { 
        const { data } = await ApiService.get("/rooms"); 
        rooms.value = data; 
    } catch (e) { 
        console.error('Error fetching rooms:', e);
        rooms.value = []; 
    } finally { 
        loading.value = false; 
    }
};

const refreshData = () => getRooms();

// ===== MODAL HANDLERS =====
const openModal = (id: string, room: any = null) => { 
    selectedRoom.value = room ? { ...room } : null; 
    new Modal(document.getElementById(id)!).show(); 
};

const openAddRoomModal = () => openModal('kt_modal_room');
const openEditRoomModal = (room: Room) => openModal('kt_modal_room', room);

// Check-in Modal
const openCheckInModal = (room: Room) => {
    if (checkInModalRef.value) {
        checkInModalRef.value.openModal(room);
    }
};

// [BARU] Open Schedule Modal
const openScheduleModal = (room: Room) => {
    if (scheduleModalRef.value) {
        scheduleModalRef.value.openModal(room);
    }
};

// ===== ACTION HANDLERS =====
const confirmAction = async (opts: any, action: Function) => {
    const res = await Swal.fire({ 
        ...opts, 
        showCancelButton: true, 
        buttonsStyling: false, 
        customClass: { 
            confirmButton: "btn fw-bold btn-danger", 
            cancelButton: "btn fw-bold btn-light" 
        } 
    });
    if (res.isConfirmed) { 
        await action(); 
        await refreshData(); 
    }
};

const deleteRoom = (id: number) => confirmAction(
    { text: "Hapus kamar ini?", icon: "warning", confirmButtonText: "Ya, Hapus" }, 
    async () => await ApiService.delete(`/rooms/${id}`)
);

const processCheckout = (room: Room) => confirmAction(
    { text: `Check-out ${room.room_number}?`, icon: "warning", confirmButtonText: "Check-out" }, 
    async () => await ApiService.post(`/check-out/${room.id}`, {})
);

const markAsClean = (room: Room) => confirmAction(
    { text: "Sudah bersih?", icon: "question", confirmButtonText: "Ya" }, 
    async () => await ApiService.post(`/rooms/${room.id}/mark-as-clean`, {})
);

const requestCleaning = (room: Room) => {
    Swal.fire({ 
        title: 'Jadwal Bersih?', 
        html: '<input type="time" id="swal-time" class="form-control text-center">', 
        showCancelButton: true, 
        confirmButtonText: 'Simpan', 
        confirmButtonColor: '#F68B1E', 
        preConfirm: () => (document.getElementById('swal-time') as HTMLInputElement).value 
    }).then(async (res) => {
        if(res.isConfirmed && res.value) { 
            await ApiService.post(`/rooms/${room.id}/request-cleaning`, { cleaning_time: res.value }); 
            refreshData(); 
        }
    });
};

// ===== LIFECYCLE =====
onMounted(getRooms);
</script>

<style scoped>
/* ========================
   THEME COLORS
   ======================== */
.text-orange { color: #F68B1E !important; }
.bg-light-orange { background-color: #FFF8F1 !important; }
.btn-orange { background-color: #F68B1E; color: white; border: none; }
.btn-orange:hover { background-color: #d97814; color: white; }
.hover-text-orange:hover { color: #F68B1E !important; }
.box-shadow-orange { box-shadow: 0 4px 12px rgba(246, 139, 30, 0.3); }

/* ========================
   1. CARD & Z-INDEX (CRITICAL)
   ======================== */
.group-card { position: relative; z-index: 1; transition: all 0.3s ease; }
/* Z-Index Tinggi saat hover agar dropdown di dalamnya TIDAK tertutup */
.group-card:hover { z-index: 20; } 

.hover-elevate-up:hover { 
    transform: translateY(-8px); 
    box-shadow: 0 15px 35px rgba(0,0,0,0.12) !important; 
}

/* ========================
   2. SMOOTH SHUFFLE ANIMATION
   ======================== */
.list-shuffle-move { transition: transform 0.5s cubic-bezier(0.55, 0, 0.1, 1); }
.list-shuffle-enter-active, .list-shuffle-leave-active { transition: all 0.5s cubic-bezier(0.55, 0, 0.1, 1); }
.list-shuffle-enter-from, .list-shuffle-leave-to { opacity: 0; transform: scale(0.9); }
.list-shuffle-leave-active { position: absolute; width: 100%; z-index: -1; visibility: hidden; }

/* ========================
   3. DROPDOWN ANIMATION
   ======================== */
.dropdown-anim-enter-active { animation: dropdown-in 0.2s ease-out; }
.dropdown-anim-leave-active { animation: dropdown-out 0.15s ease-in; }
@keyframes dropdown-in { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes dropdown-out { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(-10px); } }

/* ========================
   4. COMPONENT STYLES
   ======================== */
.btn-custom-select { background-color: #ffffff; border: 1px solid #e4e6ef; color: #5E6278; height: 40px; transition: all 0.2s; border-radius: 8px; }
.btn-custom-select:hover, .btn-custom-select.active { border-color: #F68B1E; color: #F68B1E; background-color: #FFF8F1; }
.custom-dropdown-menu { background-color: #ffffff; position: absolute; top: 100%; left: 0; width: 100%; margin-top: 5px; z-index: 1050; }
.dropdown-item-custom { display: block; width: 100%; color: #5E6278; text-decoration: none; transition: all 0.2s; font-size: 0.9rem; }
.dropdown-item-custom:hover { background-color: #F3F6F9; color: #F68B1E; }
.dropdown-item-custom.selected { background-color: #FFF4E6; color: #F68B1E; }

/* ========================
   5. DARK MODE SUPPORT
   ======================== */
[data-bs-theme="dark"] .bg-body { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .theme-card { background-color: #1e1e2d !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .bg-light-orange { background-color: rgba(246, 139, 30, 0.15) !important; }
[data-bs-theme="dark"] .bg-light-success { background-color: rgba(23, 198, 83, 0.15) !important; }
[data-bs-theme="dark"] .bg-light-danger { background-color: rgba(248, 40, 90, 0.15) !important; }
[data-bs-theme="dark"] .bg-light-warning { background-color: rgba(255, 199, 0, 0.15) !important; }
[data-bs-theme="dark"] .border-gray-200, [data-bs-theme="dark"] .border-gray-300 { border-color: #2B2B40 !important; }

[data-bs-theme="dark"] .dropdown-item { color: #9A9CAE; }
[data-bs-theme="dark"] .dropdown-item:hover { background-color: #2b2b40; color: #ffffff; }

[data-bs-theme="dark"] .form-control-solid { background-color: #1b1b29 !important; border-color: #323248 !important; color: #ffffff; }

[data-bs-theme="dark"] .btn-custom-select { background-color: #1b1b29; border-color: #323248; color: #CDCDDE; }
[data-bs-theme="dark"] .btn-custom-select:hover, [data-bs-theme="dark"] .btn-custom-select.active { border-color: #F68B1E; background-color: #2b2b40; color: #F68B1E; }
[data-bs-theme="dark"] .custom-dropdown-menu { background-color: #1e1e2d; border: 1px solid #323248 !important; }
[data-bs-theme="dark"] .dropdown-item-custom { color: #CDCDDE; }
[data-bs-theme="dark"] .dropdown-item-custom:hover { background-color: #2b2b40; color: #F68B1E; }
[data-bs-theme="dark"] .dropdown-item-custom.selected { background-color: #2b2b40; color: #F68B1E; }
</style>