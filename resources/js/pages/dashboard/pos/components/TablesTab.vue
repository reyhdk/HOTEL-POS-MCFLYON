<template>
  <div class="d-flex flex-column h-100">
    <div class="d-flex flex-column gap-3 mb-4">
        
        <!-- Tambahkan align-items-md-center disini -->
        <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 w-100">
            <div class="position-relative flex-grow-1">
                <i class="bi bi-search position-absolute top-50 ms-4 translate-middle-y text-gray-400 fs-6"></i>
                <input type="text" v-model="searchQuery" class="form-control form-control-solid ps-12 rounded-pill border-0 h-45px bg-light" placeholder="Cari nama meja..." />
            </div>

            <div class="w-100 w-md-200px">
                <el-select v-model="typeFilter" placeholder="Semua Tipe" class="w-100 custom-el-select h-45px" clearable>
                    <template v-slot:prefix><i class="bi bi-tags text-orange fs-5"></i></template>
                    <el-option v-for="type in tableTypes" :key="type.id" :label="type.name" :value="type.id" />
                </el-select>
            </div>

            <div class="w-100 w-md-200px">
                <el-select v-model="statusFilter" placeholder="Status Meja" class="w-100 custom-el-select h-45px" clearable>
                    <template v-slot:prefix><i class="bi bi-filter text-orange fs-5"></i></template>
                    <el-option label="Tersedia" value="available" />
                    <el-option label="Terisi" value="occupied" />
                    <el-option label="Maintenance" value="maintenance" />
                </el-select>
            </div>
        </div>

        <div class="d-flex gap-3">
             <button @click="openModalCreate" class="btn btn-orange rounded-pill px-5 shadow-sm hover-elevate-up fw-bold d-flex align-items-center justify-content-center gap-2 h-45px flex-grow-1 flex-md-grow-0">
                <i class="bi bi-plus-lg fs-4"></i> Tambah Meja
            </button>
            <button @click="openTypeModal" class="btn btn-light-primary rounded-pill px-5 shadow-sm hover-elevate-up fw-bold d-flex align-items-center justify-content-center gap-2 h-45px flex-grow-1 flex-md-grow-0">
                <i class="bi bi-gear-fill fs-4"></i> Kelola Tipe
            </button>
            <button @click="refreshTables" class="btn btn-light-primary btn-icon w-45px h-45px rounded-3 shadow-sm hover-elevate-up flex-shrink-0" title="Refresh Data" :disabled="loading">
                <i class="bi bi-arrow-clockwise fs-2" :class="{ 'spin': loading }"></i>
            </button>
        </div>
    </div>

     <div class="flex-grow-1 custom-scroll overflow-auto">
         <div v-if="loading" class="py-20 text-center anim-fade-in">
             <div class="spinner-border text-orange mb-3" role="status"></div>
             <p class="text-gray-500 fw-semibold">Memuat data meja...</p>
         </div>

         <div v-else-if="paginatedTables.length === 0" class="d-flex flex-column align-items-center justify-content-center py-20 text-center anim-fade-in">
            <i class="bi bi-layout-three-columns fs-3x text-gray-300 mb-3"></i>
            <p class="text-gray-500 fw-bold">Tidak ada meja ditemukan sesuai filter.</p>
         </div>

         <div v-else class="row g-4 pb-5 px-1 anim-fade-in">
            <div class="col-6 col-md-4 col-lg-3 col-xxl-3" v-for="table in paginatedTables" :key="table.id">
                <div 
                    class="card h-100 border border-gray-200 rounded-4 shadow-sm cursor-pointer position-relative overflow-hidden table-card-hover"
                    :class="getTableCardClass(table.status)"
                    @click="openDetailModal(table)"
                >
                    <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                        <div class="d-flex justify-content-between w-100 mb-2">
                             <span class="badge badge-light fw-bolder fs-9 border border-gray-300 text-gray-600">{{ table.type_name || 'Standard' }}</span>
                             <span class="badge fw-bold px-2 py-1 rounded-pill fs-9" :class="getTableBadgeClass(table.status)">
                                {{ table.status_label }}
                            </span>
                        </div>

                        <div class="position-relative mb-3 mt-1">
                            <div class="symbol symbol-60px symbol-circle bg-light-secondary p-3">
                                <i class="bi bi-ticket-fill fs-2x" :class="getTableIconClass(table.status)"></i>
                            </div>
                            <span class="position-absolute top-0 start-100 translate-middle badge badge-circle badge-dark fs-9 border border-2 border-white shadow-sm" title="Kapasitas">
                                {{ table.capacity }}
                            </span>
                        </div>
                        
                        <h5 class="fw-bolder text-gray-900 mb-1 text-truncate w-100 fs-6">{{ table.name }}</h5>
                        
                        <div class="d-flex gap-2 justify-content-center mt-3 w-100 opacity-75">
                             <div class="d-flex align-items-center gap-1 bg-white rounded px-2 py-1 border border-gray-200" title="Unit Meja">
                                 <i class="bi bi-square fs-9 text-gray-500"></i>
                                 <span class="fs-9 fw-bold text-gray-600">1</span>
                             </div>
                             <div class="d-flex align-items-center gap-1 bg-white rounded px-2 py-1 border border-gray-200" title="Unit Kursi">
                                 <i class="bi bi-people fs-9 text-gray-500"></i>
                                 <span class="fs-9 fw-bold text-gray-600">{{ table.capacity }}</span>
                             </div>
                        </div>
                    </div>

                    <div class="hover-actions position-absolute bottom-0 start-0 w-100 p-2 bg-white bg-opacity-95 border-top d-flex gap-2 backdrop-blur z-index-2">
                        <div v-if="table.status === 'maintenance'" class="w-100 d-flex gap-1">
                            <button @click.stop="repairItem(table, 'chair')" class="btn btn-xs btn-light-danger fw-bold flex-grow-1">Fix Kursi</button>
                            <button @click.stop="repairItem(table, 'table')" class="btn btn-xs btn-light-warning fw-bold flex-grow-1">Fix Meja</button>
                        </div>
                        <div v-else class="w-100 d-flex gap-2">
                             <button @click.stop="openModalEdit(table)" class="btn btn-sm btn-light-primary flex-grow-1" title="Edit">
                                <i class="bi bi-pencil-fill"></i> Edit
                             </button>
                             <button @click.stop="deleteTable(table)" class="btn btn-sm btn-light-danger flex-grow-1" title="Hapus">
                                <i class="bi bi-trash-fill"></i>
                             </button>
                        </div>
                    </div>
                </div>
            </div>
         </div>
     </div>

     <div class="d-flex justify-content-between align-items-center mt-auto pt-4 border-top">
        <span class="text-gray-600 fs-7">
            Halaman {{ page }} dari {{ totalPages }} (Total {{ filteredTables.length }} Unit)
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

    <Teleport to="body">
        <div v-if="detailModal.show" class="modal-overlay anim-fade-in d-flex justify-content-center align-items-center" @click.self="closeDetailModal">
            <div class="modal-card bg-white rounded-4 shadow-lg overflow-hidden w-md-500px">
                <div class="bg-orange px-6 py-4 border-bottom d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="symbol symbol-40px bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center text-white">
                            <i class="bi bi-layout-three-columns fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold m-0 text-white">{{ detailModal.table?.name }}</h5>
                            <span class="badge bg-white bg-opacity-25 text-white fs-9 mt-1">{{ detailModal.table?.type_name }}</span>
                        </div>
                    </div>
                    <button @click="closeDetailModal" class="btn btn-icon btn-sm btn-white rounded-circle bg-opacity-25 hover-bg-opacity-50"><i class="bi bi-x-lg text-white"></i></button>
                </div>
                
                <div class="p-6 bg-light">
                    <div v-if="detailModal.loading" class="text-center py-5">
                        <div class="spinner-border text-orange mb-3" role="status"></div>
                        <p class="text-gray-500 fw-bold">Memeriksa pesanan...</p>
                    </div>

                    <div v-else-if="detailModal.orders.length > 0">
                        <h6 class="fw-bold text-gray-800 mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-receipt"></i> Pesanan Aktif
                        </h6>
                        <div class="custom-scroll overflow-auto bg-white rounded-3 shadow-sm border p-3 mb-4" style="max-height: 250px;">
                            <div v-for="order in detailModal.orders" :key="order.id" class="mb-3 border-bottom pb-3 last-border-0">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="badge badge-light-primary fw-bold">Order #{{ order.id }}</span>
                                    <span class="fw-bolder text-gray-800">{{ formatCurrency(order.total_price) }}</span>
                                </div>
                                <div v-for="item in order.items" :key="item.id" class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="text-gray-600 fs-7">
                                        <span class="fw-bold me-2">{{ item.quantity }}x</span> {{ item.menu?.name || 'Menu' }}
                                    </div>
                                    <span class="text-muted fs-8">{{ formatCurrency(item.price * item.quantity) }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button @click="emitSelectTable" class="btn btn-light-orange fw-bold py-3 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-plus-circle"></i> Tambah Pesanan Lagi
                            </button>
                        </div>
                    </div>

                    <div v-else class="text-center py-5 bg-white rounded-3 shadow-sm border mb-4">
                        <i class="bi bi-inboxes fs-1 text-gray-300 mb-3 d-block"></i>
                        <h6 class="fw-bold text-gray-600">Meja ini masih kosong</h6>
                        <p class="text-gray-400 fs-7 mb-0">Belum ada pesanan aktif di meja ini.</p>
                    </div>

                    <div v-if="detailModal.orders.length === 0" class="d-grid">
                        <button @click="emitSelectTable" class="btn btn-orange fw-bold py-3 shadow-sm hover-scale d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-cart-plus fs-5"></i> Buat Pesanan Baru di Meja Ini
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <Teleport to="body">
        <div v-if="modal.show" class="modal-overlay anim-fade-in d-flex justify-content-center align-items-center" @click.self="closeModal">
            <div class="modal-card bg-white rounded-4 shadow-lg overflow-hidden position-relative">
                <div class="bg-light px-6 py-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold m-0 text-gray-800">{{ modal.mode === 'create' ? 'Tambah Unit Meja' : 'Edit Meja' }}</h5>
                    <button @click="closeModal" class="btn btn-icon btn-sm btn-light-danger rounded-circle"><i class="bi bi-x-lg"></i></button>
                </div>
                
                <div class="p-6 custom-scroll overflow-auto" style="max-height: 70vh;">
                    
                    <div class="row g-4 mb-5">
                        <div class="col-12">
                            <label class="form-label fs-7 fw-bold text-dark required">Tipe Meja</label>
                            <el-select v-model="modal.form.type_id" placeholder="Pilih Tipe (Wajib)" class="w-100" @change="onTypeChange">
                                <el-option v-for="type in tableTypes" :key="type.id" :label="type.name" :value="type.id" />
                            </el-select>
                            <div class="form-text fs-9">Memilih tipe akan mengisi nama meja secara otomatis.</div>
                        </div>

                        <div class="col-8">
                            <label class="form-label fs-7 fw-bold text-dark required">Nama / Label Meja</label>
                            <input v-model="modal.form.name" class="form-control form-control-solid" placeholder="Contoh: Meja-VIP-1">
                        </div>
                        <div class="col-4" v-if="modal.mode === 'create'">
                            <label class="form-label fs-7 fw-bold text-dark">Jml. Unit</label>
                            <input v-model.number="modal.form.qty" type="number" class="form-control form-control-solid text-center fw-bold" min="1">
                        </div>
                    </div>

                    <div v-if="modal.mode === 'create'" class="rounded-4 p-5 mb-5 border border-dashed border-gray-300 bg-light-primary">
                        <div class="d-flex align-items-center mb-4">
                            <div class="symbol symbol-35px symbol-circle bg-white shadow-sm me-3 d-flex align-items-center justify-content-center">
                                <i class="bi bi-box-seam text-primary fs-5"></i>
                            </div>
                            <h6 class="fw-bolder text-gray-800 m-0">Ambil Stok Gudang</h6>
                        </div>

                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label fs-8 fw-bold text-gray-600 mb-1">Filter Aset Gudang</label>
                                <el-select v-model="modal.form.category" @change="saveCategoryPreference" class="w-100" placeholder="Pilih Kategori">
                                    <el-option v-for="cat in warehouseCategories" :key="cat" :label="cat" :value="cat" />
                                </el-select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fs-8 fw-bold text-gray-600 mb-1 required">Fisik Meja</label>
                                <select v-model="modal.form.warehouse_table_id" class="form-select form-select-solid fs-7">
                                    <option value="" disabled>Pilih Stok...</option>
                                    <option v-for="item in availableTableItems" :key="item.id" :value="item.id">
                                        {{ item.name }} (Sisa: {{ item.current_stock }})
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fs-8 fw-bold text-gray-600 mb-1 required">Fisik Kursi</label>
                                <select v-model="modal.form.warehouse_chair_id" class="form-select form-select-solid fs-7">
                                    <option value="" disabled>Pilih Stok...</option>
                                    <option v-for="item in availableChairItems" :key="item.id" :value="item.id">
                                        {{ item.name }} (Sisa: {{ item.current_stock }})
                                    </option>
                                </select>
                            </div>
                        </div>

                         <div class="mt-4 pt-3 border-top border-primary border-dashed border-opacity-25">
                             <div class="d-flex justify-content-between align-items-center mb-2">
                                 <label class="fs-8 fw-bold text-gray-600 m-0">Item Tambahan (Opsional)</label>
                                 <button @click="addOtherItem" class="btn btn-xs btn-light-primary fw-bold"><i class="bi bi-plus"></i></button>
                             </div>
                             <div class="d-flex flex-column gap-3">
                                 <div v-for="(addItem, idx) in modal.form.additional_items" :key="idx" class="bg-white p-2 rounded-3 border shadow-sm d-flex gap-2 align-items-center">
                                    <select v-model="addItem.id" class="form-select form-select-xs form-select-solid flex-grow-1">
                                        <option disabled value="">Pilih Item...</option>
                                        <option v-for="item in filteredWarehouseItems" :key="item.id" :value="item.id">
                                            {{ item.name }}
                                        </option>
                                    </select>
                                    <input v-model.number="addItem.qty" type="number" class="form-control form-control-xs w-50px text-center" min="1">
                                    <button @click="removeOtherItem(idx)" class="btn btn-icon btn-xs btn-light-danger"><i class="bi bi-trash"></i></button>
                                 </div>
                             </div>
                        </div>
                    </div>

                    <div v-if="modal.mode === 'edit'" class="mb-5 bg-light rounded-3 p-4">
                        <label class="form-label fs-7 fw-bold text-gray-700">Status Operasional</label>
                        <select v-model="modal.form.status" class="form-select form-select-solid">
                            <option value="available">Available (Kosong)</option>
                            <option value="occupied">Occupied (Terisi)</option>
                            <option value="maintenance">Maintenance (Perbaikan)</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fs-7 fw-bold text-dark m-0">Kapasitas Tamu (Kursi)</label>
                        <input v-model.number="modal.form.capacity" type="number" class="form-control form-control-solid ps-2" min="1">
                        <div class="form-text fs-9">Jumlah ini akan mengurangi stok kursi di gudang.</div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-light border-top text-end">
                    <button @click="closeModal" class="btn btn-light me-2 fw-bold">Batal</button>
                    <button @click="submitModal" class="btn btn-orange fw-bold" :disabled="modal.submitting">
                        <span v-if="modal.submitting" class="spinner-border spinner-border-sm me-2"></span>
                        {{ modal.mode === 'create' ? 'Proses & Potong Stok' : 'Simpan Perubahan' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <Teleport to="body">
        <div v-if="typeModal.show" class="modal-overlay anim-fade-in d-flex justify-content-center align-items-center" @click.self="closeTypeModal">
            <div class="modal-card bg-white rounded-4 shadow-lg overflow-hidden w-md-500px">
                <div class="bg-orange px-6 py-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold m-0 text-white">Kelola Tipe Meja</h5>
                    <button @click="closeTypeModal" class="btn btn-icon btn-sm btn-white rounded-circle bg-opacity-25 hover-bg-opacity-50"><i class="bi bi-x-lg text-white"></i></button>
                </div>
                
                <div class="p-6">
                    <div class="d-flex gap-2 mb-4">
                        <input v-model="typeModal.form.name" class="form-control" placeholder="Nama Tipe (Misal: VIP, Outdoor)">
                         <button @click="submitType" class="btn btn-light-success fw-bold flex-shrink-0" :disabled="!typeModal.form.name">
                            <i class="bi bi-plus-lg"></i> Tambah
                         </button>
                    </div>
                    <div class="mb-4">
                        <textarea v-model="typeModal.form.description" class="form-control form-control-sm" placeholder="Deskripsi (Opsional)"></textarea>
                    </div>

                    <div class="separator separator-dashed mb-4"></div>

                    <div class="custom-scroll overflow-auto" style="max-height: 300px;">
                        <div v-if="tableTypes.length === 0" class="text-center text-gray-400 py-5">Belum ada tipe meja.</div>
                        <div v-for="type in tableTypes" :key="type.id" class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-2 border border-dashed hover-bg-light-primary transition-all">
                            <div>
                                <div class="fw-bold text-gray-800">{{ type.name }}</div>
                                <div class="text-gray-500 fs-9">{{ type.description || 'Tidak ada deskripsi' }}</div>
                            </div>
                            <button @click="deleteType(type)" class="btn btn-icon btn-sm btn-light-danger bg-white shadow-sm">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";

const emit = defineEmits(['select-table']);

// Data State
const tables = ref<any[]>([]);
const tableTypes = ref<any[]>([]); 
const warehouseItems = ref<any[]>([]);
const warehouseCategories = ref<string[]>([]);
const loading = ref(false); 

// Filter State
const searchQuery = ref("");
const statusFilter = ref("");
const typeFilter = ref<number | null>(null); 
const page = ref(1);
const perPage = 12;

// --- STATE MODAL DETAIL ---
const detailModal = ref({
    show: false,
    table: null as any,
    loading: false,
    orders: [] as any[]
});

// Modal Table State
const modal = ref({
    show: false,
    mode: 'create' as 'create' | 'edit',
    submitting: false,
    data: null as any,
    form: {
        name: '', qty: 1, category: '',
        type_id: null as number | null, 
        warehouse_table_id: '', warehouse_chair_id: '',
        capacity: 4, status: 'available', additional_items: [] as any[]
    }
});

// Modal Type State
const typeModal = ref({
    show: false,
    form: { name: '', description: '' }
});

// --- COMPUTED PROPERTIES ---

const filteredTables = computed(() => {
    let result = tables.value.filter(t => {
        const matchesSearch = t.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesStatus = statusFilter.value ? t.status === statusFilter.value : true;
        const matchesType = typeFilter.value ? t.type_id === typeFilter.value : true;
        return matchesSearch && matchesStatus && matchesType;
    });

    // Natural Sort
    return result.sort((a, b) => {
        return a.name.localeCompare(b.name, undefined, { numeric: true, sensitivity: 'base' });
    });
});

const totalPages = computed(() => Math.ceil(filteredTables.value.length / perPage));
const paginatedTables = computed(() => {
    const start = (page.value - 1) * perPage;
    return filteredTables.value.slice(start, start + perPage);
});

// Watcher untuk reset halaman
watch([searchQuery, statusFilter, typeFilter], () => { page.value = 1 });

// Warehouse Helper Computeds
const filteredWarehouseItems = computed(() => {
    if (!modal.value.form.category) return warehouseItems.value;
    return warehouseItems.value.filter(i => i.category === modal.value.form.category);
});

const availableTableItems = computed(() => filteredWarehouseItems.value.filter(item => {
    const name = item.name.toLowerCase();
    return name.includes('meja') || name.includes('table') || name.includes('desk');
}));

const availableChairItems = computed(() => filteredWarehouseItems.value.filter(item => {
    const name = item.name.toLowerCase();
    return name.includes('kursi') || name.includes('chair') || name.includes('stool') || name.includes('sofa');
}));

// --- FITUR BARU: MODAL DETAIL MEJA ---
const openDetailModal = async (table: any) => {
    if (table.status === 'maintenance') {
        Swal.fire('Perbaikan', 'Meja sedang dalam perbaikan', 'warning');
        return;
    }

    detailModal.value.table = table;
    detailModal.value.show = true;
    detailModal.value.loading = true;
    detailModal.value.orders = [];

    try {
        const { data } = await ApiService.get("/online-orders"); 
        
        if(data && Array.isArray(data)) {
            // Tampilkan semua pesanan meja ini kecuali yang statusnya completed/cancelled
            detailModal.value.orders = data.filter((o: any) => 
                o.table_id === table.id && 
                o.status !== 'completed' && 
                o.status !== 'cancelled'
            );
        }
    } catch (e) {
        console.error("Gagal load pesanan meja", e);
    } finally {
        detailModal.value.loading = false;
    }
};

const closeDetailModal = () => { detailModal.value.show = false; };

const emitSelectTable = () => {
    emit('select-table', detailModal.value.table);
    closeDetailModal();
};

const formatCurrency = (v: number) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(v);

// --- ACTIONS & API ---

const fetchTables = async () => {
    loading.value = true; 
    try { 
        const { data } = await ApiService.get("/pos/tables"); 
        tables.value = data || []; 
    } 
    catch (e) { console.error(e); }
    finally { loading.value = false; } 
};

const refreshTables = () => {
    fetchTables();
};

const fetchTableTypes = async () => {
    try { const { data } = await ApiService.get("/pos/table-types"); tableTypes.value = data || []; }
    catch (e) { console.error(e); }
}

const fetchWarehouseItems = async () => {
    try {
        const { data } = await ApiService.get("/warehouse/items?per_page=100&status=active");
        warehouseItems.value = data.data || []; 
        warehouseCategories.value = [...new Set(warehouseItems.value.map((i: any) => i.category))];
    } catch (e) { console.error("Gagal load gudang", e); }
};

// --- LOGIC MODAL TYPE MANAGEMENT ---

const openTypeModal = () => {
    typeModal.value.show = true;
    typeModal.value.form = { name: '', description: '' };
};
const closeTypeModal = () => typeModal.value.show = false;

const submitType = async () => {
    try {
        await ApiService.post('/pos/table-types', typeModal.value.form);
        fetchTableTypes();
        typeModal.value.form = { name: '', description: '' }; 
        Swal.fire({ icon: 'success', title: 'Tipe Ditambahkan', showConfirmButton: false, timer: 1000 });
    } catch(e: any) {
        Swal.fire('Error', e.response?.data?.message || 'Gagal tambah tipe', 'error');
    }
};

const deleteType = async (type: any) => {
    const result = await Swal.fire({
        title: 'Hapus Tipe?',
        text: `Tipe "${type.name}" akan dihapus.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus'
    });

    if (result.isConfirmed) {
        try {
            await ApiService.delete(`/pos/table-types/${type.id}`);
            fetchTableTypes();
            Swal.fire({ icon: 'success', title: 'Terhapus', showConfirmButton: false, timer: 1000 });
        } catch(e: any) {
            Swal.fire('Gagal', e.response?.data?.message || 'Tidak bisa menghapus', 'error');
        }
    }
};

// --- LOGIC MODAL TABLE CREATE/EDIT ---

const openModalCreate = () => {
    const storedCategory = localStorage.getItem('pos_default_asset_category');
    const defaultCat = storedCategory && warehouseCategories.value.includes(storedCategory) ? storedCategory : (warehouseCategories.value[0] || '');
    
    modal.value = {
        show: true, mode: 'create', submitting: false, data: null,
        form: {
            name: '', qty: 1, category: defaultCat,
            type_id: null, 
            warehouse_table_id: '', warehouse_chair_id: '',
            capacity: 4, status: 'available', additional_items: []
        }
    };
};

const openModalEdit = (table: any) => {
    modal.value = {
        show: true, mode: 'edit', submitting: false, data: table,
        form: {
            ...table, qty: 1, category: '',
            type_id: table.type_id,
            warehouse_table_id: table.inventory?.table_id || '',
            warehouse_chair_id: table.inventory?.chair_id || '',
            additional_items: []
        }
    };
};

const closeModal = () => modal.value.show = false;

// AUTO NAMING LOGIC
const onTypeChange = () => {
    if (modal.value.mode === 'create' && modal.value.form.type_id) {
        const type = tableTypes.value.find(t => t.id === modal.value.form.type_id);
        if (type) {
            modal.value.form.name = `Meja-${type.name}`;
        }
    }
};

// ADDITIONAL ITEMS LOGIC
const saveCategoryPreference = () => { if (modal.value.form.category) localStorage.setItem('pos_default_asset_category', modal.value.form.category); };
const addOtherItem = () => modal.value.form.additional_items.push({ id: '', qty: 1 });
const removeOtherItem = (index: number) => modal.value.form.additional_items.splice(index, 1);

const submitModal = async () => {
    if (!modal.value.form.name) return Swal.fire('Validasi', 'Nama meja wajib diisi', 'warning');
    if (!modal.value.form.type_id) return Swal.fire('Validasi', 'Tipe meja wajib dipilih', 'warning');

    modal.value.submitting = true;
    try {
        if (modal.value.mode === 'edit') {
            await ApiService.put(`/pos/tables/${modal.value.data.id}`, {
                name: modal.value.form.name, 
                status: modal.value.form.status,
                type_id: modal.value.form.type_id
            });
        } else {
            const quantity = modal.value.form.qty;
            const promises: any[] = [];
            const basePayload = {
                capacity: modal.value.form.capacity, status: 'available',
                type_id: modal.value.form.type_id,
                warehouse_table_id: modal.value.form.warehouse_table_id,
                warehouse_chair_id: modal.value.form.warehouse_chair_id,
                additional_items: modal.value.form.additional_items.filter((i: any) => i.id && i.qty > 0)
            };

            if (quantity > 1) {
                // Natural increment untuk bulk create
                const baseName = modal.value.form.name;
                for (let i = 1; i <= quantity; i++) {
                    const uniqueName = `${baseName}-${i}`;
                    promises.push(ApiService.post(`/pos/tables`, { ...basePayload, name: uniqueName }));
                }
                await Promise.all(promises);
            } else {
                await ApiService.post(`/pos/tables`, { ...basePayload, name: modal.value.form.name });
            }
        }
        Swal.fire({ icon: 'success', title: 'Berhasil', showConfirmButton: false, timer: 1000 });
        closeModal(); fetchTables(); fetchWarehouseItems();
    } catch (e: any) {
        Swal.fire('Gagal', e.response?.data?.message || 'Error', 'error');
    } finally { modal.value.submitting = false; }
};

const deleteTable = (table: any) => {
    Swal.fire({ title: 'Hapus Meja?', text: "Data meja akan dihapus permanen.", icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus' })
    .then(async (r) => { if(r.isConfirmed) { await ApiService.delete(`/pos/tables/${table.id}`); fetchTables(); } });
};

const repairItem = (table: any, type: string) => {
    Swal.fire('Fitur Repair', `Ganti ${type} untuk ${table.name}`, 'info');
};

const getTableCardClass = (s: string) => {
    if (s === 'occupied') return 'bg-light-orange border-orange';
    if (s === 'maintenance') return 'bg-light-danger border-danger';
    return 'bg-white hover-shadow';
};

const getTableIconClass = (s: string) => s === 'occupied' ? 'text-orange' : (s === 'maintenance' ? 'text-danger' : 'text-gray-400');
const getTableBadgeClass = (s: string) => s === 'occupied' ? 'badge-light-orange text-orange' : (s === 'maintenance' ? 'badge-light-danger text-danger' : 'badge-light-success text-success');

onMounted(() => { 
    fetchTables(); 
    fetchTableTypes(); 
    fetchWarehouseItems(); 
});
</script>

<style scoped>
.modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.5); z-index: 1050 !important; backdrop-filter: blur(2px); }
.modal-card { max-width: 650px; width: 95%; z-index: 1051 !important; animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

.text-orange { color: #ff6b00 !important; }
.bg-orange { background-color: #ff6b00 !important; }
.bg-light-orange { background-color: #fff8f5 !important; }
.border-orange { border-color: #ff6b00 !important; }
.btn-orange { background: #ff6b00; color: white; border: none; }
.btn-orange:hover { background: #e05e00; }

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

/* Dark mode tweaks for the refresh button */
[data-bs-theme="dark"] .btn-light-orange { background-color: rgba(13,110,253,0.12) !important; color: #74b6ff !important; border-color: transparent !important; }
[data-bs-theme="dark"] .btn-light-orange:hover, [data-bs-theme="dark"] .btn-light-orange:active { background-color: #0d6efd !important; color: white !important; border-color: #0d6efd !important; }

.table-card-hover { transition: all 0.3s ease; border-width: 1px; }
.table-card-hover:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.08) !important; border-color: #ff6b00 !important; }

.hover-actions { opacity: 0; transition: opacity 0.2s ease-in-out; transform: translateY(10px); }
.table-card-hover:hover .hover-actions { opacity: 1; transform: translateY(0); }
.backdrop-blur { backdrop-filter: blur(5px); }

.custom-scroll::-webkit-scrollbar { width: 6px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #e1e1e1; border-radius: 10px; }
.anim-fade-in { animation: fadeIn 0.3s ease forwards; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

.last-border-0:last-child { border-bottom: 0 !important; padding-bottom: 0 !important; margin-bottom: 0 !important; }
.hover-scale:hover { transform: scale(1.02); transition: transform 0.2s ease; }

/* Adaptive Dark Mode Support */
[data-bs-theme="dark"] .text-gray-500 { color: #cdcdde !important; }
[data-bs-theme="dark"] .text-gray-400 { color: #9a9cae !important; }
[data-bs-theme="dark"] .text-gray-600 { color: #9a9cae !important; }
[data-bs-theme="dark"] .text-gray-300 { color: #5a5c6f !important; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-dark { color: #ffffff !important; }
[data-bs-theme="dark"] .bg-light { background-color: #2b2b40 !important; }
[data-bs-theme="dark"] .bg-light-primary { background-color: rgba(0, 158, 247, 0.15) !important; }
[data-bs-theme="dark"] .bg-light-orange { background-color: rgba(255, 107, 0, 0.15) !important; }
[data-bs-theme="dark"] .bg-white { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .card { background-color: #1e1e2d !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .border-gray-300 { border-color: #323248 !important; }
[data-bs-theme="dark"] .border-dashed { border-color: #323248 !important; }
[data-bs-theme="dark"] .form-control { 
    background-color: #1b1b29 !important; 
    border-color: #323248 !important; 
    color: #ffffff;
}
[data-bs-theme="dark"] .form-select { 
    background-color: #1b1b29 !important; 
    border-color: #323248 !important; 
    color: #ffffff;
}
[data-bs-theme="dark"] .badge {
    background-color: #2b2b40 !important;
    color: #cdcdde !important;
}
[data-bs-theme="dark"] :deep(.el-input__wrapper) {
    background-color: #1b1b29 !important;
    border-color: #323248 !important;
}
[data-bs-theme="dark"] :deep(.el-input__wrapper.is-focus) {
    border-color: #ff6b00 !important;
    background-color: #1b1b29 !important;
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