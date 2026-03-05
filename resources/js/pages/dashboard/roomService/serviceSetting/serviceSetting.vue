<template>
  <div class="d-flex flex-column gap-5 anim-page-in">
    
    <!-- Header -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden anim-slide-down">
      <div class="card-body py-5 d-flex flex-column flex-md-row align-items-center justify-content-between gap-4 bg-header-gradient">
        <div class="d-flex align-items-center z-index-1">
          <div class="symbol symbol-50px me-4">
            <div class="symbol-label bg-white bg-opacity-20 text-white rounded-circle">
              <i class="bi bi-box-seam fs-2"></i>
            </div>
          </div>
          <div>
            <h1 class="fs-2 fw-bold text-white m-0 lh-1">Pengaturan Barang Layanan</h1>
            <div class="text-white text-opacity-75 fw-semibold fs-7 mt-1">Kelola Amenities, Housekeeping & Laundry</div>
          </div>
        </div>
        <div class="d-flex gap-4 z-index-1">
          <button @click="openModal()" class="btn btn-light text-orange fw-bold rounded-pill shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Tambah Barang
          </button>
        </div>
      </div>
    </div>

    <!-- Konten & Filter -->
    <div class="card card-flush shadow-sm rounded-4 border-0 card-adaptive anim-slide-up" style="--delay: 0.1s">
      <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <div class="card-title">
          <div class="d-flex align-items-center position-relative my-1">
            <i class="bi bi-search fs-3 position-absolute ms-4 text-gray-400"></i>
            <input 
              type="text" 
              v-model="filters.search" 
              @input="handleSearch" 
              class="form-control form-control-solid w-250px ps-12 rounded-pill" 
              placeholder="Cari Barang..." 
            />
          </div>
        </div>
        
        <div class="card-toolbar flex-row-fluid justify-content-end gap-3">
          <!-- Dropdown Filter Modern (Element Plus) -->
          <div class="w-200px">
            <el-select v-model="filters.category" @change="fetchItems(1)" placeholder="Pilih Kategori" class="premium-filter-select w-100" size="large">
              <template #prefix>
                <div class="d-flex align-items-center gap-2 ps-1">
                  <i class="ki-duotone ki-category fs-3 text-gray-500">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                  </i>
                  <span class="fs-9 fw-bold text-gray-500 text-uppercase ls-1">Kategori</span>
                </div>
              </template>
              <el-option label="Semua Kategori" value="" />
              <el-option v-for="cat in availableCategories" :key="cat" :label="cat" :value="cat" />
            </el-select>
          </div>
        </div>
      </div>

      <div class="card-body pt-0">
        
        <!-- Loading State -->
        <div v-if="isLoading" class="d-flex justify-content-center align-items-center py-10">
          <div class="spinner-border text-orange" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="items.length === 0" class="text-center py-10">
          <div class="symbol symbol-100px mb-4">
            <div class="symbol-label bg-secondary bg-opacity-10 rounded-circle">
              <i class="bi bi-box text-muted" style="font-size: 3rem;"></i>
            </div>
          </div>
          <h4 class="fw-bold">Tidak ada data barang</h4>
          <p class="text-muted fs-6">Silakan tambahkan barang layanan baru atau ubah filter pencarian.</p>
        </div>

        <!-- Card Grid Layout (Kotak-Kotak Kompak & Support Dark Mode) -->
        <div v-else class="row g-4">
          <div class="col-12 col-sm-6 col-md-4 col-xl-3" v-for="item in items" :key="item.id">
            <div class="card h-100 shadow-sm border border-secondary border-opacity-10 hover-elevate-up overflow-hidden rounded-4">
              
              <!-- Card Image / Header -->
              <div class="position-relative w-100" style="height: 150px;">
                <img v-if="item.photo_url" :src="item.photo_url" :alt="item.name" class="w-100 h-100 object-fit-cover" />
                <div v-else class="w-100 h-100 d-flex justify-content-center align-items-center bg-secondary bg-opacity-10">
                  <i class="bi bi-card-image text-muted" style="font-size: 3.5rem; opacity: 0.4;"></i>
                </div>
                
                <!-- Floating Badges -->
                <div class="position-absolute top-0 start-0 m-3 d-flex flex-column gap-2">
                  <span v-if="item.is_active" class="badge bg-success shadow-sm px-3 py-2 rounded-pill fs-9">Aktif</span>
                  <span v-else class="badge bg-danger shadow-sm px-3 py-2 rounded-pill fs-9">Nonaktif</span>
                </div>
                <div class="position-absolute top-0 end-0 m-3 d-flex flex-column align-items-end gap-2">
                  <span class="badge bg-dark shadow-sm px-3 py-2 rounded-pill fs-9 bg-opacity-75">{{ item.category }}</span>
                  <span class="badge bg-primary shadow-sm px-3 py-2 rounded-pill fs-9 fw-bold">Max: {{ item.max_quantity }}</span>
                </div>
              </div>

              <!-- Card Body (Lebih padat) -->
              <div class="card-body p-4 d-flex flex-column">
                <h5 class="fw-bold mb-1 fs-5 text-truncate" :title="item.name">{{ item.name }}</h5>
                <p class="text-muted fs-8 mb-4 item-desc">
                  {{ item.description || 'Tidak ada deskripsi tambahan.' }}
                </p>

                <!-- Status Terhubung dengan Gudang -->
                <div v-if="item.warehouse_item" class="d-flex align-items-center gap-2 mb-4 p-2 bg-light-info rounded border border-info border-opacity-25">
                  <i class="bi bi-box-seam text-info fs-4"></i>
                  <div class="d-flex flex-column overflow-hidden">
                    <span class="fs-9 text-info fw-bolder text-uppercase">Link Gudang:</span>
                    <span class="fs-8 text-dark fw-semibold text-truncate" :title="item.warehouse_item.name">
                      {{ item.warehouse_item.name }}
                    </span>
                  </div>
                </div>

                <!-- Spacer untuk mendorong footer ke bawah -->
                <div class="mt-auto">
                  
                  <!-- Petugas Info (Dibuat ringkas) -->
                  <div class="d-flex align-items-center mb-4 p-2 px-3 bg-secondary bg-opacity-10 rounded-3 border border-secondary border-opacity-10">
                    <div class="symbol symbol-30px symbol-circle me-3 bg-primary bg-opacity-10 text-primary fw-bold fs-7 d-flex justify-content-center align-items-center flex-shrink-0">
                      {{ item.assigned_user ? item.assigned_user.name.charAt(0).toUpperCase() : '?' }}
                    </div>
                    <div class="d-flex flex-column overflow-hidden">
                      <span class="text-muted fs-9 fw-semibold">Ditugaskan Kepada:</span>
                      <span class="fs-8 fw-bold text-truncate" :title="item.assigned_user ? item.assigned_user.name : 'Belum Ditugaskan'">
                        {{ item.assigned_user ? item.assigned_user.name : 'Belum Ditugaskan' }}
                      </span>
                    </div>
                  </div>

                  <!-- Actions -->
                  <div class="d-flex gap-2">
                    <button @click="openModal(item)" class="btn btn-secondary bg-opacity-10 text-primary btn-sm flex-grow-1 fw-bold border-0" :disabled="deletingItemId === item.id">
                      <i class="bi bi-pencil me-1"></i> Edit
                    </button>
                    <button @click="deleteItem(item.id)" class="btn btn-secondary bg-opacity-10 text-danger btn-sm flex-grow-1 fw-bold border-0" :disabled="deletingItemId === item.id">
                      <span v-if="deletingItemId === item.id" class="spinner-border spinner-border-sm"></span>
                      <span v-else><i class="bi bi-trash me-1"></i> Hapus</span>
                    </button>
                  </div>

                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Custom Pagination -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-8 px-2" v-if="!isLoading && pagination.last_page > 1">
          <div class="text-muted fs-7 fw-semibold mb-3 mb-md-0">
            Menampilkan {{ (pagination.current_page - 1) * pagination.per_page + 1 }} sampai 
            {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} 
            dari total {{ pagination.total }} data
          </div>
          
          <ul class="pagination pagination-circle m-0">
            <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
              <a class="page-link" href="#" @click.prevent="fetchItems(pagination.current_page - 1)">
                <i class="bi bi-chevron-left"></i>
              </a>
            </li>
            
            <li class="page-item" v-for="page in visiblePages" :key="page" :class="{ active: pagination.current_page === page, disabled: page === '...' }">
              <a class="page-link" href="#" @click.prevent="page !== '...' ? fetchItems(page as number) : null">{{ page }}</a>
            </li>

            <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
              <a class="page-link" href="#" @click.prevent="fetchItems(pagination.current_page + 1)">
                <i class="bi bi-chevron-right"></i>
              </a>
            </li>
          </ul>
        </div>

      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showModal" class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold fs-3">{{ isEditing ? 'Edit Barang' : 'Tambah Barang Baru' }}</h5>
            <button type="button" class="btn-close" @click="closeModal" :disabled="isSaving"></button>
          </div>
          <div class="modal-body p-6">
            <form @submit.prevent="saveItem">
              
              <div class="mb-5">
                <label class="form-label fw-semibold fs-6">Foto Barang</label>
                <div class="d-flex align-items-center gap-4">
                  <div class="symbol symbol-100px rounded-4 overflow-hidden border border-dashed border-secondary flex-shrink-0 bg-secondary bg-opacity-10">
                    <img v-if="photoPreview" :src="photoPreview" class="w-100 h-100 object-fit-cover" />
                    <div v-else class="w-100 h-100 d-flex justify-content-center align-items-center">
                      <i class="bi bi-card-image text-muted fs-1"></i>
                    </div>
                  </div>
                  <div class="flex-grow-1">
                    <input type="file" ref="fileInput" @change="handleFileChange" class="form-control form-control-solid" accept="image/*" />
                    <div class="form-text text-muted fs-8 mt-2">Format yang diizinkan: JPG, PNG, WEBP. Maks: 2MB. Resolusi disarankan 1:1.</div>
                  </div>
                </div>
              </div>

              <div class="mb-5">
                <label class="form-label fw-semibold fs-6">Nama Barang</label>
                <input type="text" v-model="form.name" class="form-control form-control-solid" required placeholder="Cth: Handuk Tambahan">
              </div>
              
              <div class="row mb-5">
                <div class="col-12 col-md-7 mb-5 mb-md-0">
                  <label class="form-label fw-semibold fs-6">Kategori Layanan</label>
                  <div class="d-flex gap-2 align-items-center">
                    <el-select v-model="form.category" placeholder="Pilih Kategori..." class="modern-form-select w-100">
                      <el-option v-for="cat in availableCategories" :key="cat" :label="cat" :value="cat" />
                    </el-select>
                    <button type="button" @click="addNewCategory" class="btn btn-icon btn-secondary bg-opacity-10 text-orange rounded flex-shrink-0 border-0" style="height: 40px; width: 40px;" title="Tambah Kategori Baru">
                      <i class="bi bi-plus-lg"></i>
                    </button>
                  </div>
                </div>
                <div class="col-12 col-md-5">
                  <label class="form-label fw-semibold fs-6">Maksimal Qty Per Request</label>
                  <input type="number" v-model="form.max_quantity" class="form-control form-control-solid" style="height: 40px;" min="1" required>
                </div>
              </div>

              <div class="mb-5">
                <label class="form-label fw-semibold fs-6">Petugas Bertanggung Jawab</label>
                <el-select v-model="form.assigned_user_id" placeholder="-- Tidak Ditugaskan --" class="modern-form-select w-100" clearable>
                  <el-option :value="null" label="-- Tidak Ditugaskan --" />
                  <el-option v-for="staff in staffList" :key="staff.id" :label="staff.name" :value="staff.id" />
                </el-select>
                <div class="form-text text-muted fs-8 mt-2">Hanya menampilkan user dengan Role Housekeeping.</div>
              </div>

              <!-- Integrasi Gudang dengan Filter Kategori -->
              <div class="row mb-5 border rounded p-4 mx-0 bg-light-info bg-opacity-25 border-info border-opacity-25">
                <div class="col-12 mb-4">
                  <h6 class="fw-bold text-info m-0 d-flex align-items-center">
                    <i class="bi bi-box-seam fs-4 me-2"></i> Integrasi Gudang (Opsional)
                  </h6>
                  <div class="text-muted fs-8 mt-1">
                    Pilih barang dari gudang agar stok otomatis terpotong saat pesanan diselesaikan oleh petugas.
                  </div>
                </div>
                
                <div class="col-12 col-md-6 mb-4 mb-md-0">
                  <label class="form-label fw-semibold fs-7">Filter Kategori Gudang</label>
                  <el-select v-model="form.warehouse_category_filter" placeholder="Semua Kategori Gudang" class="modern-form-select w-100" clearable @change="form.warehouse_item_id = null">
                    <el-option v-for="cat in warehouseCategories" :key="cat.value" :label="cat.label" :value="cat.value" />
                  </el-select>
                </div>
                
                <div class="col-12 col-md-6">
                  <label class="form-label fw-semibold fs-7">Pilih Barang Gudang</label>
                  <el-select v-model="form.warehouse_item_id" placeholder="-- Tidak Dihubungkan --" class="modern-form-select w-100" filterable clearable>
                    <el-option :value="null" label="-- Tidak Dihubungkan --" />
                    <!-- Gunakan filteredWarehouseList hasil filter dari computed property -->
                    <el-option v-for="wItem in filteredWarehouseList" :key="wItem.id" :label="`${wItem.name} (Stok: ${wItem.current_stock})`" :value="wItem.id" />
                  </el-select>
                </div>
              </div>
              
              <div class="mb-5">
                <label class="form-label fw-semibold fs-6">Deskripsi Tambahan</label>
                <textarea v-model="form.description" class="form-control form-control-solid" rows="3" placeholder="Contoh: Bantal ekstra dengan sarung putih (Opsional)"></textarea>
              </div>
              
              <div class="form-check form-switch mb-5">
                <input class="form-check-input" type="checkbox" v-model="form.is_active" id="isActive" style="width: 2.5rem; height: 1.25rem;">
                <label class="form-check-label fw-semibold fs-6 ms-3" style="margin-top: 2px;" for="isActive">Barang Aktif (Tersedia untuk direquest tamu)</label>
              </div>
              
              <div class="d-flex justify-content-end mt-8 border-top pt-5">
                <button type="button" class="btn btn-secondary bg-opacity-10 me-3 px-6 border-0" @click="closeModal" :disabled="isSaving">Batal</button>
                <button type="submit" class="btn btn-orange px-8" :disabled="isSaving">
                  <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span> 
                  {{ isSaving ? 'Menyimpan...' : 'Simpan Data' }}
                </button>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

// State Utama
const items = ref<any[]>([]);
const fetchedCategories = ref<string[]>([]);
const staffList = ref<any[]>([]);

// State untuk Gudang
const warehouseList = ref<any[]>([]); 
const warehouseCategories = ref<any[]>([]);

// State Pagination
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 15
});

// Loading States
const isLoading = ref(false);
const isSaving = ref(false);
const deletingItemId = ref<number | null>(null);

// Modal States & File Upload
const showModal = ref(false);
const isEditing = ref(false);
const debounceTimer = ref<any>(null);

const photoPreview = ref<string>('');
const selectedFile = ref<File | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const filters = reactive({ 
  search: '', 
  category: '' 
});

const form = reactive({
  id: null as number | null,
  name: '',
  category: '',
  description: '',
  max_quantity: 5,
  assigned_user_id: null as number | null,
  warehouse_category_filter: null as string | null, // Field filter gudang sementara di modal
  warehouse_item_id: null as number | null, // Field relasi gudang sebenarnya
  is_active: true
});

// Computed Properties
const availableCategories = computed(() => {
  const defaults = ['Amenities', 'Housekeeping', 'Laundry', 'Minibar'];
  const merged = [...new Set([...defaults, ...fetchedCategories.value])];
  return merged.sort();
});

// Computed property untuk memfilter dropdown barang gudang
const filteredWarehouseList = computed(() => {
  if (!form.warehouse_category_filter) {
    return warehouseList.value; // Tampilkan semua jika tidak ada filter
  }
  return warehouseList.value.filter(item => item.category === form.warehouse_category_filter);
});

// Menampilkan logic halaman agar tidak terlalu panjang (1 2 ... 8 9 10)
const visiblePages = computed(() => {
  const current = pagination.value.current_page;
  const last = pagination.value.last_page;
  if (last <= 7) {
    return Array.from({ length: last }, (_, i) => i + 1);
  }
  if (current <= 3) return [1, 2, 3, 4, '...', last];
  if (current >= last - 2) return [1, '...', last - 3, last - 2, last - 1, last];
  return [1, '...', current - 1, current, current + 1, '...', last];
});

// Logic Menambahkan Kategori
const addNewCategory = async () => {
  const { value: newCat } = await Swal.fire({
    title: 'Tambah Kategori Baru',
    input: 'text',
    inputPlaceholder: 'Masukkan nama kategori (Cth: Makanan Ringan)',
    showCancelButton: true,
    confirmButtonText: 'Tambahkan',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#ff6b00',
    inputValidator: (value) => {
      if (!value) return 'Nama kategori tidak boleh kosong!';
    }
  });

  if (newCat) {
    const formattedCat = newCat.trim().replace(/\b\w/g, (c: string) => c.toUpperCase());
    fetchedCategories.value.push(formattedCat);
    form.category = formattedCat;
    
    Swal.fire({
      icon: 'success', title: 'Berhasil', text: `Kategori "${formattedCat}" tersedia.`, timer: 1500, showConfirmButton: false
    });
  }
};

// Logic Fetch API dengan Pagination
const fetchItems = async (page: number = 1) => {
  if (page < 1 || page > pagination.value.last_page && pagination.value.last_page !== 1) return;
  
  isLoading.value = true;
  try {
    const { data } = await axios.get('/admin/service-items', { 
      params: { 
        ...filters, 
        page: page 
      } 
    });
    
    items.value = data.data; 
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      total: data.total,
      per_page: data.per_page
    };
  } catch (error) {
    console.error("Gagal mengambil data barang:", error);
  } finally {
    isLoading.value = false;
  }
};

// Ambil Master Data saat komponen di load
const fetchMasterData = async () => {
  try {
    const [catRes, staffRes, warehouseRes, wCatRes] = await Promise.all([
      axios.get('/admin/service-items-categories'),
      axios.get('/admin/service-items-staff'),
      axios.get('/warehouse/items?status=active&per_page=100'), // List barang gudang
      axios.get('/warehouse/items/categories') // Kategori gudang untuk dropdown filter
    ]);
    
    fetchedCategories.value = catRes.data;
    staffList.value = staffRes.data;
    warehouseList.value = warehouseRes.data.data;
    warehouseCategories.value = wCatRes.data.data; // Simpan list kategori gudang
  } catch (error) {
    console.error("Gagal mengambil master data:", error);
  }
};

const handleSearch = () => {
  clearTimeout(debounceTimer.value);
  // Reset ke page 1 saat melakukan pencarian
  debounceTimer.value = setTimeout(() => fetchItems(1), 500);
};

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    selectedFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
  } else {
    selectedFile.value = null;
    photoPreview.value = '';
  }
};

// Modal Actions
const openModal = (item: any = null) => {
  if (item) {
    isEditing.value = true;
    form.id = item.id;
    form.name = item.name;
    form.category = item.category;
    form.description = item.description || '';
    form.max_quantity = item.max_quantity;
    form.assigned_user_id = item.assigned_user_id;
    form.warehouse_item_id = item.warehouse_item_id;
    form.is_active = !!item.is_active;
    photoPreview.value = item.photo_url || '';

    // Cari kategori gudang dari item yang sudah tersambung agar filter dropdown terisi
    if (item.warehouse_item_id) {
      const wItem = warehouseList.value.find(w => w.id === item.warehouse_item_id);
      if (wItem) {
        form.warehouse_category_filter = wItem.category;
      } else {
        form.warehouse_category_filter = null;
      }
    } else {
      form.warehouse_category_filter = null;
    }

  } else {
    isEditing.value = false;
    resetForm();
  }
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  resetForm();
};

const resetForm = () => {
  form.id = null; 
  form.name = ''; 
  form.category = ''; 
  form.description = ''; 
  form.max_quantity = 5; 
  form.assigned_user_id = null; 
  form.warehouse_category_filter = null;
  form.warehouse_item_id = null;
  form.is_active = true;
  
  selectedFile.value = null;
  if (photoPreview.value && photoPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(photoPreview.value);
  }
  photoPreview.value = '';
  if (fileInput.value) fileInput.value.value = ''; 
};

// CRUD Actions
const saveItem = async () => {
  isSaving.value = true;
  try {
    const formData = new FormData();
    formData.append('name', form.name);
    formData.append('category', form.category);
    formData.append('description', form.description || '');
    formData.append('max_quantity', form.max_quantity.toString());
    formData.append('is_active', form.is_active ? '1' : '0');

    if (form.assigned_user_id) formData.append('assigned_user_id', form.assigned_user_id.toString());
    if (form.warehouse_item_id) formData.append('warehouse_item_id', form.warehouse_item_id.toString());
    
    if (selectedFile.value) {
      formData.append('photo', selectedFile.value);
    }

    const config = { headers: { 'Content-Type': 'multipart/form-data' } };

    if (isEditing.value) {
      formData.append('_method', 'PUT');
      await axios.post(`/admin/service-items/${form.id}`, formData, config);
      Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Barang berhasil diupdate', timer: 1500, showConfirmButton: false });
      fetchItems(pagination.value.current_page); // Refresh halaman saat ini
    } else {
      await axios.post('/admin/service-items', formData, config);
      Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Barang baru ditambahkan', timer: 1500, showConfirmButton: false });
      fetchItems(1); // Balik ke halaman 1 agar barang baru terlihat
    }
    
    closeModal();
    fetchMasterData(); // Refresh list agar stok gudang selalu up to date
  } catch (error: any) {
    Swal.fire('Gagal', error.response?.data?.message || 'Terjadi kesalahan sistem', 'error');
  } finally {
    isSaving.value = false;
  }
};

const deleteItem = async (id: number) => {
  Swal.fire({
    title: 'Hapus Barang?',
    text: "Data yang dihapus tidak dapat dikembalikan!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#aaaaaa',
    confirmButtonText: 'Ya, Hapus!',
    cancelButtonText: 'Batal',
    showLoaderOnConfirm: true,
    preConfirm: async () => {
      deletingItemId.value = id;
      try {
        await axios.delete(`/admin/service-items/${id}`);
        return true;
      } catch (error: any) {
        Swal.showValidationMessage(error.response?.data?.message || 'Gagal menghapus barang.');
        return false;
      } finally {
        deletingItemId.value = null;
      }
    },
    allowOutsideClick: () => !Swal.isLoading()
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire('Terhapus!', 'Barang berhasil dihapus.', 'success');
      
      // Jika hapus item di halaman selain page 1, dan item itu satu-satunya di halaman tersebut, mundur 1 halaman.
      const isLastItemOnPage = items.value.length === 1;
      const targetPage = isLastItemOnPage && pagination.value.current_page > 1 
                         ? pagination.value.current_page - 1 
                         : pagination.value.current_page;
      
      fetchItems(targetPage);
    }
  });
};

onMounted(() => {
  fetchItems(1);
  fetchMasterData();
});
</script>

<style scoped>
/* Gradient Header */
.bg-header-gradient { background: linear-gradient(135deg, #ff6b00 0%, #ff8534 100%); }
.text-orange { color: #ff6b00 !important; }
.btn-orange { background-color: #ff6b00; color: #ffffff; border-color: #ff6b00; transition: all 0.2s; }
.btn-orange:hover { background-color: #e65c00 !important; color: #ffffff; transform: translateY(-1px); }
.btn-light-orange { background-color: #fff5f0; color: #ff6b00; border: none; }
.btn-light-orange:hover { background-color: #ff6b00; color: #fff; }

/* Elevate Animation for Cards */
.hover-elevate-up { 
  transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease; 
}
.hover-elevate-up:hover { 
  transform: translateY(-8px); 
  box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1) !important;
}

/* Deskripsi Trucate agar tinggi card seragam */
.item-desc {
  display: -webkit-box;
  -webkit-line-clamp: 2; /* Maksimal 2 baris */
  line-clamp: 2; /* Standard property for compatibility */
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  min-height: 40px; /* Jaga agar konsisten meskipun kosong */
}

/* Custom Pagination styling overrides to match theme */
.pagination-circle .page-item.active .page-link {
    background-color: #ff6b00;
    border-color: #ff6b00;
    color: white;
}

/* Animations */
.anim-page-in { animation: fadeIn 0.8s ease-out forwards; }
.anim-slide-down { animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(-20px); }
.anim-slide-up { animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(20px); }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideDown { to { opacity: 1; transform: translateY(0); } }
@keyframes slideUp { to { opacity: 1; transform: translateY(0); } }

/* Element Plus Premium Select Override */
:deep(.premium-filter-select .el-input__wrapper) {
  background-color: var(--bs-secondary-bg);
  border-radius: 50px;
  box-shadow: none !important;
  border: 1px solid transparent;
  transition: all 0.3s ease;
  padding-left: 0.5rem;
}
:deep(.premium-filter-select .el-input__wrapper:hover) {
  border-color: #ff6b00;
}
:deep(.premium-filter-select .el-input__wrapper.is-focus) {
  border-color: #ff6b00;
  box-shadow: 0 0 0 2px rgba(255, 107, 0, 0.1) !important;
}

/* Element Plus Modern Form Select Override */
:deep(.modern-form-select .el-input__wrapper) {
  background-color: var(--bs-secondary-bg);
  border-radius: 0.475rem; 
  box-shadow: none !important;
  border: 1px solid transparent;
  transition: all 0.3s ease;
  padding-left: 0.75rem;
  height: 40px; 
}
:deep(.modern-form-select .el-input__wrapper:hover) {
  border-color: #ff6b00;
}
:deep(.modern-form-select .el-input__wrapper.is-focus) {
  border-color: #ff6b00;
  box-shadow: 0 0 0 2px rgba(255, 107, 0, 0.1) !important;
}
</style>