<template>
  <div class="d-flex flex-column gap-5">
    
    <!-- 1. KARTU STATISTIK (TETAP SAMA) -->
    <div class="row g-5 g-xl-8">
      <!-- Total Barang -->
      <div class="col-xl-3 col-md-6 animate-item" style="--delay: 0s">
        <div class="card bg-body hover-elevate-up border-0 h-100 theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-primary text-primary rounded-4">
                <i class="ki-duotone ki-abstract-39 fs-2x text-primary"><span class="path1"></span><span class="path2"></span></i>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ stats.totalItems }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Total Barang</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Nilai Stok -->
      <div class="col-xl-3 col-md-6 animate-item" style="--delay: 0.1s">
        <div class="card bg-body hover-elevate-up border-0 h-100 theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-success text-success rounded-4">
                <i class="ki-duotone ki-dollar fs-2x text-success"><span class="path1"></span><span class="path2"></span></i>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ formatCurrency(stats.stockValue) }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Total Aset</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Stok Rendah -->
      <div class="col-xl-3 col-md-6 animate-item" style="--delay: 0.2s">
        <div class="card bg-body hover-elevate-up border-0 h-100 theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-warning text-warning rounded-4">
                <i class="ki-duotone ki-down fs-2x text-warning"><span class="path1"></span><span class="path2"></span></i>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ stats.lowStockItems }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Stok Menipis</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Barang Aktif -->
      <div class="col-xl-3 col-md-6 animate-item" style="--delay: 0.3s">
        <div class="card bg-body hover-elevate-up border-0 h-100 theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-info text-info rounded-4">
                <i class="ki-duotone ki-check-square fs-2x text-info">
                  <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ stats.activeItems }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Barang Aktif</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. FILTER & SEARCH (TETAP SAMA) -->
    <div class="card border-0 shadow-sm theme-card animate-item position-relative" style="--delay: 0.4s; z-index: 99;">
      <div class="card-body py-4">
        <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-4">
          
          <div class="d-flex align-items-center position-relative w-100 w-sm-300px">
            <i class="ki-duotone ki-magnifier fs-2 position-absolute ms-4 text-gray-500">
              <span class="path1"></span><span class="path2"></span>
            </i>
            <input 
              type="text" 
              v-model="searchQuery" 
              @input="debouncedSearch" 
              class="form-control form-control-solid ps-12 search-input" 
              placeholder="Cari nama atau kode..." 
            />
          </div>

          <div class="d-flex flex-wrap gap-3 align-items-center w-100 w-sm-auto justify-content-end">
            <button class="btn btn-sm btn-light-primary fw-bold hover-scale" @click="openCategoryModal">
              <i class="ki-duotone ki-setting-2 fs-2"><span class="path1"></span><span class="path2"></span></i> 
              Kategori & Satuan
            </button>

            <div class="dropdown-wrapper position-relative w-150px" v-click-outside="() => closeDropdown('category')">
              <button 
                class="btn btn-custom-select w-100 d-flex align-items-center justify-content-between px-4" 
                type="button" 
                @click="toggleDropdown('category')"
                :class="{ 'active': isDropdownOpen.category }"
              >
                <div class="d-flex align-items-center text-truncate">
                  <i class="ki-duotone ki-category fs-2 me-2 text-gray-500">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                  </i>
                  <span class="fw-bold text-gray-700 fs-7">{{ activeCategoryLabel }}</span>
                </div>
                <i class="ki-duotone ki-down fs-5 text-gray-500 ms-2 transition-icon" :class="{ 'rotate-180': isDropdownOpen.category }"></i>
              </button>

              <transition name="dropdown-anim">
                <div v-if="isDropdownOpen.category" class="custom-dropdown-menu shadow-lg border-0 p-2 rounded-3 theme-dropdown">
                  <ul class="list-unstyled m-0">
                    <li>
                      <a href="#" class="dropdown-item-custom rounded px-3 py-2 fw-semibold mb-1" 
                         :class="{ 'selected': filters.category === 'all' }" 
                         @click.prevent="setFilter('category', 'all')">
                        Semua Kategori
                      </a>
                    </li>
                    <li v-for="cat in categories" :key="cat.value">
                      <a href="#" class="dropdown-item-custom rounded px-3 py-2 fw-semibold mb-1" 
                         :class="{ 'selected': filters.category === cat.value }" 
                         @click.prevent="setFilter('category', cat.value)">
                        {{ cat.label }}
                      </a>
                    </li>
                  </ul>
                </div>
              </transition>
            </div>

            <button class="btn btn-sm btn-primary fw-bold hover-scale box-shadow-primary" @click="openAddModal">
              <i class="ki-duotone ki-plus fs-2 text-white"></i> Tambah Barang
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. TABEL DATA (TETAP SAMA) -->
    <div v-if="loading" class="d-flex justify-content-center py-10">
      <div class="d-flex flex-column align-items-center">
        <span class="spinner-border text-primary mb-3 w-40px h-40px"></span>
        <span class="text-gray-500 fw-bold">Memuat data...</span>
      </div>
    </div>

    <div v-else-if="items.length === 0" class="d-flex flex-column align-items-center justify-content-center py-15 text-muted animate-fade-in">
      <div class="symbol symbol-100px mb-5 bg-light-primary rounded-circle d-flex align-items-center justify-content-center">
        <i class="ki-duotone ki-box fs-4x text-primary"><span class="path1"></span><span class="path2"></span></i>
      </div>
      <span class="fs-4 fw-bold text-gray-800">Tidak ada barang ditemukan.</span>
      <p class="text-gray-500 mt-2">Mulai dengan menambahkan barang baru</p>
      <button class="btn btn-primary mt-4" @click="openAddModal">
        <i class="ki-duotone ki-plus fs-2"></i> Tambah Barang
      </button>
    </div>

    <div v-else class="card border-0 shadow-sm theme-card animate-fade-in">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle table-row-dashed fs-6 gy-5">
            <thead class="bg-light-primary">
              <tr class="text-start text-gray-600 fw-bold fs-7 text-uppercase gs-0">
                <th class="ps-6 min-w-200px">Nama Barang</th>
                <th class="min-w-100px">Kategori</th>
                <th class="min-w-150px">Stok Tersedia</th>
                <th class="min-w-100px">Min. Stok</th>
                <th class="min-w-150px">Harga / Satuan</th>
                <th class="min-w-150px">Supplier</th>
                <th class="min-w-100px text-center">Status</th>
                <th class="text-end pe-6 min-w-100px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in items" :key="item.id">
                <td class="ps-6">
                  <div class="d-flex flex-column">
                    <span class="fw-bold text-gray-800 fs-6 mb-1">{{ item.name }}</span>
                    <span class="badge badge-light-dark fw-bold fs-9 w-fit-content border border-gray-200">{{ item.code }}</span>
                  </div>
                </td>
                <td>
                  <span class="badge fs-8 fw-bold" :class="getCategoryBadgeClass(item.category)">
                    {{ item.category }}
                  </span>
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <div class="d-flex align-items-center mb-1">
                      <span class="fw-bolder fs-6 me-1" :class="getStockTextColor(item)">
                        {{ formatQty(item.current_stock, item.unit, item.category) }}
                      </span>
                      <span class="text-gray-500 fs-7 fw-semibold">{{ item.unit }}</span>
                    </div>
                    <div class="progress h-4px w-100 bg-light rounded-pill">
                      <div class="progress-bar rounded-pill" role="progressbar" 
                           :style="{ width: getStockPercentage(item) + '%' }" 
                           :class="getStockColor(item)">
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="fw-bold text-gray-600 fs-7">
                    {{ formatQty(item.min_stock, item.unit, item.category) }} {{ item.unit }}
                  </span>
                </td>
                <td>
                    <div class="d-flex align-items-baseline">
                        <span class="fw-bold text-gray-800 fs-6">{{ formatCurrency(item.cost_price) }}</span>
                        <span class="text-gray-500 fs-8 fw-semibold ms-1">/ {{ item.unit }}</span>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <i class="ki-duotone ki-truck fs-3 text-gray-400 me-2" v-if="item.supplier"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                        <span class="text-gray-700 fs-7">{{ item.supplier || '-' }}</span>
                    </div>
                </td>
                <td class="text-center">
                  <span class="badge fw-bold fs-8" :class="item.is_active ? 'badge-light-success text-success' : 'badge-light-danger text-danger'">
                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td class="text-end pe-6">
                  <div class="d-flex justify-content-end gap-2">
                    <button @click="openEditModal(item)" class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary w-30px h-30px" data-bs-toggle="tooltip" title="Edit Detail">
                      <i class="ki-duotone ki-pencil fs-5"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                    <button @click="openStockModal(item)" class="btn btn-sm btn-icon btn-bg-light btn-active-color-warning w-30px h-30px" data-bs-toggle="tooltip" title="Sesuaikan Stok">
                      <i class="ki-duotone ki-finance-calculator fs-5"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                    <button @click="deleteItem(item.id)" class="btn btn-sm btn-icon btn-bg-light btn-active-color-danger w-30px h-30px" title="Hapus Barang">
                      <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-if="meta.total > meta.per_page" class="d-flex justify-content-between align-items-center border-top p-4">
          <div class="text-gray-600 fs-7">
            Menampilkan <span class="fw-bold">{{ (meta.current_page - 1) * meta.per_page + 1 }}</span> - 
            <span class="fw-bold">{{ Math.min(meta.current_page * meta.per_page, meta.total) }}</span> dari <span class="fw-bold">{{ meta.total }}</span> barang
          </div>
          <div class="d-flex gap-2">
            <button @click="changePage(meta.current_page - 1)" :disabled="meta.current_page === 1" class="btn btn-sm btn-light hover-elevate">
              <i class="ki-duotone ki-left fs-3"></i>
            </button>
            <button v-for="page in paginationPages" :key="page" @click="changePage(page)" class="btn btn-sm hover-elevate" :class="page === meta.current_page ? 'btn-primary' : 'btn-light'">
              {{ page }}
            </button>
            <button @click="changePage(meta.current_page + 1)" :disabled="meta.current_page === meta.last_page" class="btn btn-sm btn-light hover-elevate">
              <i class="ki-duotone ki-right fs-3"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 4. MODAL TAMBAH/EDIT BARANG (UPDATED: LOGIKA HARGA) -->
    <div class="modal fade" id="kt_modal_warehouse" ref="warehouseModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content rounded-3 shadow-lg border-0 theme-modal">
          
          <div class="modal-header border-0 pb-0 justify-content-between align-items-center py-4 px-5">
            <h2 class="fw-bold m-0 fs-3 text-gray-900">{{ isEditMode ? 'Edit Barang' : 'Tambah Barang Baru' }}</h2>
            <div class="btn btn-sm btn-icon btn-active-light-primary rounded-circle" data-bs-dismiss="modal">
              <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
            </div>
          </div>

          <div class="modal-body scroll-y px-5 pb-5 pt-2">
            <form @submit.prevent="submitItem" class="compact-form">
              
              <!-- Nama & Kode -->
              <div class="row g-5 mb-4">
                <div class="col-md-8">
                  <label class="required fw-bold fs-7 text-gray-700 mb-2">Nama Barang</label>
                  <input v-model="formData.name" type="text" class="form-control form-control-solid" placeholder="Contoh: Beras Premium, Minyak Goreng" required />
                </div>
                <div class="col-md-4">
                  <label class="required fw-bold fs-7 text-gray-700 mb-2">Kode Barang</label>
                  <div class="position-relative">
                    <input v-model="formData.code" type="text" class="form-control form-control-solid" placeholder="Kode Barang" required />
                    <span v-if="!isEditMode && isAutoCode" class="position-absolute top-50 end-0 translate-middle-y me-3 badge badge-light-success fs-9 fw-bold">AUTO</span>
                  </div>
                </div>
              </div>

              <!-- Kategori & Satuan -->
              <div class="row g-5 mb-4">
                <div class="col-md-6">
                  <label class="required fw-bold fs-7 text-gray-700 mb-2">Kategori</label>
                  <select v-model="formData.category" class="form-select form-select-solid" required @change="onCategoryChange">
                    <option value="">Pilih Kategori</option>
                    <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="required fw-bold fs-7 text-gray-700 mb-2">Satuan</label>
                  <select v-model="formData.unit" class="form-select form-select-solid" required :disabled="!formData.category">
                     <option value="">Pilih Satuan</option>
                     <option v-for="u in availableUnits" :key="u.name" :value="u.name">{{ u.name }}</option>
                  </select>
                  <div v-if="!formData.category" class="form-text text-xs text-muted mt-1">Pilih kategori dahulu untuk memuat satuan.</div>
                  <div v-if="formData.unit && selectedUnitPrecision !== null" class="form-text text-xs text-primary mt-1">
                      <i class="ki-duotone ki-information-2 fs-9 text-primary me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                      Tipe: {{ selectedUnitPrecision === 0 ? 'Bilangan Bulat (Fix)' : 'Pecahan/Desimal (0.00)' }}
                  </div>
                </div>
              </div>

              <!-- Stok & Harga (UPDATED LOGIC) -->
              <div class="separator separator-dashed my-4"></div>
              
              <div class="row g-5 mb-4">
                <!-- Stok Awal -->
                <div class="col-md-4">
                  <label class="required fw-bold fs-7 text-gray-700 mb-2">Stok Awal</label>
                  <div class="input-group input-group-solid">
                    <input v-model="formData.current_stock" type="number" :step="selectedUnitPrecision === 0 ? '1' : '0.01'" class="form-control form-control-solid" placeholder="0" required @input="calculateCostPrice" />
                    <span class="input-group-text fs-8 text-gray-500">{{ formData.unit || '-' }}</span>
                  </div>
                </div>
                <!-- Min Stok -->
                 <div class="col-md-4">
                  <label class="required fw-bold fs-7 text-gray-700 mb-2">Min. Stok</label>
                  <div class="input-group input-group-solid">
                    <input v-model="formData.min_stock" type="number" :step="selectedUnitPrecision === 0 ? '1' : '0.01'" class="form-control form-control-solid" placeholder="0" required />
                    <span class="input-group-text fs-8 text-gray-500">{{ formData.unit || '-' }}</span>
                  </div>
                </div>
                
                <!-- Harga Beli (DINAMIS) -->
                <div class="col-md-4">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                      <label class="required fw-bold fs-7 text-gray-700">Harga Beli</label>
                  </div>
                  
                  <!-- Switch Mode Harga -->
                  <div class="d-flex gap-2 mb-2 bg-light rounded p-1 border border-gray-300">
                    <button type="button" 
                      class="btn btn-sm w-50 py-1 fs-9 fw-bold" 
                      :class="pricingMode === 'per_unit' ? 'btn-white shadow-sm text-primary' : 'text-gray-500'"
                      @click="setPricingMode('per_unit')">
                      Per Satuan
                    </button>
                    <button type="button" 
                      class="btn btn-sm w-50 py-1 fs-9 fw-bold" 
                      :class="pricingMode === 'total' ? 'btn-white shadow-sm text-primary' : 'text-gray-500'"
                      @click="setPricingMode('total')">
                      Total
                    </button>
                  </div>

                  <!-- Input Harga Per Satuan -->
                  <div v-if="pricingMode === 'per_unit'">
                      <div class="input-group input-group-solid">
                        <span class="input-group-text border-0 text-gray-600 fw-bold">Rp</span>
                        <input v-model="formData.cost_price" type="number" step="0.01" class="form-control form-control-solid ps-2" placeholder="0" required />
                      </div>
                      <div class="form-text fs-9 mt-1 text-end text-gray-500">Harga per {{ formData.unit || 'satuan' }}</div>
                  </div>

                  <!-- Input Harga Total -->
                  <div v-if="pricingMode === 'total'">
                      <div class="input-group input-group-solid">
                        <span class="input-group-text border-0 text-gray-600 fw-bold">Rp</span>
                        <input v-model="totalPriceInput" type="number" step="100" class="form-control form-control-solid ps-2" placeholder="0" @input="calculateCostPrice" />
                      </div>
                      <div class="form-text fs-9 mt-1 text-end text-primary fw-bold" v-if="Number(formData.cost_price) > 0">
                        = {{ formatCurrency(Number(formData.cost_price)) }} / {{ formData.unit || 'unit' }}
                      </div>
                  </div>
                </div>
              </div>

              <!-- Supplier & Status -->
              <div class="row g-5 mb-4">
                <div class="col-md-8">
                  <label class="fw-bold fs-7 text-gray-700 mb-2">Supplier</label>
                  <input v-model="formData.supplier" type="text" class="form-control form-control-solid" placeholder="Nama supplier" />
                </div>
                <div class="col-md-4">
                  <label class="fw-bold fs-7 text-gray-700 mb-2">Status Barang</label>
                  <div class="form-check form-switch form-check-custom form-check-solid mt-2">
                    <input v-model="formData.is_active" class="form-check-input h-25px w-45px" type="checkbox" id="activeSwitch" />
                    <label class="form-check-label fw-bold ms-2 text-gray-700" for="activeSwitch">
                      {{ formData.is_active ? 'Aktif' : 'Nonaktif' }}
                    </label>
                  </div>
                </div>
              </div>

              <div class="mb-4">
                <label class="fw-bold fs-7 text-gray-700 mb-2">Catatan Tambahan</label>
                <textarea v-model="formData.notes" class="form-control form-control-solid" rows="2" placeholder="Keterangan lain..."></textarea>
              </div>

              <div class="d-flex justify-content-end align-items-center mt-5 pt-3 border-top border-gray-200">
                <button type="button" class="btn btn-light me-3 fw-bold text-gray-700 px-5" data-bs-dismiss="modal">Batal</button>
                <button :disabled="formLoading" class="btn btn-primary fw-bold px-6 shadow-sm hover-elevate" type="submit">
                  <span v-if="!formLoading" class="d-flex align-items-center">
                    <i class="ki-duotone ki-check fs-2 me-2"></i> Simpan
                  </span>
                  <span v-if="formLoading" class="indicator-progress d-flex align-items-center">
                    <span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...
                  </span>
                </button>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- 5. MODAL KELOLA KATEGORI (TETAP SAMA) -->
    <div class="modal fade" id="kt_modal_category_settings" ref="categoryModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content rounded-3 shadow-lg border-0 theme-modal">
          <div class="modal-header border-0 pb-0 pt-4 px-5">
            <h2 class="fw-bold m-0 fs-3 text-gray-900">Kelola Kategori & Satuan</h2>
            <div class="btn btn-sm btn-icon btn-active-light-primary rounded-circle" data-bs-dismiss="modal">
              <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
            </div>
          </div>
          <div class="modal-body scroll-y px-5 pb-5 pt-2">
            
            <div class="alert alert-dismissible bg-light-primary border border-primary border-dashed d-flex flex-column flex-sm-row w-100 p-5 mb-5 rounded-3">
                <i class="ki-duotone ki-setting-2 fs-2hx text-primary me-4 mb-5 mb-sm-0"><span class="path1"></span><span class="path2"></span></i>
                <div class="d-flex flex-column pe-0 pe-sm-10">
                    <h5 class="mb-1 text-primary">Konfigurasi Satuan</h5>
                    <span class="fs-7 text-gray-700">Atur satuan untuk setiap kategori. Anda bisa menentukan apakah satuan tersebut menggunakan desimal (cth: kg) atau bilangan bulat (cth: pcs).</span>
                </div>
            </div>

            <!-- List Kategori yang Ada -->
            <div class="mb-5">
              <div v-for="(cat, index) in categorySettings" :key="index" class="bg-light rounded p-4 mb-3 border border-gray-300">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div class="w-100 me-3">
                    <label class="fs-9 fw-bold text-gray-500 text-uppercase mb-1">Nama Kategori</label>
                    <input type="text" v-model="cat.name" class="form-control form-control-sm form-control-solid fw-bold text-gray-800" placeholder="Nama Kategori">
                  </div>
                  <button @click="cat.id ? deleteCategoryDb(cat.id) : removeCategorySetting(index)" class="btn btn-sm btn-icon btn-light-danger h-30px w-30px mt-4" tabindex="-1">
                    <i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                  </button>
                </div>

                <!-- Input Satuan Baru (List Builder) -->
                <div class="bg-white rounded p-3 border border-dashed border-gray-300">
                  <label class="fs-9 fw-bold text-gray-500 text-uppercase mb-2">Tambah Satuan</label>
                  <div class="d-flex gap-2 mb-3">
                      <input type="text" v-model="cat.newUnitName" class="form-control form-control-sm form-control-solid" placeholder="Nama (cth: kg)" @keyup.enter="addUnitToCategory(index)">
                      <select v-model="cat.newUnitPrecision" class="form-select form-select-sm form-select-solid w-150px" title="Tipe Angka">
                          <option :value="0">Bulat (1)</option>
                          <option :value="2">Desimal (1.00)</option>
                      </select>
                      <button @click="addUnitToCategory(index)" class="btn btn-sm btn-light-primary btn-icon w-35px h-35px">
                          <i class="ki-duotone ki-plus fs-2"></i>
                      </button>
                  </div>

                  <!-- List Satuan -->
                  <div class="d-flex flex-wrap gap-2">
                    <div v-for="(unit, uIdx) in cat.units" :key="uIdx" class="badge badge-lg badge-light-primary d-flex align-items-center gap-2 px-3 py-2">
                        <span class="fw-bold">{{ unit.name }}</span>
                        <span class="text-gray-500 fs-9 border-start border-gray-300 ps-2" data-bs-toggle="tooltip" title="Format Angka">
                            {{ unit.precision === 0 ? '123' : '123.45' }}
                        </span>
                        <i @click="removeUnitFromCategory(index, uIdx)" class="ki-duotone ki-cross fs-5 text-hover-danger ms-1 cursor-pointer"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <span v-if="cat.units.length === 0" class="text-gray-400 fs-8 fst-italic py-1">Belum ada satuan</span>
                  </div>
                </div>

              </div>

              <!-- Button Tambah Kategori -->
              <button @click="addCategorySetting" class="btn btn-sm btn-dashed btn-outline btn-outline-dashed btn-outline-primary w-100 py-3 hover-elevate">
                <i class="ki-duotone ki-plus fs-3"></i> Tambah Kategori Baru
              </button>
            </div>

            <div class="d-flex justify-content-end border-top pt-4">
               <button type="button" class="btn btn-sm btn-light me-3" data-bs-dismiss="modal">Tutup</button>
               <button @click="saveCategories" class="btn btn-primary btn-sm fw-bold px-6">
                 <span v-if="!settingsLoading">Simpan Perubahan</span>
                 <span v-if="settingsLoading" class="indicator-progress d-flex align-items-center">
                    <span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...
                 </span>
               </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 6. MODAL ADJUST STOCK (UPDATED: TOMBOL AKSI DENGAN ICON) -->
    <div class="modal fade" id="kt_modal_stock_adjust" ref="stockModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content rounded-3 shadow-lg border-0 theme-modal">
          
          <div class="modal-header border-0 pb-0 justify-content-between align-items-center py-4 px-5">
            <h2 class="fw-bold m-0 fs-3 text-gray-900">Atur Stok</h2>
            <div class="btn btn-sm btn-icon btn-active-light-primary rounded-circle" data-bs-dismiss="modal">
              <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
            </div>
          </div>

          <div class="modal-body scroll-y px-5 pb-5 pt-2">
            <div v-if="selectedItem" class="alert alert-info d-flex align-items-center p-4 mb-4 border-dashed border-info bg-light-info rounded">
               <i class="ki-duotone ki-information-5 fs-2hx text-info me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
               <div class="d-flex flex-column">
                  <span class="fw-bold text-gray-800">{{ selectedItem.name }} ({{ selectedItem.code }})</span>
                  <span class="text-gray-600 fs-7">
                      Stok saat ini: 
                      <span class="fw-bold text-dark">
                          {{ formatQty(selectedItem.current_stock, selectedItem.unit, selectedItem.category) }} {{ selectedItem.unit }}
                      </span>
                  </span>
               </div>
            </div>

            <form @submit.prevent="submitStockAdjust" class="compact-form">
              <div class="mb-4">
                <label class="required fw-bold fs-7 text-gray-700 mb-3 d-block">Pilih Tindakan</label>
                
                <!-- TOMBOL PILIHAN AKSI STOK (ICON + CARD STYLE) -->
                <div class="row g-3" data-kt-buttons="true">
                  <!-- Tambah -->
                  <div class="col">
                    <input type="radio" class="btn-check" name="stock_type" value="increment" id="stock_inc" v-model="stockForm.type" @change="onStockTypeChange">
                    <label class="btn btn-outline btn-outline-dashed btn-active-light-success d-flex flex-column align-items-center p-3 h-100" for="stock_inc">
                      <i class="ki-duotone ki-arrow-up fs-2hx mb-2 text-success"><span class="path1"></span><span class="path2"></span></i>
                      <span class="fw-bold fs-8">Tambah (+)</span>
                    </label>
                  </div>
                  <!-- Kurang -->
                  <div class="col">
                    <input type="radio" class="btn-check" name="stock_type" value="decrement" id="stock_dec" v-model="stockForm.type" @change="onStockTypeChange">
                    <label class="btn btn-outline btn-outline-dashed btn-active-light-danger d-flex flex-column align-items-center p-3 h-100" for="stock_dec">
                      <i class="ki-duotone ki-arrow-down fs-2hx mb-2 text-danger"><span class="path1"></span><span class="path2"></span></i>
                      <span class="fw-bold fs-8">Kurang (-)</span>
                    </label>
                  </div>
                  <!-- Reset -->
                  <div class="col">
                    <input type="radio" class="btn-check" name="stock_type" value="set" id="stock_set" v-model="stockForm.type" @change="onStockTypeChange">
                    <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex flex-column align-items-center p-3 h-100" for="stock_set">
                      <i class="ki-duotone ki-arrows-circle fs-2hx mb-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                      <span class="fw-bold fs-8">Koreksi (=)</span>
                    </label>
                  </div>
                </div>
              </div>

              <div class="mb-4">
                <label class="required fw-bold fs-7 text-gray-700 mb-2">Jumlah</label>
                <div class="input-group input-group-solid">
                    <!-- Step dinamis saat adjust stok juga -->
                    <input v-model="stockForm.quantity" type="number" :step="selectedItemPrecision === 0 ? '1' : '0.01'" class="form-control form-control-solid" :placeholder="stockPlaceholder" required />
                    <span class="input-group-text fs-8 text-gray-500" v-if="selectedItem">{{ selectedItem.unit }}</span>
                </div>
                <div v-if="selectedItem && stockForm.quantity" class="text-gray-600 fs-8 mt-2 ms-1">
                  <i class="ki-duotone ki-arrow-right fs-8 me-1"></i>
                  <span v-if="stockForm.type === 'increment'">Estimasi akhir: <strong>{{ formatQty((selectedItem.current_stock + (parseFloat(String(stockForm.quantity)) || 0)), selectedItem.unit, selectedItem.category) }} {{ selectedItem.unit }}</strong></span>
                  <span v-else-if="stockForm.type === 'decrement'">Estimasi akhir: <strong>{{ formatQty(Math.max(0, (selectedItem.current_stock - (parseFloat(String(stockForm.quantity)) || 0))), selectedItem.unit, selectedItem.category) }} {{ selectedItem.unit }}</strong></span>
                  <span v-else>Stok akan menjadi: <strong>{{ formatQty((parseFloat(String(stockForm.quantity)) || 0), selectedItem.unit, selectedItem.category) }} {{ selectedItem.unit }}</strong></span>
                </div>
              </div>

              <div class="mb-4">
                <label class="fw-bold fs-7 text-gray-700 mb-2">Catatan</label>
                <textarea v-model="stockForm.notes" class="form-control form-control-solid" rows="3" placeholder="Catatan perubahan stok (wajib diisi)..." required></textarea>
              </div>

              <div class="d-flex justify-content-end align-items-center mt-5 pt-3 border-top border-gray-200">
                <button type="button" class="btn btn-light me-3 fw-bold text-gray-700 px-5" data-bs-dismiss="modal">Batal</button>
                <button :disabled="stockFormLoading" class="btn btn-primary fw-bold px-6 shadow-sm hover-elevate" type="submit">
                  <span v-if="!stockFormLoading" class="d-flex align-items-center">
                    <i class="ki-duotone ki-check fs-2 me-1"></i> Simpan
                  </span>
                  <span v-if="stockFormLoading" class="indicator-progress d-flex align-items-center">
                    <span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...
                  </span>
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
import { ref, computed, onMounted, watch } from "vue";
import { debounce } from "lodash-es";
import axios from "@/libs/axios";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";

// --- INTERFACES ---
interface WarehouseItem {
  id: number;
  code: string;
  name: string;
  category: string;
  unit: string;
  min_stock: number;
  current_stock: number;
  cost_price: number;
  supplier: string | null;
  notes: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

interface UnitConfig {
  name: string;
  precision: number;
}

interface CategoryOption {
  id?: number;
  value: string;
  label: string;
  units: UnitConfig[];
}

interface CategorySetting {
  id: number | null;
  name: string;
  units: UnitConfig[];
  newUnitName: string;
  newUnitPrecision: number;
}

// --- STATE / REFS ---
const items = ref<WarehouseItem[]>([]);
const loading = ref(true);
const selectedItem = ref<WarehouseItem | null>(null);
const searchQuery = ref("");
const isAutoCode = ref(false);
const pricingMode = ref<'per_unit' | 'total'>('per_unit'); // Mode Harga
const totalPriceInput = ref<string | number>(''); // Input sementara untuk harga total

const categories = ref<CategoryOption[]>([]);
const categorySettings = ref<CategorySetting[]>([]);

const warehouseModalRef = ref<HTMLElement>();
const stockModalRef = ref<HTMLElement>();
const categoryModalRef = ref<HTMLElement>();

const filters = ref({
  category: 'all',
  status: 'all',
  sort_by: 'created_at',
  sort_order: 'desc'
});

const isDropdownOpen = ref({ category: false, status: false });

const meta = ref({
  total: 0,
  per_page: 12,
  current_page: 1,
  last_page: 1
});

const stats = ref({
  totalItems: 0,
  stockValue: 0,
  lowStockItems: 0,
  activeItems: 0
});

const formData = ref({
  id: null as number | null,
  code: '',
  name: '',
  category: '',
  unit: '', 
  min_stock: '' as string | number,
  current_stock: '' as string | number,
  cost_price: '' as string | number,
  supplier: '',
  notes: '',
  is_active: true
});

const stockForm = ref({
  type: 'increment',
  quantity: '' as string | number,
  notes: ''
});

const formLoading = ref(false);
const stockFormLoading = ref(false);
const settingsLoading = ref(false);

// --- COMPUTED ---
const isEditMode = computed(() => !!selectedItem.value?.id);

const availableUnits = computed(() => {
    const selectedCat = categories.value.find(c => c.value === formData.value.category);
    return selectedCat ? selectedCat.units : [];
});

const selectedUnitPrecision = computed(() => {
    const unitObj = availableUnits.value.find(u => u.name === formData.value.unit);
    return unitObj ? unitObj.precision : null;
});

const selectedItemPrecision = computed(() => {
    if(!selectedItem.value) return 2;
    const cat = categories.value.find(c => c.value === selectedItem.value?.category);
    if(cat) {
        const unit = cat.units.find(u => u.name === selectedItem.value?.unit);
        if(unit) return unit.precision;
    }
    return 2;
});

const activeCategoryLabel = computed(() => {
  if (filters.value.category === 'all') return 'Semua Kategori';
  const cat = categories.value.find(c => c.value === filters.value.category);
  return cat ? cat.label : filters.value.category;
});

const paginationPages = computed<(number | string)[]>(() => {
  const pages: (number | string)[] = [];
  const current = meta.value.current_page;
  const last = meta.value.last_page;
  if (last <= 5) {
    for (let i = 1; i <= last; i++) pages.push(i);
  } else {
    if (current <= 3) pages.push(1, 2, 3, '...', last);
    else if (current >= last - 2) pages.push(1, '...', last - 2, last - 1, last);
    else pages.push(1, '...', current - 1, current, current + 1, '...', last);
  }
  return pages;
});

const stockPlaceholder = computed(() => {
  switch (stockForm.value.type) {
    case 'increment': return 'Jumlah yang ditambahkan';
    case 'decrement': return 'Jumlah yang dikurangi';
    case 'set': return 'Masukkan stok nyata saat ini';
    default: return 'Jumlah';
  }
});

// --- METHODS BARU (HARGA & STOK) ---

// Mengubah mode harga (Per Satuan vs Total)
const setPricingMode = (mode: 'per_unit' | 'total') => {
    pricingMode.value = mode;
    // Reset nilai visual total jika pindah ke mode total
    if(mode === 'total' && Number(formData.value.current_stock) > 0 && Number(formData.value.cost_price) > 0) {
        totalPriceInput.value = (parseFloat(String(formData.value.current_stock)) * parseFloat(String(formData.value.cost_price))).toFixed(2);
    } else {
        totalPriceInput.value = '';
    }
};

// Menghitung harga satuan otomatis jika dalam mode "Harga Total"
const calculateCostPrice = () => {
    if (pricingMode.value === 'total') {
        const stock = parseFloat(String(formData.value.current_stock));
        const total = parseFloat(String(totalPriceInput.value));
        
        if (stock > 0 && total >= 0) {
            // Rumus: Harga Beli Satuan = Harga Total / Stok Awal
            formData.value.cost_price = total / stock;
        } else {
            formData.value.cost_price = 0;
        }
    }
};

// --- METHODS EXIST ---
const formatQty = (val: number, unitName: string, categoryName: string) => {
    if (val === undefined || val === null) return "0";
    let precision = 2;
    const cat = categories.value.find(c => c.value === categoryName);
    if(cat) {
        const unit = cat.units.find(u => u.name === unitName);
        if(unit) precision = unit.precision;
    } else {
        const integerUnits = ['pcs', 'unit', 'set', 'buah', 'box', 'pak'];
        if(integerUnits.includes(unitName.toLowerCase())) precision = 0;
    }
    return new Intl.NumberFormat("id-ID", { 
        minimumFractionDigits: precision, 
        maximumFractionDigits: precision 
    }).format(val);
};

const fetchCategories = async () => {
  try {
    const res = await axios.get('/warehouse/items/categories');
    if(res.data.success) {
      categories.value = res.data.data.map((cat: any) => {
          let unitsFormatted: UnitConfig[] = [];
          if(Array.isArray(cat.units)) {
              if(cat.units.length > 0 && typeof cat.units[0] === 'string') {
                  unitsFormatted = cat.units.map((u: string) => {
                      const isInt = ['pcs', 'unit', 'set', 'box'].includes(u.toLowerCase());
                      return { name: u, precision: isInt ? 0 : 2 };
                  });
              } else {
                  unitsFormatted = cat.units;
              }
          }
          return {
              ...cat,
              units: unitsFormatted
          };
      });
    }
  } catch (e) { console.error("Gagal load kategori", e); }
};

const getNextCode = async () => {
    try {
        const res = await axios.get('/warehouse/items/next-code');
        formData.value.code = res.data.code;
        isAutoCode.value = true;
    } catch (e) {
        console.error('Gagal auto generate code');
    }
};

const getItems = async () => {
  loading.value = true;
  try {
    const params: any = {
      page: meta.value.current_page,
      per_page: meta.value.per_page,
      search: searchQuery.value,
      sort_by: filters.value.sort_by,
      sort_order: filters.value.sort_order
    };
    if (filters.value.category !== 'all') params.category = filters.value.category;
    if (filters.value.status !== 'all') params.status = filters.value.status;
    
    const response = await axios.get('/warehouse/items', { params });
    if (response.data.success) {
      items.value = response.data.data;
      meta.value = response.data.meta;
    }
    getStats();
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const getStats = async () => {
    try {
        const res = await axios.get('/warehouse/items/stock-value');
        if (res.data.success) {
            stats.value.stockValue = res.data.data.total_value;
            stats.value.totalItems = res.data.data.total_items;
            stats.value.lowStockItems = res.data.data.low_stock_items;
        }
    } catch(e) {}
};

const openAddModal = async () => {
  selectedItem.value = null;
  resetForm();
  pricingMode.value = 'per_unit'; // Default ke harga satuan
  totalPriceInput.value = '';
  
  if (warehouseModalRef.value) {
    new Modal(warehouseModalRef.value).show();
  }
  
  await getNextCode();
};

const openEditModal = (item: WarehouseItem) => {
  selectedItem.value = { ...item };
  formData.value = {
    id: item.id,
    code: item.code,
    name: item.name,
    category: item.category,
    unit: item.unit,
    min_stock: item.min_stock,
    current_stock: item.current_stock,
    cost_price: item.cost_price,
    supplier: item.supplier || '',
    notes: item.notes || '',
    is_active: item.is_active
  };
  pricingMode.value = 'per_unit'; // Reset mode saat edit
  totalPriceInput.value = '';
  isAutoCode.value = false;
  
  if (warehouseModalRef.value) {
    new Modal(warehouseModalRef.value).show();
  }
};

const onCategoryChange = () => {
    formData.value.unit = '';
};

const submitItem = async () => {
    formLoading.value = true;
    try {
        const payload = { ...formData.value };
        payload.min_stock = parseFloat(String(payload.min_stock)) || 0;
        payload.current_stock = parseFloat(String(payload.current_stock)) || 0;
        payload.cost_price = parseFloat(String(payload.cost_price)) || 0;

        if (isEditMode.value && formData.value.id) {
            await axios.put(`/warehouse/items/${formData.value.id}`, payload);
            Swal.fire({ title: 'Berhasil!', text: 'Data barang diperbarui', icon: 'success', timer: 1500, showConfirmButton: false });
        } else {
            await axios.post('/warehouse/items', payload);
            Swal.fire({ title: 'Berhasil!', text: 'Barang baru ditambahkan', icon: 'success', timer: 1500, showConfirmButton: false });
        }
        
        const modal = Modal.getInstance(warehouseModalRef.value!);
        modal?.hide();
        
        getItems();
        fetchCategories();
    } catch (error: any) {
        Swal.fire('Error', error.response?.data?.message || 'Terjadi kesalahan', 'error');
    } finally {
        formLoading.value = false;
    }
};

const deleteItem = (id: number) => {
  Swal.fire({
    title: 'Hapus Barang?',
    text: "Data yang dihapus tidak dapat dikembalikan",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    customClass: { confirmButton: 'btn fw-bold btn-danger', cancelButton: 'btn fw-bold btn-light' }
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await axios.delete(`/warehouse/items/${id}`);
        Swal.fire({ title: 'Terhapus!', text: 'Barang berhasil dihapus', icon: 'success', timer: 1500, showConfirmButton: false });
        getItems();
      } catch (error: any) {
        Swal.fire('Error', error.response?.data?.message || 'Gagal menghapus', 'error');
      }
    }
  });
};

const openStockModal = (item: WarehouseItem) => {
  selectedItem.value = { ...item };
  stockForm.value = { type: 'increment', quantity: '', notes: '' };
  if (stockModalRef.value) new Modal(stockModalRef.value).show();
};

const onStockTypeChange = () => {
    stockForm.value.quantity = '';
};

const submitStockAdjust = async () => {
  if (!selectedItem.value) return;
  stockFormLoading.value = true;
  try {
    const qty = parseFloat(String(stockForm.value.quantity));
    if (isNaN(qty) || qty <= 0) {
      Swal.fire('Error', 'Jumlah harus lebih dari 0', 'error');
      return;
    }
    await axios.post(`/warehouse/items/${selectedItem.value.id}/adjust-stock`, {
      quantity: qty,
      type: stockForm.value.type,
      notes: stockForm.value.notes
    });
    
    Swal.fire({ title: 'Stok Disesuaikan!', icon: 'success', timer: 1500, showConfirmButton: false });
    const modal = Modal.getInstance(stockModalRef.value!);
    modal?.hide();
    getItems();
  } catch (error: any) {
    Swal.fire('Error', error.response?.data?.message || 'Gagal', 'error');
  } finally {
    stockFormLoading.value = false;
  }
};

const openCategoryModal = async () => {
    if (categoryModalRef.value) {
        new Modal(categoryModalRef.value).show();
    }
    
    categorySettings.value = categories.value.map(c => ({
        id: c.id || null, 
        name: c.label,
        units: JSON.parse(JSON.stringify(c.units)), 
        newUnitName: '',
        newUnitPrecision: 0 
    }));
};

const addCategorySetting = () => {
    categorySettings.value.push({
        id: null,
        name: '',
        units: [],
        newUnitName: '',
        newUnitPrecision: 0
    });
};

const removeCategorySetting = (index: number) => {
    categorySettings.value.splice(index, 1);
};

const addUnitToCategory = (catIndex: number) => {
    const cat = categorySettings.value[catIndex];
    if(!cat.newUnitName.trim()) return;

    if(cat.units.some(u => u.name.toLowerCase() === cat.newUnitName.toLowerCase())) {
        Swal.fire('Info', 'Satuan sudah ada', 'info');
        return;
    }

    cat.units.push({
        name: cat.newUnitName.trim(),
        precision: cat.newUnitPrecision
    });

    cat.newUnitName = '';
    cat.newUnitPrecision = 0;
};

const removeUnitFromCategory = (catIndex: number, unitIndex: number) => {
    categorySettings.value[catIndex].units.splice(unitIndex, 1);
};


const deleteCategoryDb = async (id: number) => {
    const res = await Swal.fire({
        title: 'Hapus Kategori?',
        text: "Kategori akan dihapus dari pilihan.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus'
    });
    
    if (res.isConfirmed) {
        try {
            await axios.delete(`/warehouse/categories/${id}`);
            const idx = categorySettings.value.findIndex(c => c.id === id);
            if(idx !== -1) categorySettings.value.splice(idx, 1);
            fetchCategories();
        } catch (e) {
            Swal.fire('Gagal', 'Gagal menghapus kategori', 'error');
        }
    }
};

const saveCategories = async () => {
    settingsLoading.value = true;
    try {
        for (const cat of categorySettings.value) {
            if (!cat.name) continue;
            
            const payload = { name: cat.name, units: cat.units };
            
            if (cat.id) {
                await axios.put(`/warehouse/categories/${cat.id}`, payload);
            } else {
                await axios.post(`/warehouse/categories`, payload);
            }
        }
        
        Swal.fire({ title: 'Tersimpan', icon: 'success', timer: 1500, showConfirmButton: false });
        const modal = Modal.getInstance(categoryModalRef.value!);
        modal?.hide();
        fetchCategories();
    } catch (e) {
        Swal.fire('Error', 'Gagal menyimpan pengaturan', 'error');
    } finally {
        settingsLoading.value = false;
    }
};

const resetForm = () => {
    formData.value = {
        id: null, code: '', name: '', category: '', unit: '',
        min_stock: '', current_stock: '', cost_price: '',
        supplier: '', notes: '', is_active: true
    };
};

const debouncedSearch = debounce(() => {
  meta.value.current_page = 1;
  getItems();
}, 500);

const toggleDropdown = (type: 'category' | 'status') => {
  isDropdownOpen.value[type] = !isDropdownOpen.value[type];
};

const closeDropdown = (type: 'category' | 'status') => {
  isDropdownOpen.value[type] = false;
};

const setFilter = (key: 'category' | 'status', value: string) => {
  // @ts-ignore
  filters.value[key] = value;
  meta.value.current_page = 1;
  getItems();
  closeDropdown(key);
};

const changePage = (page: number | string) => {
  if (typeof page === 'number' && page >= 1 && page <= meta.value.last_page) {
    meta.value.current_page = page;
    getItems();
  }
};

const formatCurrency = (value: number) => {
  if (!value || isNaN(value)) return "Rp 0";
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
};

const getStockPercentage = (item: WarehouseItem) => {
  const max = Math.max(item.min_stock * 3, 10);
  return Math.min((item.current_stock / max) * 100, 100);
};

const getStockColor = (item: WarehouseItem) => {
  if (item.current_stock <= item.min_stock) return 'bg-danger';
  if (item.current_stock <= item.min_stock * 2) return 'bg-warning';
  return 'bg-success';
};

const getStockTextColor = (item: WarehouseItem) => {
  if (item.current_stock <= item.min_stock) return 'text-danger';
  if (item.current_stock <= item.min_stock * 2) return 'text-warning';
  return 'text-dark';
};

const getCategoryBadgeClass = (category: string) => {
    const colors = ['badge-light-primary text-primary', 'badge-light-success text-success', 'badge-light-info text-info', 'badge-light-warning text-warning', 'badge-light-dark text-dark'];
    return colors[category.length % colors.length];
};

onMounted(() => {
  getItems();
  fetchCategories();
});

watch(filters, () => {
  meta.value.current_page = 1;
  getItems();
}, { deep: true });

const vClickOutside = {
  mounted(el: HTMLElement, binding: any) {
    (el as any).clickOutsideEvent = function(event: Event) { 
      if (!(el === event.target || el.contains(event.target as Node))) {
        binding.value(event, el);
      }
    };
    document.body.addEventListener('click', (el as any).clickOutsideEvent);
  },
  unmounted(el: HTMLElement) {
    document.body.removeEventListener('click', (el as any).clickOutsideEvent);
  },
};
</script>

<style scoped>
.text-primary { color: #009EF7 !important; }
.bg-light-primary { background-color: #F1FAFF !important; }
.btn-primary { background-color: #009EF7; color: white; border: none; }
.btn-primary:hover { background-color: #0085d4; }
.box-shadow-primary { box-shadow: 0 4px 12px rgba(0, 158, 247, 0.3); }

/* Table Adjustments */
.table > :not(caption) > * > * { padding: 1rem 1.25rem; border-bottom-width: 1px; vertical-align: middle; }
.table-hover tbody tr:hover { background-color: rgba(0, 158, 247, 0.02); transition: background-color 0.2s; }
thead th { letter-spacing: 0.05em; color: #7e8299 !important; }

/* Custom Dropdown */
.dropdown-wrapper { position: relative; z-index: 100; }
.btn-custom-select {
    background-color: #F9F9F9;
    border: 1px solid transparent;
    border-radius: 10px;
    height: 42px;
    transition: all 0.2s ease;
}
.btn-custom-select:hover, .btn-custom-select.active { 
  background-color: #ffffff; border-color: #009EF7; box-shadow: 0 4px 12px rgba(0,0,0,0.05); color: #009EF7; 
}
.custom-dropdown-menu {
    position: absolute; top: 110%; left: 0; width: 100%;
    background: white; z-index: 99999 !important;
    border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}
.dropdown-item-custom { 
  display: block; text-decoration: none; color: #5E6278; 
  transition: all 0.2s ease; cursor: pointer; 
}
.dropdown-item-custom:hover { background-color: #F5F8FA; color: #009EF7; padding-left: 1.25rem !important; }
.dropdown-item-custom.selected { background-color: #E8F4FF; color: #009EF7; font-weight: 700; }

/* Transitions */
.animate-item { animation: fadeInUp 0.5s ease backwards; animation-delay: var(--delay); }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.dropdown-anim-enter-active, .dropdown-anim-leave-active { transition: all 0.2s ease; }
.dropdown-anim-enter-from, .dropdown-anim-leave-to { opacity: 0; transform: translateY(-10px); }

/* Dark Mode Support */
[data-bs-theme="dark"] .theme-card { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .text-gray-800 { color: #ffffff !important; }
[data-bs-theme="dark"] .btn-custom-select { background-color: #1b1b29; border-color: #323248; color: #CDCDDE; }
[data-bs-theme="dark"] .custom-dropdown-menu { background-color: #1e1e2d; border-color: #323248; }
[data-bs-theme="dark"] .dropdown-item-custom { color: #9A9CAE; }
[data-bs-theme="dark"] .dropdown-item-custom:hover { background-color: #2b2b40; color: #009EF7; }
</style>