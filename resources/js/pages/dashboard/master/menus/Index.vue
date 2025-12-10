<template>
  <div class="d-flex flex-column gap-5">
    
    <div class="row g-5 g-xl-8">
      <div class="col-xl-4 col-md-6 animate-item" style="--delay: 0s">
        <div class="card bg-body hover-elevate-up border-0 h-100 card-stat-orange theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-orange text-orange rounded-4">
                <span class="svg-icon svg-icon-2x svg-icon-orange">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path opacity="0.3" d="M14 2H21C21.6 2 22 2.4 22 3V10C22 10.6 21.6 11 21 11H14C13.4 11 13 10.6 13 10V3C13 2.4 13.4 2 14 2Z" fill="currentColor"/>
                        <path opacity="0.3" d="M3 2H10C10.6 2 11 2.4 11 3V10C11 10.6 10.6 11 10 11H3C2.4 11 2 10.6 2 10V3C2 2.4 2.4 2 3 2Z" fill="currentColor"/>
                        <path opacity="0.3" d="M14 13H21C21.6 13 22 13.4 22 14V21C22 21.6 21.6 22 21 22H14C13.4 22 13 21.6 13 21V14C13 13.4 13.4 13 14 13Z" fill="currentColor"/>
                        <path d="M3 13H10C10.6 13 11 13.4 11 14V21C11 21.6 10.6 22 10 22H3C2.4 22 2 21.6 2 21V14C2 13.4 2.4 13 3 13Z" fill="currentColor"/>
                    </svg>
                </span>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ menus.length }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Total Menu</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-4 col-md-6 animate-item" style="--delay: 0.1s">
        <div class="card bg-body hover-elevate-up border-0 h-100 theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
             <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-danger text-danger rounded-4">
                <span class="svg-icon svg-icon-2x svg-icon-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path opacity="0.3" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="currentColor"/>
                        <path d="M10.5858 13.4142L9.17157 12L10.5858 10.5858C11.3668 9.80473 12.6332 9.80473 13.4142 10.5858L14.8284 12L13.4142 13.4142C12.6332 14.1953 11.3668 14.1953 10.5858 13.4142Z" fill="currentColor"/>
                        <rect x="11" y="16" width="2" height="2" rx="1" fill="currentColor"/>
                        <rect x="11" y="7" width="2" height="6" rx="1" fill="currentColor"/>
                    </svg>
                </span>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ lowStockCount }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Stok Menipis (&lt;5)</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm theme-card animate-item position-relative" style="--delay: 0.2s; z-index: 99;">
        <div class="card-body py-4">
            <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-4">
                
                <div class="d-flex align-items-center position-relative w-100 w-sm-300px">
                    <i class="ki-duotone ki-magnifier fs-2 position-absolute ms-4 text-gray-500"><span class="path1"></span><span class="path2"></span></i>
                    <input type="text" v-model="searchQuery" class="form-control form-control-solid ps-12 search-input" placeholder="Cari menu..." />
                </div>

                <div class="d-flex flex-wrap gap-3 align-items-center w-100 w-sm-auto justify-content-end">
                    
                    <div class="dropdown-wrapper position-relative w-150px" v-click-outside="() => closeDropdown()">
                        <button 
                            class="btn btn-custom-select w-100 d-flex align-items-center justify-content-between px-4" 
                            type="button" 
                            @click="toggleDropdown()"
                            :class="{ 'active': isDropdownOpen }"
                        >
                            <div class="d-flex align-items-center text-truncate">
                                <i class="ki-duotone ki-category fs-2 me-2 text-gray-500"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                <span class="fw-bold text-gray-700 fs-7">{{ activeCategoryLabel }}</span>
                            </div>
                            <i class="ki-duotone ki-down fs-5 text-gray-500 ms-2 transition-icon" :class="{ 'rotate-180': isDropdownOpen }"></i>
                        </button>

                        <transition name="dropdown-anim">
                            <div v-if="isDropdownOpen" class="custom-dropdown-menu shadow-lg border-0 p-2 rounded-3 theme-dropdown">
                                <ul class="list-unstyled m-0">
                                    <li>
                                        <a href="#" class="dropdown-item-custom rounded px-3 py-2 fw-semibold mb-1" 
                                           :class="{ 'selected': filterCategory === 'all' }" 
                                           @click.prevent="setFilterCategory('all')">
                                            Semua Kategori
                                        </a>
                                    </li>
                                    <li v-for="cat in uniqueCategories" :key="cat">
                                        <a href="#" class="dropdown-item-custom rounded px-3 py-2 fw-semibold mb-1" 
                                           :class="{ 'selected': filterCategory === cat }" 
                                           @click.prevent="setFilterCategory(cat)">
                                            {{ cat }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </transition>
                    </div>

                    <button v-if="userHasPermission('create menus')" class="btn btn-sm btn-orange fw-bold hover-scale box-shadow-orange" @click="openAddModal">
                        <i class="ki-duotone ki-plus fs-2 text-white"></i> Tambah Menu
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

        <div v-else-if="filteredMenus.length === 0" class="d-flex flex-column align-items-center justify-content-center py-15 text-muted animate-fade-in">
             <div class="symbol symbol-100px mb-5 bg-light-orange rounded-circle d-flex align-items-center justify-content-center">
                <i class="ki-duotone ki-coffee fs-4x text-orange"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span></i>
             </div>
             <span class="fs-4 fw-bold text-gray-800">Tidak ada menu ditemukan.</span>
        </div>

        <div v-else>
            <TransitionGroup name="fade-grid" tag="div" class="row g-6">
                <div class="col-md-6 col-lg-4 col-xl-3" v-for="menu in filteredMenus" :key="menu.id">
                    
                    <div class="card h-100 border-0 shadow-sm theme-card hover-elevate-up transition-300 group-card">
                        
                        <div class="position-relative h-200px bg-secondary rounded-top-3 overflow-hidden room-image-container">
                             <img :src="menu.image_url || '/media/svg/files/blank-image.svg'" class="w-100 h-100 object-fit-cover room-img" :alt="menu.name" />
                             
                             <div class="overlay-layer position-absolute w-100 h-100 transition-300 z-index-1"></div>
                             
                             <div class="position-absolute bottom-0 end-0 m-3 z-index-2">
                                <div class="backdrop-blur px-3 py-1 rounded-pill shadow-sm price-badge">
                                    <span class="fw-bolder fs-7 text-white">{{ formatCurrency(menu.price) }}</span>
                                </div>
                             </div>

                             <div class="position-absolute top-0 start-0 m-3 z-index-2 status-badge-hover">
                                <span class="badge fw-bold fs-8 shadow-sm text-uppercase" 
                                      :class="getCategoryBadgeClass(menu.category)">
                                    {{ menu.category }}
                                </span>
                             </div>
                        </div>

                        <div class="card-body p-5 d-flex flex-column">
                            
                            <div class="mb-3">
                                <a href="#" class="fs-4 fw-bolder text-gray-900 text-hover-orange transition-200 mb-1 d-block" @click.prevent="openEditModal(menu)">
                                    {{ menu.name }}
                                </a>
                                
                                <div class="d-flex align-items-center mt-2">
                                    <div class="progress h-6px w-100 me-2 bg-light rounded-pill">
                                        <div class="progress-bar rounded-pill" role="progressbar" 
                                             :style="{ width: getStockPercentage(menu.stock) + '%' }" 
                                             :class="getStockColor(menu.stock)">
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                         <span v-if="menu.stock < 5" class="me-1 animate-pulse svg-icon svg-icon-danger" title="Stok Menipis!">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                <rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10" fill="currentColor"/>
                                                <rect x="11" y="14" width="2" height="2" rx="1" transform="rotate(180 11 14)" fill="currentColor"/>
                                                <rect x="11" y="17" width="2" height="2" rx="1" transform="rotate(180 11 17)" fill="currentColor"/>
                                                <path d="M11 11V7C11 6.4 11.4 6 12 6C12.6 6 13 6.4 13 7V11C13 11.6 12.6 12 12 12C11.4 12 11 11.6 11 11Z" fill="currentColor"/>
                                            </svg>
                                         </span>
                                         <span class="fs-9 fw-bold text-nowrap" :class="getStockTextColor(menu.stock)">
                                            {{ menu.stock }} Unit
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto d-flex justify-content-center gap-2 w-100 border-top border-dashed border-gray-300 pt-4">
                                <button v-if="userHasPermission('edit menus')" @click="openEditModal(menu)" class="btn btn-sm btn-light btn-active-light-orange fw-bold flex-grow-1">
                                    <i class="ki-duotone ki-pencil fs-5"><span class="path1"></span><span class="path2"></span></i> Edit
                                </button>
                                <button v-if="userHasPermission('delete menus')" @click="deleteMenu(menu.id)" class="btn btn-sm btn-light btn-active-light-danger fw-bold flex-grow-1">
                                    <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i> Hapus
                                </button>
                            </div>

                        </div>
                    </div>

                </div>
            </TransitionGroup>
        </div>
    </div>

    <MenuModal :menu-data="selectedMenu" @menu-updated="refreshData" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import axios from "@/libs/axios";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import MenuModal from "./MenuModal.vue";

interface Menu {
  id: number;
  name: string;
  category: string;
  price: number;
  stock: number;
  image_url: string | null;
}

const authStore = useAuthStore();
const menus = ref<Menu[]>([]);
const loading = ref(true);
const selectedMenu = ref<Menu | null>(null);
const searchQuery = ref("");
const filterCategory = ref('all');
const isDropdownOpen = ref(false);

const userHasPermission = (permission: string): boolean => {
  return authStore.user?.all_permissions?.includes(permission) ?? false;
};

// --- Dropdown Logic ---
const toggleDropdown = () => { isDropdownOpen.value = !isDropdownOpen.value; };
const closeDropdown = () => { isDropdownOpen.value = false; };
const setFilterCategory = (cat: string) => { filterCategory.value = cat; closeDropdown(); };

const vClickOutside = {
    mounted(el: any, binding: any) {
        el.clickOutsideEvent = function(event: Event) { if (!(el === event.target || el.contains(event.target))) binding.value(event, el); };
        document.body.addEventListener('click', el.clickOutsideEvent);
    },
    unmounted(el: any) { document.body.removeEventListener('click', el.clickOutsideEvent); },
};

// --- Computed ---
const activeCategoryLabel = computed(() => filterCategory.value === 'all' ? 'Semua Kategori' : filterCategory.value);

const uniqueCategories = computed(() => {
    const cats = menus.value.map(m => m.category);
    return [...new Set(cats)].filter(Boolean);
});

const filteredMenus = computed(() => {
    let result = menus.value;
    if (searchQuery.value) {
        result = result.filter(m => m.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
    }
    if (filterCategory.value !== 'all') {
        result = result.filter(m => m.category === filterCategory.value);
    }
    return result;
});

const lowStockCount = computed(() => menus.value.filter(m => m.stock < 5).length);

// --- Utils ---
const formatCurrency = (value: number) => {
  if (!value || isNaN(value)) return "Rp 0";
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
};

const getCategoryBadgeClass = (cat: string) => {
    const map: any = {
        'Makanan': 'bg-success text-white',
        'Minuman': 'bg-info text-white',
        'Snack': 'bg-warning text-white'
    };
    return map[cat] || 'bg-secondary text-gray-700';
};

const getStockPercentage = (stock: number) => Math.min((stock / 50) * 100, 100); 
const getStockColor = (stock: number) => stock < 5 ? 'bg-danger' : (stock < 15 ? 'bg-warning' : 'bg-success');
const getStockTextColor = (stock: number) => stock < 5 ? 'text-danger' : 'text-gray-500';

// --- Actions ---
const getMenus = async () => {
  try {
    loading.value = true;
    const response = await axios.get("/menus");
    menus.value = response.data;
  } catch (error) {
    console.error("Gagal mengambil data menu:", error);
  } finally {
    loading.value = false;
  }
};

const refreshData = () => { getMenus(); };

const openAddModal = () => {
  selectedMenu.value = null;
  const modalEl = document.getElementById("kt_modal_menu");
  if (modalEl) new Modal(modalEl).show();
};

const openEditModal = (menu: Menu) => {
  selectedMenu.value = { ...menu };
  const modalEl = document.getElementById("kt_modal_menu");
  if (modalEl) new Modal(modalEl).show();
};

const deleteMenu = (id: number) => {
  Swal.fire({
    text: "Hapus menu ini?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    customClass: { confirmButton: "btn fw-bold btn-danger", cancelButton: "btn fw-bold btn-light" },
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await axios.delete(`/menus/${id}`);
        Swal.fire("Berhasil!", "Menu dihapus.", "success");
        refreshData();
      } catch (error) {
        Swal.fire("Error", "Gagal menghapus.", "error");
      }
    }
  });
};

onMounted(() => { getMenus(); });
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
   CARD & IMAGE
   ======================== */
.group-card { position: relative; z-index: 1; transition: all 0.3s ease; }
.group-card:hover { z-index: 10; }
.hover-elevate-up:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.12) !important; }

.room-image-container { position: relative; overflow: hidden; }
.room-img { transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1), filter 0.4s ease; }
.group-card:hover .room-img { transform: scale(1.08); } 

.overlay-layer { background: linear-gradient(to top, rgba(0,0,0,0.5) 0%, rgba(0,0,0,0) 40%); opacity: 0.6; transition: opacity 0.3s ease; }
.group-card:hover .overlay-layer { opacity: 0.8; }

.price-badge { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.group-card:hover .price-badge { transform: translateY(-5px) scale(1.05); }

.status-badge-hover { opacity: 0; transform: translateY(-10px); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.group-card:hover .status-badge-hover { opacity: 1; transform: translateY(0); }

.backdrop-blur { background: rgba(30, 30, 45, 0.75); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.15); }

/* ========================
   CUSTOM DROPDOWN (Z-INDEX FIXED)
   ======================== */
.dropdown-wrapper { position: relative; z-index: 100; }
.dropdown-wrapper:has(.custom-dropdown-menu) { z-index: 9999 !important; }

.btn-custom-select {
    background-color: #F9F9F9;
    border: 1px solid transparent;
    border-radius: 12px;
    height: 42px;
    transition: all 0.3s ease;
}
.btn-custom-select:hover, .btn-custom-select.active { background-color: #ffffff; border-color: #F68B1E; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.transition-icon { transition: transform 0.3s ease; }
.rotate-180 { transform: rotate(180deg); color: #F68B1E !important; }

.custom-dropdown-menu {
    position: absolute; top: 110%; left: 0; width: 100%; min-width: 180px;
    background: white; z-index: 99999 !important; border: 1px solid rgba(0,0,0,0.05);
}
.dropdown-item-custom { display: block; text-decoration: none; color: #5E6278; transition: all 0.2s ease; cursor: pointer; }
.dropdown-item-custom:hover { background-color: #F5F8FA; color: #F68B1E; transform: translateX(5px); }
.dropdown-item-custom.selected { background-color: #FFF4E6; color: #F68B1E; font-weight: 700; }

.dropdown-anim-enter-active { animation: dropdown-in 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.dropdown-anim-leave-active { animation: dropdown-out 0.2s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes dropdown-in { 0% { opacity: 0; transform: translateY(-10px) scale(0.95); } 100% { opacity: 1; transform: translateY(0) scale(1); } }
@keyframes dropdown-out { 0% { opacity: 1; transform: translateY(0) scale(1); } 100% { opacity: 0; transform: translateY(-10px) scale(0.95); } }

/* ========================
   ANIMATIONS
   ======================== */
.animate-item { opacity: 0; animation: fadeUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; animation-delay: var(--delay, 0s); }
@keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.fade-grid-enter-active, .fade-grid-leave-active { transition: all 0.4s ease; }
.fade-grid-enter-from, .fade-grid-leave-to { opacity: 0; transform: translateY(20px); }

.animate-pulse { animation: pulse 2s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }

/* ========================
   DARK MODE
   ======================== */
[data-bs-theme="dark"] .theme-card { background-color: #1e1e2d !important; color: #ffffff; }
[data-bs-theme="dark"] .theme-dropdown { background-color: #1e1e2d; border-color: #323248; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-gray-700 { color: #CDCDDE !important; }
[data-bs-theme="dark"] .bg-body { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .bg-light-subtle { background-color: #1b1b29 !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .bg-light-orange { background-color: #2b2b40 !important; }
[data-bs-theme="dark"] .bg-light-success { background-color: rgba(23, 198, 83, 0.15) !important; }
[data-bs-theme="dark"] .bg-light-danger { background-color: rgba(248, 40, 90, 0.15) !important; }
[data-bs-theme="dark"] .border-gray-200, [data-bs-theme="dark"] .border-gray-300 { border-color: #2B2B40 !important; }
[data-bs-theme="dark"] .form-control-solid { background-color: #1b1b29 !important; border-color: #323248 !important; color: #ffffff; }
[data-bs-theme="dark"] .btn-custom-select { background-color: #1b1b29; border-color: #323248; color: #CDCDDE; }
[data-bs-theme="dark"] .btn-custom-select:hover, [data-bs-theme="dark"] .btn-custom-select.active { border-color: #F68B1E; color: #ffffff; }
[data-bs-theme="dark"] .custom-dropdown-menu { background-color: #1e1e2d; border: 1px solid #323248; }
[data-bs-theme="dark"] .dropdown-item-custom { color: #9A9CAE; }
[data-bs-theme="dark"] .dropdown-item-custom:hover { background-color: #2b2b40; color: #F68B1E; }
[data-bs-theme="dark"] .dropdown-item-custom.selected { background-color: rgba(246, 139, 30, 0.15); }
[data-bs-theme="dark"] .overlay-layer { background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 50%); }
[data-bs-theme="dark"] .backdrop-blur { background: rgba(40, 40, 60, 0.85); }
</style>