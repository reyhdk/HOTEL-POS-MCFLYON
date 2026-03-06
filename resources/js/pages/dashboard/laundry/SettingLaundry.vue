<template>
  <div class="d-flex flex-column gap-5 laundry-setting-wrapper">
    
    <!-- STATISTIK HEADER -->
    <div class="row g-5 g-xl-8">
      <div class="col-xl-4 col-md-6 animate-item" style="--delay: 0s">
        <div class="card bg-body hover-elevate-up border-0 h-100 card-stat-primary theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-primary text-primary rounded-4">
                <span class="svg-icon svg-icon-2x svg-icon-primary">
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
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ services.length }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Total Layanan</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-4 col-md-6 animate-item" style="--delay: 0.1s">
        <div class="card bg-body hover-elevate-up border-0 h-100 theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
             <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-success text-success rounded-4">
                <span class="svg-icon svg-icon-2x svg-icon-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10" fill="currentColor"/>
                        <path d="M10.4343 15.4343L9.25 14.25C8.83579 13.8358 8.16421 13.8358 7.75 14.25C7.33579 14.6642 7.33579 15.3358 7.75 15.75L10.2929 18.2929C10.6834 18.6834 11.3166 18.6834 11.7071 18.2929L17.25 12.75C17.6642 12.3358 17.6642 11.6642 17.25 11.25C16.8358 10.8358 16.1642 10.8358 15.75 11.25L10.4343 16.5657C10.1219 16.8781 9.61537 16.8781 9.30296 16.5657L10.4343 15.4343Z" fill="currentColor"/>
                    </svg>
                </span>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ activeServicesCount }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Layanan Aktif</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TOOLBAR -->
    <div class="card border-0 shadow-sm theme-card animate-item position-relative" style="--delay: 0.2s; z-index: 99;">
        <div class="card-body py-4">
            <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-4">
                
                <div class="d-flex align-items-center position-relative w-100 w-sm-300px">
                    <i class="ki-duotone ki-magnifier fs-2 position-absolute ms-4 text-gray-500"><span class="path1"></span><span class="path2"></span></i>
                    <input type="text" v-model="searchQuery" class="form-control form-control-solid ps-12 search-input" placeholder="Cari layanan laundry..." />
                </div>

                <div class="d-flex flex-wrap gap-3 align-items-center w-100 w-sm-auto justify-content-end">
                    <button class="btn btn-sm btn-primary fw-bold hover-scale box-shadow-primary" @click="openModal()">
                        <i class="ki-duotone ki-plus fs-2 text-white"></i> Tambah Layanan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- GRID CONTENT -->
    <div class="position-relative min-h-300px" style="z-index: 1;">
        
        <div v-if="loading" class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center z-index-10 bg-body bg-opacity-75 rounded-3">
             <div class="d-flex flex-column align-items-center animate-pulse">
                 <span class="spinner-border text-primary mb-3 w-40px h-40px"></span>
                 <span class="text-gray-500 fw-bold">Memuat data laundry...</span>
             </div>
        </div>

        <div v-else-if="filteredServices.length === 0" class="d-flex flex-column align-items-center justify-content-center py-15 text-muted animate-fade-in">
             <div class="symbol symbol-100px mb-5 bg-light-primary rounded-circle d-flex align-items-center justify-content-center">
                <i class="ki-duotone ki-basket fs-4x text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
             </div>
             <span class="fs-4 fw-bold text-gray-800">Tidak ada layanan ditemukan.</span>
        </div>

        <div v-else>
            <TransitionGroup name="fade-grid" tag="div" class="row g-6">
                <div class="col-md-6 col-lg-4 col-xl-3" v-for="service in filteredServices" :key="service.id">
                    
                    <div class="card h-100 border-0 shadow-sm theme-card hover-elevate-up transition-300 group-card">
                        
                        <div class="card-body p-5 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div class="symbol symbol-50px bg-light-primary rounded-3 d-flex align-items-center justify-content-center">
                                     <i class="ki-duotone ki-handcart fs-2x text-primary"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                                <span class="badge fw-bold shadow-sm" :class="service.is_active ? 'badge-light-success' : 'badge-light-danger'">
                                    {{ service.is_active ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <a href="#" class="fs-4 fw-bolder text-gray-900 text-hover-primary transition-200 mb-1 d-block" @click.prevent="openModal(service)">
                                    {{ service.name }}
                                </a>
                                <div class="d-flex align-items-center text-gray-500 fs-7 fw-semibold mt-1">
                                    <i class="ki-duotone ki-tag fs-6 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                    Per {{ service.unit === 'kg' ? 'Kilogram (Kg)' : 'Pieces (Pcs)' }}
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mt-auto pt-4 mb-4 border-top border-dashed border-gray-200">
                                <div class="d-flex flex-column">
                                    <span class="text-gray-500 fs-8 fw-bold text-uppercase">Harga</span>
                                    <span class="fw-bolder fs-3 text-gray-800">{{ formatCurrency(service.price) }}</span>
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    <span class="text-gray-500 fs-8 fw-bold text-uppercase">Bahan</span>
                                    <span class="badge badge-light-warning fw-bold fs-7">{{ countMaterials(service.estimated_materials) }} Item</span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-center gap-2 w-100">
                                <button @click="openModal(service)" class="btn btn-sm btn-light btn-active-light-primary fw-bold flex-grow-1">
                                    <i class="ki-duotone ki-pencil fs-5"><span class="path1"></span><span class="path2"></span></i> Edit
                                </button>
                                <button @click="deleteService(service.id)" class="btn btn-sm btn-light btn-active-light-danger fw-bold flex-grow-1">
                                    <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i> Hapus
                                </button>
                            </div>

                        </div>
                    </div>

                </div>
            </TransitionGroup>
        </div>
    </div>

    <!-- MODAL FORM LAUNDRY -->
    <div class="modal fade" id="kt_modal_laundry" ref="modalRef" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-1000px">
            <div class="modal-content rounded-3 shadow-lg border-0 theme-modal">
                
                <div class="modal-header border-0 pb-0 justify-content-between align-items-center py-4 px-5">
                    <h2 class="fw-bold m-0 fs-3 text-gray-900">{{ isEditMode ? 'Edit Layanan Laundry' : 'Tambah Layanan Baru' }}</h2>
                    <div class="btn btn-sm btn-icon btn-active-light-primary rounded-circle" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>

                <div class="modal-body scroll-y px-5 pb-5 pt-2">
                    <el-form :model="formData" :rules="rules" ref="formRef" label-position="top" class="compact-form">
                        
                        <div class="row g-5">
                            <!-- KOLOM KIRI: Informasi Layanan -->
                            <div class="col-lg-4 border-end border-gray-300">
                                <h4 class="fw-bold text-gray-800 mb-4">Detail Layanan</h4>

                                <el-form-item prop="name" label="Nama Layanan" class="mb-3">
                                    <el-input v-model="formData.name" placeholder="Contoh: Cuci Kering + Setrika" class="metronic-input fw-bold" />
                                </el-form-item>

                                <div class="row">
                                    <div class="col-6">
                                        <el-form-item prop="unit" label="Satuan" class="mb-3">
                                            <el-select v-model="formData.unit" placeholder="Pilih..." class="w-100 metronic-input">
                                                <el-option value="kg" label="Kilogram (Kg)" />
                                                <el-option value="pcs" label="Pieces (Pcs)" />
                                            </el-select>
                                        </el-form-item>
                                    </div>
                                    <div class="col-6">
                                        <el-form-item prop="is_active" label="Status" class="mb-3 text-center">
                                            <div class="form-check form-switch form-check-custom form-check-solid justify-content-center mt-2">
                                                <input class="form-check-input h-25px w-45px cursor-pointer" type="checkbox" v-model="formData.is_active" />
                                            </div>
                                        </el-form-item>
                                    </div>
                                </div>
                                
                                <el-form-item prop="price" label="Harga Layanan" class="mb-3">
                                    <el-input v-model="formData.price" type="number" class="metronic-input text-end fw-bold">
                                        <template #prefix>Rp</template>
                                    </el-input>
                                </el-form-item>

                                <div class="alert bg-light-info border-info border-dashed d-flex flex-column p-3 mt-5">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="ki-duotone ki-information fs-3 text-info me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                        <h6 class="fw-bold text-info m-0">Informasi HPP</h6>
                                    </div>
                                    <span class="fs-8 text-gray-700">Harga modal otomatis dihitung berdasarkan estimasi bahan. Gunakan data ini untuk memastikan profit margin sesuai.</span>
                                </div>
                            </div>

                            <!-- KOLOM KANAN: Estimasi Bahan Baku (Deterjen, Pewangi, dll) -->
                            <div class="col-lg-8 ps-lg-5">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h4 class="fw-bold text-gray-800 m-0">Estimasi Kebutuhan Bahan</h4>
                                        <span class="text-muted fs-8">Pilih kategori, lalu pilih bahan gudang.</span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-primary fw-bold" @click="addMaterialRow">
                                        <i class="ki-duotone ki-plus fs-3"></i> Tambah Bahan
                                    </button>
                                </div>

                                <div class="table-responsive mb-4 bg-light-subtle rounded border border-gray-200 p-1" style="max-height: 250px; overflow-y: auto;">
                                    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-3 m-0">
                                        <thead class="fw-bold fs-8 text-gray-500 text-uppercase bg-body">
                                            <tr>
                                                <th class="ps-3 min-w-150px">Kategori</th>
                                                <th class="min-w-150px">Item Gudang</th>
                                                <th class="min-w-80px text-center">Jumlah</th>
                                                <th class="min-w-80px">Satuan</th>
                                                <th class="min-w-100px text-end">Subtotal HPP</th>
                                                <th class="w-50px text-center">Hapus</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="formData.materials.length === 0">
                                                <td colspan="6" class="text-center py-5 text-muted fs-7">
                                                    Belum ada estimasi bahan. Klik "Tambah Bahan".
                                                </td>
                                            </tr>
                                            <tr v-for="(item, index) in formData.materials" :key="index" class="animate-fade-in">
                                                <td class="ps-2">
                                                    <el-select 
                                                        v-model="item.category_filter" 
                                                        placeholder="Pilih Kategori" 
                                                        class="w-100 metronic-input form-select-sm"
                                                        @change="() => onCategoryChange(index)">
                                                        <el-option 
                                                            v-for="cat in laundryCategories" 
                                                            :key="cat" 
                                                            :label="cat" 
                                                            :value="cat" 
                                                        />
                                                    </el-select>
                                                </td>
                                                <td>
                                                    <el-select 
                                                        v-model="item.warehouse_item_id" 
                                                        filterable 
                                                        placeholder="Pilih Bahan..." 
                                                        class="w-100 metronic-input form-select-sm"
                                                        :disabled="!item.category_filter"
                                                        @change="(val) => onMaterialSelect(val, index)">
                                                        <el-option 
                                                            v-for="wItem in getItemsByCategory(item.category_filter)" 
                                                            :key="wItem.id" 
                                                            :label="wItem.name" 
                                                            :value="wItem.id">
                                                            <span class="d-flex justify-content-between align-items-center w-100">
                                                                <span>{{ wItem.name }}</span>
                                                                <span class="badge badge-light-secondary fs-9">{{ wItem.current_stock }} {{ wItem.unit }}</span>
                                                            </span>
                                                        </el-option>
                                                    </el-select>
                                                </td>
                                                <td>
                                                    <el-input 
                                                        v-model="item.qty" 
                                                        type="number" 
                                                        step="0.01" 
                                                        min="0"
                                                        placeholder="0" 
                                                        class="metronic-input text-center h-35px" />
                                                </td>
                                                <td>
                                                    <span class="badge badge-light fw-bold fs-7 text-gray-700 w-100 h-35px d-flex align-items-center justify-content-center border border-gray-300">
                                                        {{ item.unit_label || '-' }}
                                                    </span>
                                                </td>
                                                <td class="text-end pe-3">
                                                    <span class="text-gray-800 fw-bold fs-7">
                                                        {{ formatCurrency(calculateRowHPP(item)) }}
                                                    </span>
                                                </td>
                                                <td class="text-center pe-2">
                                                    <button type="button" @click="removeMaterialRow(index)" class="btn btn-icon btn-sm btn-light-danger btn-active-danger w-30px h-30px rounded-circle" title="Hapus">
                                                        <i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- SUMMARY CARD HPP & MARGIN -->
                                <div class="card bg-light-warning border-warning border border-dashed shadow-sm">
                                    <div class="card-body p-4">
                                        <div class="row text-center">
                                            <div class="col-4 border-end border-warning border-opacity-25">
                                                <div class="fs-8 text-gray-600 fw-semibold text-uppercase">Total HPP</div>
                                                <div class="fs-4 fw-bolder text-gray-900 mt-1">{{ formatCurrency(totalHPP) }}</div>
                                            </div>
                                            <div class="col-4 border-end border-warning border-opacity-25">
                                                <div class="fs-8 text-gray-600 fw-semibold text-uppercase">Harga Jual</div>
                                                <div class="fs-4 fw-bolder text-gray-900 mt-1">{{ formatCurrency(formData.price || 0) }}</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="fs-8 text-gray-600 fw-semibold text-uppercase">Keuntungan Bersih</div>
                                                <div class="fs-4 fw-bolder mt-1" :class="profitValue > 0 ? 'text-success' : 'text-danger'">
                                                    {{ formatCurrency(profitValue) }}
                                                </div>
                                                <div class="fs-9 fw-bold" :class="profitValue > 0 ? 'text-success' : 'text-danger'">
                                                    ({{ profitMargin }}% Margin)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- END SUMMARY CARD -->

                            </div>
                        </div>

                        <div class="d-flex justify-content-end align-items-center mt-5 pt-3 border-top border-gray-200">
                            <button type="button" class="btn btn-sm btn-light me-3 fw-bold text-gray-700 px-5" data-bs-dismiss="modal">Batal</button>
                            <button :disabled="saving" class="btn btn-primary fw-bold px-6 shadow-sm hover-elevate" type="button" @click="submit">
                                <span v-if="!saving" class="d-flex align-items-center">
                                    Simpan <i class="ki-duotone ki-check-circle fs-2 ms-2 text-white"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                                <span v-if="saving" class="indicator-progress d-flex align-items-center">
                                    <span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...
                                </span>
                            </button>
                        </div>

                    </el-form>
                </div>
            </div>
        </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import axios from "@/libs/axios";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import type { FormInstance, FormRules } from 'element-plus'

// --- Interfaces ---
interface MaterialInput {
    category_filter: string; 
    warehouse_item_id: number | null;
    qty: number | string;
    unit_label: string; 
    cost_price: number; // Disimpan untuk kalkulasi HPP
}

interface LaundryService {
    id: number;
    name: string;
    unit: string;
    price: number;
    is_active: boolean;
    estimated_materials: any; // JSON dari API
}

interface WarehouseItem {
    id: number;
    name: string;
    unit: string;
    category: string;
    current_stock: number;
    cost_price: number; // Harga modal gudang
}

// --- State ---
const services = ref<LaundryService[]>([]);
const warehouseItems = ref<WarehouseItem[]>([]);
const loading = ref(true);
const saving = ref(false);
const searchQuery = ref("");
const isEditMode = ref(false);

const modalRef = ref<null | HTMLElement>(null);
const formRef = ref<FormInstance>();

// Form State
const getInitialFormData = () => ({
    id: null as number | null,
    name: "",
    unit: "kg",
    price: "",
    is_active: true,
    materials: [] as MaterialInput[]
});

const formData = ref(getInitialFormData());

// --- Computed ---
const activeServicesCount = computed(() => services.value.filter(s => s.is_active).length);

const filteredServices = computed(() => {
    if (!searchQuery.value) return services.value;
    return services.value.filter(s => s.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

// Mengambil kategori unik dari gudang yang hanya mengandung kata "laundry"
const laundryCategories = computed(() => {
    const categories = new Set(warehouseItems.value.map(i => i.category));
    return [...categories]
        .filter(Boolean)
        .filter(cat => cat.toLowerCase().includes('laundry'))
        .sort();
});

// Mendapatkan item gudang berdasarkan kategori yang dipilih
const getItemsByCategory = (category: string) => {
    if (!category) return [];
    return warehouseItems.value.filter(i => i.category === category);
};

// --- HPP & Profit Calculations ---
const calculateRowHPP = (item: MaterialInput) => {
    const qty = parseFloat(String(item.qty)) || 0;
    return qty * (item.cost_price || 0);
};

const totalHPP = computed(() => {
    return formData.value.materials.reduce((acc, item) => acc + calculateRowHPP(item), 0);
});

const profitValue = computed(() => {
    const price = parseFloat(String(formData.value.price)) || 0;
    return price - totalHPP.value;
});

const profitMargin = computed(() => {
    const price = parseFloat(String(formData.value.price)) || 0;
    if (price === 0) return 0;
    return Math.round((profitValue.value / price) * 100);
});

// --- Validation Rules ---
const rules = ref<FormRules>({
  name: [{ required: true, message: "Nama layanan wajib diisi", trigger: "blur" }],
  unit: [{ required: true, message: "Pilih satuan", trigger: "change" }],
  price: [{ required: true, message: "Harga wajib diisi", trigger: "blur" }],
});

// --- Utils ---
const formatCurrency = (value: number | string) => {
  const val = typeof value === 'string' ? parseFloat(value) : value;
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(val || 0);
};

const countMaterials = (materialsJSON: any) => {
    if (!materialsJSON) return 0;
    try {
        const parsed = typeof materialsJSON === 'string' ? JSON.parse(materialsJSON) : materialsJSON;
        return Array.isArray(parsed) ? parsed.length : 0;
    } catch (e) {
        return 0;
    }
};

// --- Actions ---
const fetchServices = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/admin/laundry/services');
        services.value = response.data.data;
    } catch (error) {
        console.error("Gagal mengambil data laundry", error);
    } finally {
        loading.value = false;
    }
};

const fetchWarehouseItems = async () => {
    try {
        const response = await axios.get('/warehouse/items?per_page=500&status=active'); 
        warehouseItems.value = response.data.data;
    } catch (error) {
        console.error("Gagal ambil data gudang", error);
    }
};

const openModal = (service: LaundryService | null = null) => {
    formRef.value?.resetFields();
    
    if (service) {
        isEditMode.value = true;
        let parsedMaterials = [];
        
        // Parse JSON materials dari database
        if (service.estimated_materials) {
             parsedMaterials = typeof service.estimated_materials === 'string' 
                ? JSON.parse(service.estimated_materials) 
                : service.estimated_materials;
        }

        // Mapping ulang unit label dan cost price dari data warehouse
        const mappedMaterials = parsedMaterials.map((m: any) => {
            const wItem = warehouseItems.value.find(w => w.id == m.warehouse_item_id);
            return {
                category_filter: wItem ? wItem.category : '',
                warehouse_item_id: m.warehouse_item_id,
                qty: m.qty,
                unit_label: wItem ? wItem.unit : '',
                cost_price: wItem ? wItem.cost_price : 0
            };
        });

        formData.value = {
            id: service.id,
            name: service.name,
            unit: service.unit,
            price: String(service.price),
            is_active: Boolean(service.is_active),
            materials: mappedMaterials
        };
    } else {
        isEditMode.value = false;
        formData.value = getInitialFormData();
    }

    const modalEl = document.getElementById("kt_modal_laundry");
    if (modalEl) new Modal(modalEl).show();
};

const deleteService = (id: number) => {
    Swal.fire({
        text: "Hapus layanan laundry ini secara permanen?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, Hapus",
        cancelButtonText: "Batal",
        customClass: { confirmButton: "btn fw-bold btn-danger", cancelButton: "btn fw-bold btn-light" },
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(`/admin/laundry/services/${id}`);
                Swal.fire("Berhasil!", "Layanan dihapus.", "success");
                fetchServices();
            } catch (error) {
                Swal.fire("Error", "Gagal menghapus.", "error");
            }
        }
    });
};

// --- Materials Logic ---
const addMaterialRow = () => {
    formData.value.materials.push({ category_filter: "", warehouse_item_id: null, qty: "", unit_label: "", cost_price: 0 });
};

const removeMaterialRow = (index: number) => {
    formData.value.materials.splice(index, 1);
};

const onCategoryChange = (index: number) => {
    // Reset bahan dan satuan saat kategori diganti
    formData.value.materials[index].warehouse_item_id = null;
    formData.value.materials[index].unit_label = "";
    formData.value.materials[index].qty = "";
    formData.value.materials[index].cost_price = 0;
};

const onMaterialSelect = (id: number, index: number) => {
    const selected = warehouseItems.value.find(i => i.id === id);
    if (selected) {
        formData.value.materials[index].unit_label = selected.unit;
        formData.value.materials[index].cost_price = selected.cost_price;
    }
};

// --- Submit Logic ---
const submit = () => {
    if (!formRef.value) return;
    
    formRef.value.validate(async (valid: boolean) => {
        if (valid) {
            saving.value = true;
            
            // Format data sesuai request backend
            const payload = {
                name: formData.value.name,
                unit: formData.value.unit,
                price: parseFloat(String(formData.value.price)),
                is_active: formData.value.is_active,
                // Filter bahan yang valid (ada ID dan qty > 0)
                estimated_materials: formData.value.materials
                    .filter(m => m.warehouse_item_id && Number(m.qty) > 0)
                    .map(m => ({
                        warehouse_item_id: m.warehouse_item_id,
                        qty: parseFloat(String(m.qty))
                    }))
            };

            try {
                if (isEditMode.value) {
                    await axios.put(`/admin/laundry/services/${formData.value.id}`, payload);
                } else {
                    await axios.post("/admin/laundry/services", payload);
                }
                
                Swal.fire({ text: `Layanan berhasil disimpan!`, icon: "success", timer: 1500, showConfirmButton: false })
                    .then(() => {
                        const modalEl = document.getElementById("kt_modal_laundry");
                        if (modalEl) Modal.getInstance(modalEl)?.hide();
                        fetchServices();
                    });
            } catch (error: any) {
                console.error(error);
                Swal.fire({ text: "Gagal menyimpan data.", icon: "error" });
            } finally {
                saving.value = false;
            }
        } else {
            Swal.fire({ text: "Mohon periksa kembali isian form Anda.", icon: "warning" });
        }
    });
};

onMounted(() => {
    fetchWarehouseItems();
    fetchServices();
});
</script>

<style scoped>
/* ========================
   THEME COLORS & STYLES (Light Mode Default)
   ======================== */
.text-primary { color: #009EF7 !important; }
.bg-light-primary { background-color: #F1FAFF !important; }
.btn-primary { background-color: #009EF7; color: white; border: none; }
.btn-primary:hover { background-color: #008be1; color: white; }
.hover-text-primary:hover { color: #009EF7 !important; }
.box-shadow-primary { box-shadow: 0 4px 12px rgba(0, 158, 247, 0.3); }

/* ========================
   CARD & IMAGE (Sama dengan Index.vue)
   ======================== */
.group-card { position: relative; z-index: 1; transition: all 0.3s ease; }
.group-card:hover { z-index: 10; }
.hover-elevate-up:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.12) !important; }

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

/* Element Plus Form Overrides */
.h-35px { height: 35px !important; }
.form-select-sm { padding-top: 0.25rem; padding-bottom: 0.25rem; font-size: 0.9rem; }

:deep(.metronic-input .el-input__wrapper), :deep(.metronic-input .el-textarea__inner) { 
    background-color: #F9F9F9; 
    box-shadow: none !important; 
    border: 1px solid transparent; 
    border-radius: 0.5rem; 
    padding: 6px 12px; 
    transition: all 0.2s; 
}
:deep(.metronic-input .el-input__wrapper.is-focus) { 
    background-color: #ffffff; 
    border-color: #009EF7 !important; 
    box-shadow: 0 0 0 3px rgba(0, 158, 247, 0.1) !important; 
}
</style>

<!-- STYLE GLOBAL (UNSCOPED) UNTUK MENANGKAP ATRIBUT DATA-BS-THEME -->
<style>
/* ========================
   GLOBAL DARK MODE (Untuk Komponen Setting Laundry)
   ======================== */
[data-bs-theme="dark"] .laundry-setting-wrapper .theme-card { background-color: #1e1e2d !important; color: #ffffff; }
[data-bs-theme="dark"] .laundry-setting-wrapper .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .laundry-setting-wrapper .text-gray-800 { color: #ffffff !important; } 
[data-bs-theme="dark"] .laundry-setting-wrapper .text-gray-700 { color: #CDCDDE !important; }
[data-bs-theme="dark"] .laundry-setting-wrapper .text-gray-500 { color: #9A9CAE !important; }
[data-bs-theme="dark"] .laundry-setting-wrapper .bg-body { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .laundry-setting-wrapper .bg-light { background-color: #2b2b40 !important; }
[data-bs-theme="dark"] .laundry-setting-wrapper .bg-light-primary { background-color: rgba(0, 158, 247, 0.15) !important; }
[data-bs-theme="dark"] .laundry-setting-wrapper .bg-light-success { background-color: rgba(23, 198, 83, 0.15) !important; }
[data-bs-theme="dark"] .laundry-setting-wrapper .bg-light-danger { background-color: rgba(248, 40, 90, 0.15) !important; }
[data-bs-theme="dark"] .laundry-setting-wrapper .bg-light-warning { background-color: rgba(241, 65, 108, 0.15) !important; color: #f1416c !important; }
[data-bs-theme="dark"] .laundry-setting-wrapper .border-gray-200, 
[data-bs-theme="dark"] .laundry-setting-wrapper .border-gray-300 { border-color: #2B2B40 !important; }
[data-bs-theme="dark"] .laundry-setting-wrapper .form-control-solid { background-color: #1b1b29 !important; border-color: #323248 !important; color: #ffffff; }

/* Dark Mode untuk Modal */
[data-bs-theme="dark"] .theme-modal { background-color: #1e1e2d; color: #ffffff; }
[data-bs-theme="dark"] .theme-modal .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .theme-modal .text-gray-800 { color: #e1e1e8 !important; }
[data-bs-theme="dark"] .theme-modal .bg-light-subtle { background-color: #1b1b29 !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .theme-modal .table-row-dashed tr { border-bottom-color: #323248 !important; }

/* Override Form Element Plus di Dark Mode */
[data-bs-theme="dark"] .theme-modal .metronic-input .el-input__wrapper,
[data-bs-theme="dark"] .theme-modal .metronic-input .el-textarea__inner { 
    background-color: #151521 !important; 
    border-color: #323248 !important; 
    box-shadow: none !important;
}
[data-bs-theme="dark"] .theme-modal .metronic-input .el-input__inner { color: #ffffff !important; }
</style>