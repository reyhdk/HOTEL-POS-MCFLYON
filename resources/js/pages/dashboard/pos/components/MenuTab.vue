<template>
  <div class="d-flex flex-column h-100">
    <!-- Tambahkan align-items-md-center disini -->
    <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 pb-4 border-bottom mb-4">
      <div class="position-relative flex-grow-1">
        <i class="bi bi-search position-absolute top-50 ms-4 translate-middle-y text-gray-400 fs-6"></i>
        <input type="text" v-model="searchQuery" class="form-control form-control-solid ps-12 rounded-pill border-0 h-45px bg-light" placeholder="Cari menu makanan atau minuman..." />
      </div>
      <div class="d-flex align-items-center gap-2">
        <div class="w-100 w-md-250px">
          <el-select v-model="selectedCategory" placeholder="Semua Kategori" class="w-100 custom-el-select h-45px" clearable>
            <template #prefix><i class="bi bi-funnel text-orange fs-5"></i></template>
            <el-option v-for="cat in uniqueCategories" :key="cat" :label="cat" :value="cat" />
          </el-select>
        </div>
        
        <button @click="fetchMenus" class="btn btn-light-primary btn-icon w-45px h-45px rounded-3 shadow-sm hover-elevate-up flex-shrink-0" title="Refresh Menu" :disabled="loading">
          <i class="bi bi-arrow-clockwise fs-2" :class="{ 'spin': loading }"></i>
        </button>
      </div>
    </div>

    <div class="flex-grow-1 custom-scroll overflow-auto">
      <div v-if="loading" class="py-20 text-center">
        <div class="spinner-border text-orange mb-3" role="status"></div>
        <p class="text-gray-500 fw-semibold">Sedang memuat menu...</p>
      </div>
      
      <div v-else-if="paginatedMenus.length === 0" class="d-flex flex-column align-items-center justify-content-center py-20 bg-light rounded-4 border border-dashed border-gray-300 mx-5">
        <i class="bi bi-search fs-3x text-gray-300 mb-4"></i>
        <h4 class="text-gray-600 fw-bold">Menu tidak ditemukan</h4>
        <p class="text-gray-400">Coba kata kunci lain atau ubah kategori.</p>
      </div>

      <div v-else class="row g-5 pb-5 px-1">
        <div class="col-6 col-md-4 col-xxl-3" v-for="menu in paginatedMenus" :key="menu.id">
          <div 
            class="card h-100 border-0 shadow-sm rounded-4 cursor-pointer hover-elevate-up group-hover overflow-hidden transition-all bg-white"
            @click="$emit('add-to-cart', menu)"
            :class="{ 'opacity-50 grayscale': menu.stock <= 0 }"
          >
            <div class="position-relative h-140px overflow-hidden">
              <img :src="menu.image_url || '/media/svg/files/blank-image.svg'" class="w-100 h-100 object-fit-cover transition-transform group-hover-scale" />
              <div class="position-absolute top-0 end-0 m-2">
                  <span :class="menu.stock <= 0 ? 'bg-danger' : 'bg-orange'" class="badge shadow-sm fw-bold px-3 py-2 rounded-pill">
                    {{ menu.stock <= 0 ? 'Habis' : menu.stock + ' Porsi' }}
                  </span>
              </div>
            </div>
            <div class="p-4 d-flex flex-column h-100">
              <h6 class="fw-bolder text-gray-800 line-clamp-1 mb-1 fs-6">{{ menu.name }}</h6>
              <span class="text-gray-400 fs-9 fw-bold d-block mb-3 text-uppercase tracking-wider">{{ menu.category }}</span>
              <div class="mt-auto d-flex justify-content-between align-items-center bg-light rounded-3 p-2">
                <span class="text-orange fw-bolder fs-6">{{ formatCurrency(menu.price) }}</span>
                <button class="btn btn-icon btn-sm btn-orange rounded-circle shadow-sm w-30px h-30px d-flex align-items-center justify-content-center border-0 p-0">
                    <i class="bi bi-plus-lg text-white fs-4"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-auto pt-4 border-top">
        <span class="text-gray-600 fs-7">
            Halaman {{ page }} dari {{ totalPages }} (Total {{ filteredMenus.length }} Menu)
        </span>
        <div class="btn-group">
            <button class="btn btn-sm btn-light-primary fw-bold" :disabled="page === 1" @click="page--">
                <i class="bi bi-chevron-left"></i> Prev
            </button>
            <button class="btn btn-sm btn-light-primary fw-bold" :disabled="page >= totalPages" @click="page++">
                Next <i class="bi bi-chevron-right"></i>
            </button>
        </div>
     </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import ApiService from "@/core/services/ApiService";

const emit = defineEmits(['add-to-cart']);

const menus = ref<any[]>([]);
const loading = ref(true);
const searchQuery = ref("");
const selectedCategory = ref("");
const page = ref(1);
const perPage = 8;

const uniqueCategories = computed(() => [...new Set(menus.value.map(m => m.category))].filter(Boolean));

const filteredMenus = computed(() => menus.value.filter(m => 
    m.name.toLowerCase().includes(searchQuery.value.toLowerCase()) && 
    (selectedCategory.value ? m.category === selectedCategory.value : true)
));

const totalPages = computed(() => Math.ceil(filteredMenus.value.length / perPage));
const paginatedMenus = computed(() => {
    const start = (page.value - 1) * perPage;
    return filteredMenus.value.slice(start, start + perPage);
});

watch([searchQuery, selectedCategory], () => { page.value = 1 });

const formatCurrency = (v: number) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(v);

const fetchMenus = async () => {
    try { 
      loading.value = true; 
      const { data } = await ApiService.get("/menus"); 
      menus.value = data || []; 
    } 
    finally { loading.value = false; }
};

onMounted(() => {
  fetchMenus();
});
</script>

<style scoped>
.text-orange { color: #ff6b00 !important; }
.bg-orange { background-color: #ff6b00 !important; }
.btn-orange { background: #ff6b00; color: white; border: none; transition: all 0.2s; }
.btn-orange:hover { background: #e05e00; transform: scale(1.1); }

/* Custom Light Blue Button */
.btn-light-orange { background-color: #f0f7ff; color: #0d6efd; border: 1px solid transparent; transition: all 0.3s; }
.btn-light-orange:hover, .btn-light-orange:active { background-color: #0d6efd; color: white; border-color: #0d6efd; }
.btn-light-orange .bi { transition: color 0.3s; }

/* FIX ALIGNMENT EL-SELECT */
:deep(.custom-el-select .el-select__wrapper) {
    min-height: 45px !important;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
}

.hover-elevate-up:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
.group-hover:hover .group-hover-scale { transform: scale(1.1); }
.grayscale { filter: grayscale(100%); }
.transition-all { transition: all 0.3s ease; }

.spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* Adaptive Light/Dark Mode */
.form-control-solid { background-color: #f5f5f5; border-color: #e5e7eb; color: #1f2937; }
[data-bs-theme="dark"] .btn-light-orange { background-color: rgba(13,110,253,0.12) !important; color: #74b6ff !important; border-color: transparent !important; }
[data-bs-theme="dark"] .btn-light-orange:hover, [data-bs-theme="dark"] .btn-light-orange:active { background-color: #0d6efd !important; color: white !important; border-color: #0d6efd !important; }

[data-bs-theme="dark"] .form-control-solid {
    background-color: #1b1b29 !important;
    border-color: #323248 !important;
    color: #ffffff;
}
[data-bs-theme="dark"] .text-gray-500 { color: #cdcdde !important; }
[data-bs-theme="dark"] .text-gray-400 { color: #9a9cae !important; }
[data-bs-theme="dark"] .text-gray-600 { color: #9a9cae !important; }
[data-bs-theme="dark"] .text-gray-300 { color: #5a5c6f !important; }
[data-bs-theme="dark"] .text-gray-800 { color: #ffffff !important; }
[data-bs-theme="dark"] .bg-light { background-color: #2b2b40 !important; }
[data-bs-theme="dark"] .border-gray-300 { border-color: #323248 !important; }
[data-bs-theme="dark"] .card { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] :deep(.el-select__wrapper) {
    background-color: #1b1b29 !important;
    border-color: #323248 !important;
}
[data-bs-theme="dark"] :deep(.el-select__wrapper.is-focus) {
    border-color: #ff6b00 !important;
}
[data-bs-theme="dark"] :deep(.el-select-dropdown) {
    background-color: #1e1e2d !important;
}
[data-bs-theme="dark"] :deep(.el-select-dropdown__item) {
    color: #cdcdde;
}
[data-bs-theme="dark"] :deep(.el-select-dropdown__item:hover) {
    background-color: #2b2b40 !important;
}
</style>