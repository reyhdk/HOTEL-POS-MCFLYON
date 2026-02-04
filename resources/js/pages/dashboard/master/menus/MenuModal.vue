<template>
  <div class="modal fade" id="kt_modal_menu" ref="modalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
      <div class="modal-content rounded-3 shadow-lg border-0 theme-modal">
        
        <div class="modal-header border-0 pb-0 justify-content-between align-items-center py-4 px-5">
            <h2 class="fw-bold m-0 fs-3 text-gray-900">{{ modalTitle }}</h2>
            <div class="btn btn-sm btn-icon btn-active-light-primary rounded-circle" data-bs-dismiss="modal">
                <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
            </div>
        </div>

        <div class="modal-body scroll-y px-5 pb-5 pt-2">
          <!-- Menghapus v-on:submit.prevent karena parser bermasalah dengan modifier dot (.) -->
          <el-form :model="formData" :rules="rules" ref="formRef" label-position="top" class="compact-form">
            
            <div class="row g-5">
                <!-- KOLOM KIRI: Informasi Dasar Menu -->
                <div class="col-lg-4 border-end border-gray-300">
                    <h4 class="fw-bold text-gray-800 mb-4">Informasi Produk</h4>
                    
                    <div class="mb-5">
                        <div class="image-upload-box d-flex align-items-center justify-content-center position-relative rounded-3 overflow-hidden border border-dashed border-gray-300 bg-light-subtle transition-300"
                            :class="{ 'has-image': imagePreview }"
                            :style="{ backgroundImage: `url(${imagePreview})` }">
                            
                            <div v-if="!imagePreview" class="text-center p-3">
                                <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                                    <i class="ki-duotone ki-picture fs-2 text-gray-400"><span class="path1"></span><span class="path2"></span></i>
                                    <span class="fs-7 fw-bold text-gray-600">Foto</span>
                                </div>
                            </div>

                            <div class="hover-overlay position-absolute w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50 backdrop-blur gap-2">
                                <label class="btn btn-sm btn-icon btn-orange rounded-circle shadow-sm hover-scale cursor-pointer">
                                    <i class="ki-duotone ki-pencil fs-4 text-white"><span class="path1"></span><span class="path2"></span></i>
                                    <input type="file" accept=".png, .jpg, .jpeg" v-on:change="handleImageChange" class="d-none" />
                                </label>
                                <button v-if="imagePreview" v-on:click="removeImage" type="button" class="btn btn-sm btn-icon btn-danger rounded-circle shadow-sm hover-scale">
                                    <i class="ki-duotone ki-trash fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <el-form-item prop="name" label="Nama Menu" class="mb-3">
                        <el-input v-model="formData.name" placeholder="Nama Menu" class="metronic-input fw-bold" />
                    </el-form-item>

                    <el-form-item prop="category" label="Kategori Menu" class="mb-3">
                        <el-select v-model="formData.category" placeholder="Pilih..." class="w-100 metronic-input">
                            <el-option value="Makanan" label="Makanan" />
                            <el-option value="Minuman" label="Minuman" />
                            <el-option value="Snack" label="Snack" />
                        </el-select>
                    </el-form-item>
                    
                    <div class="row">
                        <div class="col-6">
                            <el-form-item prop="price" label="Harga Jual" class="mb-3">
                                <el-input v-model="formData.price" type="number" class="metronic-input text-end fw-bold">
                                </el-input>
                            </el-form-item>
                        </div>
                        <div class="col-6">
                            <el-form-item prop="stock" label="Stok Siap" class="mb-3">
                                <div class="d-flex flex-column">
                                    <el-input v-model="formData.stock" type="number" class="metronic-input text-center fw-bold mb-1" />
                                    <button type="button" v-on:click="calculateAutoStock" class="btn btn-sm btn-light-success py-1 px-2 fs-9 fw-bold mt-1 border border-success border-opacity-25" title="Hitung otomatis berdasarkan ketersediaan bahan">
                                        <i class="ki-duotone ki-calculator fs-8 me-1"><span class="path1"></span><span class="path2"></span></i> Hitung Max
                                    </button>
                                </div>
                            </el-form-item>
                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN: Resep & Kalkulasi HPP -->
                <div class="col-lg-8 ps-lg-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="fw-bold text-gray-800 m-0">Resep & Bahan Baku</h4>
                            <span class="text-muted fs-8">Pilih Kategori, lalu pilih Bahan.</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-light-primary fw-bold" v-on:click="addIngredientRow">
                            <i class="ki-duotone ki-plus fs-3"></i> Tambah Bahan
                        </button>
                    </div>

                    <div class="table-responsive mb-4 bg-light-subtle rounded border border-gray-200 p-1" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-3 m-0">
                            <thead class="fw-bold fs-8 text-gray-500 text-uppercase bg-body">
                                <tr>
                                    <th class="ps-3 min-w-100px">Kategori</th>
                                    <th class="min-w-150px">Nama Bahan</th>
                                    <th class="min-w-80px text-center">Jumlah</th>
                                    <th class="min-w-80px">Satuan</th>
                                    <th class="min-w-100px text-end pe-3">Subtotal</th>
                                    <th class="w-50px text-center">Hapus</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="formData.ingredients.length === 0">
                                    <td colspan="6" class="text-center py-5 text-muted fs-7">
                                        Belum ada bahan baku. Klik "Tambah Bahan".
                                    </td>
                                </tr>
                                <tr v-for="(item, index) in formData.ingredients" :key="index" class="animate-fade-in">
                                    <!-- 1. PILIH KATEGORI GUDANG -->
                                    <td class="ps-2">
                                        <el-select 
                                            v-model="item.category_filter" 
                                            placeholder="Pilih Kat." 
                                            class="w-100 metronic-input form-select-sm"
                                            v-on:change="() => onCategoryChange(index)">
                                            <el-option 
                                                v-for="cat in uniqueItemCategories" 
                                                :key="cat" 
                                                :label="cat" 
                                                :value="cat" 
                                            />
                                        </el-select>
                                    </td>

                                    <!-- 2. PILIH ITEM (DIFILTER BERDASARKAN KATEGORI) -->
                                    <td>
                                        <el-select 
                                            v-model="item.warehouse_item_id" 
                                            filterable 
                                            placeholder="Pilih Bahan..." 
                                            class="w-100 metronic-input form-select-sm"
                                            :disabled="!item.category_filter"
                                            v-on:change="(val) => onIngredientSelect(val, index)">
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
                                            v-model="item.quantity" 
                                            type="number" 
                                            step="0.001" 
                                            min="0"
                                            placeholder="0" 
                                            class="metronic-input text-center h-35px" />
                                    </td>
                                    <td>
                                        <!-- Dropdown Satuan dengan Logika Validasi -->
                                        <el-select 
                                            v-model="item.unit_label" 
                                            placeholder="Satuan" 
                                            class="w-100 metronic-input form-select-sm"
                                            :disabled="!item.warehouse_item_id">
                                            <el-option 
                                                v-for="unit in getAvailableUnits(item.base_unit)" 
                                                :key="unit" 
                                                :label="unit" 
                                                :value="unit" 
                                            />
                                        </el-select>
                                    </td>
                                    <td class="text-end pe-2">
                                        <span class="text-gray-800 fw-bold fs-7">{{ formatCurrency(calculateRowSubtotal(item)) }}</span>
                                    </td>
                                    <td class="text-center pe-2">
                                        <button type="button" v-on:click="removeIngredientRow(index)" class="btn btn-icon btn-sm btn-light-danger btn-active-danger w-30px h-30px rounded-circle" title="Hapus Bahan">
                                            <i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- SUMMARY CARD -->
                    <div class="card bg-light-warning border-warning border border-dashed shadow-sm">
                        <div class="card-body p-4">
                            <div class="row text-center">
                                <div class="col-4 border-end border-warning border-opacity-25">
                                    <div class="fs-8 text-gray-600 fw-semibold text-uppercase">Total HPP (Modal)</div>
                                    <div class="fs-4 fw-bolder text-gray-900 mt-1">{{ formatCurrency(totalHPP) }}</div>
                                </div>
                                <div class="col-4 border-end border-warning border-opacity-25">
                                    <div class="fs-8 text-gray-600 fw-semibold text-uppercase">Harga Jual</div>
                                    <div class="fs-4 fw-bolder text-gray-900 mt-1">{{ formatCurrency(formData.price || 0) }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="fs-8 text-gray-600 fw-semibold text-uppercase">Estimasi Profit</div>
                                    <div class="fs-4 fw-bolder mt-1" :class="profitValue > 0 ? 'text-success' : 'text-danger'">
                                        {{ formatCurrency(profitValue) }}
                                    </div>
                                    <div class="fs-9 fw-bold" :class="profitValue > 0 ? 'text-success' : 'text-danger'">
                                        ({{ profitMargin }}%)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-dismissible bg-light-primary border border-primary border-dashed d-flex flex-column flex-sm-row p-3 mt-3">
                         <i class="ki-duotone ki-information-5 fs-2hx text-primary me-4 mb-5 mb-sm-0"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                         <div class="d-flex flex-column pe-0 pe-sm-10">
                            <h5 class="mb-1">Info Konversi Satuan</h5>
                            <span class="fs-8 text-gray-600">
                                <ul class="mb-0 ps-3">
                                    <li><b>Berat:</b> Kg, Gram, Ons (100g), Pon (500g), Kwintal.</li>
                                    <li><b>Volume:</b> Liter, ML, SDM (15ml), SDT (5ml), Cup (250ml).</li>
                                    <li><b>Jumlah:</b> Pcs, Lusin (12), Kodi (20), Gross (144).</li>
                                    <li class="mt-2 text-dark fw-bold">Tips: Anda bisa menggunakan angka desimal. <br/>Contoh: Input <b>0.1 Botol</b> jika hanya menggunakan sebagian kecil.</li>
                                </ul>
                            </span>
                         </div>
                    </div>

                </div>
            </div>

            <div class="d-flex justify-content-end align-items-center mt-5 pt-3 border-top border-gray-200">
                 <button type="button" class="btn btn-sm btn-light me-3 fw-bold text-gray-700 px-5" data-bs-dismiss="modal">Batal</button>
                 <!-- Mengubah type submit menjadi button dan menggunakan @click untuk menghindari isu modifier form -->
                 <button :disabled="loading" class="btn btn-orange fw-bold px-6 shadow-sm hover-elevate" type="button" v-on:click="submit">
                    <span v-if="!loading" class="d-flex align-items-center">
                        Simpan Menu <i class="ki-duotone ki-check-circle fs-2 ms-2 text-white"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <span v-if="loading" class="indicator-progress d-flex align-items-center">
                        <span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...
                    </span>
                 </button>
            </div>

          </el-form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import axios from "@/libs/axios";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import type { FormInstance, FormRules } from 'element-plus'

// --- Interfaces ---
interface IngredientInput {
    category_filter: string; // [BARU] Untuk filter dropdown
    warehouse_item_id: number | null;
    quantity: number;
    unit_label: string; 
    base_unit: string;  
    cost_price: number; 
}

interface MenuData { 
    id: number; 
    name: string; 
    category: string; 
    price: number; 
    stock: number; 
    image_url: string | null;
    ingredients?: any[];
}

interface FormData { 
    id: number | null; 
    name: string; 
    category: string; 
    price: number | string; 
    stock: number | string; 
    image: File | null; 
    ingredients: IngredientInput[];
}

interface WarehouseItem {
    id: number;
    name: string;
    unit: string;
    category: string; // Pastikan backend mengirim ini
    cost_price: number;
    current_stock: number;
}

const props = defineProps<{ menuData: MenuData | null }>();
const emit = defineEmits(['menu-updated']);

// --- Refs ---
const formRef = ref<FormInstance>();
const modalRef = ref<null | HTMLElement>(null);
const loading = ref(false);
const imagePreview = ref<string | null>(null);
const warehouseItems = ref<WarehouseItem[]>([]);
const isEditMode = computed(() => !!props.menuData);
const modalTitle = computed(() => isEditMode.value ? 'Edit Menu & Resep' : 'Menu Baru & Resep');

const getInitialFormData = (): FormData => ({ 
    id: null, 
    name: "", 
    category: "", 
    price: "", 
    stock: "", 
    image: null,
    ingredients: [] 
});

const formData = ref<FormData>(getInitialFormData());

// --- Helper: Advanced Unit Conversion Logic ---

// 1. Normalisasi string satuan agar tidak case-sensitive
const normalizeUnit = (u: string) => u ? u.toLowerCase().trim() : '';

// 2. Kategori Satuan & Faktor Konversi
const unitFactors: Record<string, number> = {
    // Berat (Base: Gram)
    'kg': 1000, 'kilo': 1000, 'kilogram': 1000,
    'hg': 100, 'ons': 100, 'pon': 500, 'kwintal': 100000, 'ton': 1000000,
    'gram': 1, 'gr': 1, 'g': 1, 'mg': 0.001,

    // Volume (Base: ML)
    'liter': 1000, 'ltr': 1000, 'l': 1000,
    'ml': 1, 'cc': 1, 'milliliter': 1,
    'sdt': 5, 'tsp': 5, 'sdm': 15, 'tbsp': 15, 'cup': 250, 'gelas': 250,

    // Hitung (Base: Pcs)
    'pcs': 1, 'pc': 1, 'buah': 1, 'biji': 1, 'unit': 1, 'btl': 1, 'botol': 1, 'bungkus': 1, 'bks': 1, 'sachet': 1, 'pack': 1, 'pax': 1,
    'lusin': 12, 'dozen': 12, 'kodi': 20, 'gross': 144, 'rim': 500
};

// 3. Tentukan kategori unit
const getUnitCategory = (unit: string): 'weight' | 'volume' | 'count' | 'unknown' => {
    const u = normalizeUnit(unit);
    if (['kg', 'kilo', 'kilogram', 'hg', 'ons', 'pon', 'kwintal', 'ton', 'gram', 'gr', 'g', 'mg'].includes(u)) return 'weight';
    if (['liter', 'ltr', 'l', 'ml', 'cc', 'milliliter', 'sdt', 'tsp', 'sdm', 'tbsp', 'cup', 'gelas'].includes(u)) return 'volume';
    if (['pcs', 'pc', 'buah', 'biji', 'unit', 'btl', 'botol', 'bungkus', 'bks', 'sachet', 'pack', 'pax', 'lusin', 'dozen', 'kodi', 'gross', 'rim'].includes(u)) return 'count';
    return 'unknown';
}

// 4. Daftar Satuan untuk Dropdown
const getAvailableUnits = (baseUnit: string) => {
    if (!baseUnit) return [];
    const category = getUnitCategory(baseUnit);
    let options: string[] = [];
    
    if (category === 'weight') options = ['Kg', 'Gram', 'Ons (100g)', 'Pon (500g)', 'Kwintal'];
    else if (category === 'volume') options = ['Liter', 'ML', 'SDM (15ml)', 'SDT (5ml)', 'Cup (250ml)'];
    else if (category === 'count') options = ['Pcs', 'Lusin (12)', 'Kodi (20)', 'Gross (144)'];
    else return [baseUnit];

    const baseNormalized = normalizeUnit(baseUnit);
    const exists = options.some(opt => normalizeUnit(opt).includes(baseNormalized) || normalizeUnit(opt) === baseNormalized);
    if (!exists) options.unshift(baseUnit);

    return options;
}

// 5. Rumus Konversi
const convertToBase = (qty: number, fromUnitLabel: string, baseUnit: string): number => {
    if (!qty || !fromUnitLabel || !baseUnit) return qty;
    const cleanFrom = normalizeUnit(fromUnitLabel.split('(')[0]); 
    const cleanBase = normalizeUnit(baseUnit);
    if (cleanFrom === cleanBase) return qty;

    const factorFrom = unitFactors[cleanFrom];
    const factorBase = unitFactors[cleanBase];

    if (factorFrom && factorBase && getUnitCategory(cleanFrom) === getUnitCategory(cleanBase)) {
        return (qty * factorFrom) / factorBase;
    }
    return qty;
}

// --- Data Fetching ---
const fetchWarehouseItems = async () => {
    try {
        const response = await axios.get('/warehouse/items?per_page=100&status=active'); 
        warehouseItems.value = response.data.data;
    } catch (e) {
        console.error("Gagal ambil data gudang", e);
    }
};

onMounted(() => { fetchWarehouseItems(); });

// --- Filter Categories Logic ---
const uniqueItemCategories = computed(() => {
    // Ambil kategori unik dari list barang gudang
    const cats = warehouseItems.value.map(i => i.category);
    return [...new Set(cats)].filter(Boolean).sort();
});

const getItemsByCategory = (category: string) => {
    if (!category) return [];
    return warehouseItems.value.filter(i => i.category === category);
};

const onCategoryChange = (index: number) => {
    // Reset item saat kategori berubah agar tidak rancu
    formData.value.ingredients[index].warehouse_item_id = null;
    formData.value.ingredients[index].base_unit = '';
    formData.value.ingredients[index].unit_label = '';
    formData.value.ingredients[index].cost_price = 0;
};

// --- Watcher for Edit Mode ---
watch(() => props.menuData, (newVal) => {
  if (newVal) {
    formData.value = { 
        ...newVal, 
        price: String(newVal.price), 
        stock: String(newVal.stock), 
        image: null,
        ingredients: newVal.ingredients ? newVal.ingredients.map((i: any) => {
            // Cari item di gudang untuk mendapatkan kategorinya (Auto-fill kategori saat edit)
            const matchedItem = warehouseItems.value.find(w => w.id === i.id);
            const category = matchedItem ? matchedItem.category : '';

            return {
                warehouse_item_id: i.id,
                category_filter: category, // SET KATEGORI DISINI
                quantity: parseFloat(i.pivot.quantity), 
                unit_label: i.unit, 
                base_unit: i.unit,
                cost_price: parseFloat(i.cost_price)
            };
        }) : []
    };
    imagePreview.value = newVal.image_url;
  } else {
    formRef.value?.resetFields();
    formData.value = getInitialFormData();
    imagePreview.value = null;
  }
});

// --- Ingredient UI Logic ---
const addIngredientRow = () => {
    formData.value.ingredients.push({
        category_filter: '', // Start empty
        warehouse_item_id: null,
        quantity: 0,
        unit_label: '',
        base_unit: '',
        cost_price: 0
    });
};

const removeIngredientRow = (index: number) => {
    formData.value.ingredients.splice(index, 1);
};

const onIngredientSelect = (id: number, index: number) => {
    const selected = warehouseItems.value.find(i => i.id === id);
    if (selected) {
        formData.value.ingredients[index].base_unit = selected.unit;
        formData.value.ingredients[index].unit_label = selected.unit; 
        formData.value.ingredients[index].cost_price = selected.cost_price;
    }
};

// --- Auto Calculate Stock Logic ---
const calculateAutoStock = () => {
    let minStock = Infinity;
    let hasIngredients = false;

    formData.value.ingredients.forEach(ing => {
        if (ing.warehouse_item_id && ing.quantity > 0) {
            hasIngredients = true;
            const wItem = warehouseItems.value.find(x => x.id === ing.warehouse_item_id);
            
            if (wItem) {
                const usageInBase = convertToBase(ing.quantity, ing.unit_label, wItem.unit);
                if (usageInBase > 0) {
                    const possiblePortions = Math.floor(wItem.current_stock / usageInBase);
                    if (possiblePortions < minStock) {
                        minStock = possiblePortions;
                    }
                }
            }
        }
    });

    if (hasIngredients && minStock !== Infinity) {
        formData.value.stock = minStock;
        Swal.fire({
            toast: true, position: 'top-end', icon: 'success', 
            title: `Stok Max: ${minStock} porsi`, showConfirmButton: false, timer: 2000 
        });
    } else {
        Swal.fire({ icon: 'warning', text: hasIngredients ? 'Stok bahan tidak cukup.' : 'Tambahkan bahan baku terlebih dahulu.' });
    }
}

// --- Cost Calculations ---
const calculateRowSubtotal = (item: IngredientInput) => {
    const qtyBase = convertToBase(item.quantity, item.unit_label, item.base_unit);
    return (qtyBase || 0) * (item.cost_price || 0);
};

const totalHPP = computed(() => {
    return formData.value.ingredients.reduce((acc, item) => acc + calculateRowSubtotal(item), 0);
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

const formatCurrency = (value: number | string) => {
  const val = typeof value === 'string' ? parseFloat(value) : value;
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(val || 0);
};

// --- Form Submit ---
const handleImageChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];
    if (file.size > 2 * 1024 * 1024) { Swal.fire({text: "Ukuran max 2MB", icon: "warning"}); return; }
    formData.value.image = file;
    const reader = new FileReader();
    reader.onload = (e) => { imagePreview.value = e.target?.result as string; };
    reader.readAsDataURL(file);
  }
};

const removeImage = () => { imagePreview.value = null; formData.value.image = null; };

const rules = ref<FormRules>({
  name: [{ required: true, message: "Wajib diisi", trigger: "blur" }],
  category: [{ required: true, message: "Pilih kategori", trigger: "change" }],
  price: [{ required: true, message: "Wajib diisi", trigger: "blur" }],
  stock: [{ required: true, message: "Wajib diisi", trigger: "blur" }], 
});

const submit = () => {
  if (!formRef.value) return;
  
  formRef.value.validate(async (valid: boolean) => {
    if (valid) {
      loading.value = true;
      const data = new FormData();
      data.append('name', formData.value.name);
      data.append('category', formData.value.category);
      data.append('price', String(formData.value.price));
      data.append('stock', String(formData.value.stock));
      if (formData.value.image) data.append('image', formData.value.image);

      formData.value.ingredients.forEach((item, index) => {
          if(item.warehouse_item_id) {
              const qtyToSend = convertToBase(item.quantity, item.unit_label, item.base_unit);
              data.append(`ingredients[${index}][id]`, String(item.warehouse_item_id));
              data.append(`ingredients[${index}][quantity]`, String(qtyToSend));
          }
      });

      try {
        if (isEditMode.value) {
            data.append('_method', 'PUT');
            await axios.post(`/menus/${formData.value.id}`, data);
        } else {
            await axios.post("/menus", data);
        }
        
        Swal.fire({ text: `Berhasil disimpan!`, icon: "success", timer: 1500, showConfirmButton: false })
            .then(() => {
                if (modalRef.value) Modal.getInstance(modalRef.value)?.hide();
                emit('menu-updated');
            });
      } catch (error: any) {
        console.error(error);
        Swal.fire({ text: "Gagal menyimpan data.", icon: "error" });
      } finally {
        loading.value = false;
      }
    } else {
        Swal.fire({ text: "Mohon lengkapi formulir.", icon: "warning" });
        return; 
    }
  });
};
</script>

<style scoped>
/* ========================
   THEME COLORS & UTILS
   ======================== */
.text-orange { color: #F68B1E !important; }
.btn-orange { background-color: #F68B1E; color: white; border: none; }
.btn-orange:hover { background-color: #d97814; color: white; }

.image-upload-box {
    height: 180px; 
    background-size: cover; background-position: center;
    transition: all 0.2s ease;
}
.image-upload-box:hover { border-color: #F68B1E !important; }
.image-upload-box.has-image { border-style: solid; }

.hover-overlay { opacity: 0; transition: opacity 0.2s ease; }
.image-upload-box:hover .hover-overlay { opacity: 1; }
.backdrop-blur { backdrop-filter: blur(2px); }

/* Compact inputs for table */
.h-35px { height: 35px !important; }
.form-select-sm { padding-top: 0.25rem; padding-bottom: 0.25rem; font-size: 0.9rem; }

:deep(.metronic-input .el-input__wrapper), 
:deep(.metronic-input .el-textarea__inner) {
    background-color: #F9F9F9;
    box-shadow: none !important; 
    border: 1px solid transparent; 
    border-radius: 0.5rem; 
    padding: 6px 12px;
    transition: all 0.2s;
}

:deep(.metronic-input .el-input__wrapper.is-focus) {
    background-color: #ffffff;
    border-color: #F68B1E !important;
    box-shadow: 0 0 0 3px rgba(246, 139, 30, 0.1) !important;
}

/* Dark Mode Support */
[data-bs-theme="dark"] .theme-modal { background-color: #1e1e2d; color: #ffffff; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-gray-800 { color: #e1e1e8 !important; }
[data-bs-theme="dark"] .bg-light-subtle { background-color: #1b1b29 !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .table-row-dashed tr { border-bottom-color: #323248 !important; }
[data-bs-theme="dark"] :deep(.metronic-input .el-input__wrapper) { background-color: #151521 !important; border-color: #323248 !important; }
</style>