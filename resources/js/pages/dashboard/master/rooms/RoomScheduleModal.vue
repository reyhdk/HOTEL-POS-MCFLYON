<template>
  <div class="modal fade" id="kt_modal_room_schedule" ref="modalRef" tabindex="-1" aria-hidden="true">
    <!-- Ukuran modal dikurangi dari mw-1000px ke mw-800px -->
    <div class="modal-dialog modal-dialog-centered mw-800px">
      <div class="modal-content rounded-3 border-0 shadow-lg theme-modal overflow-hidden">
        
        <div class="modal-header border-0 pb-0 bg-gradient-orange position-relative overflow-hidden">
          <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10">
            <div class="pattern-dots"></div>
          </div>
          
          <!-- Padding header dikurangi dari py-6 ke py-4 -->
          <div class="w-100 position-relative z-index-1 py-4 px-5">
            <div class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-3">
                <!-- Ukuran simbol dikurangi dari 60px ke 45px -->
                <div class="symbol symbol-45px symbol-circle bg-white bg-opacity-20 shadow-sm">
                  <i class="ki-duotone ki-calendar-tick fs-2x text-white">
                    <span class="path1"></span><span class="path2"></span>
                    <span class="path3"></span><span class="path4"></span>
                  </i>
                </div>
                <div>
                  <!-- Font size dikurangi dari fs-1 ke fs-3 -->
                  <h2 class="text-white fw-bolder mb-0 fs-3">
                    Kamar <span class="text-warning">#{{ displayRoomNumber }}</span>
                  </h2>
                  <div class="d-flex align-items-center gap-2 mt-0">
                    <span class="badge badge-light-warning fs-9 fw-bold px-2 py-1">
                      {{ getRoomType }}
                    </span>
                    <span class="text-white text-opacity-75 fs-9 fw-medium ms-1">Jadwal & Histori</span>
                  </div>
                </div>
              </div>
              
              <button 
                type="button" 
                class="btn btn-icon btn-sm btn-active-light-primary rounded-circle" 
                data-bs-dismiss="modal"
                style="background: rgba(255,255,255,0.2);">
                <i class="ki-duotone ki-cross fs-2 text-white">
                  <span class="path1"></span><span class="path2"></span>
                </i>
              </button>
            </div>
          </div>
        </div>

        <div class="modal-body p-0">
          <div class="row g-0">
            
            <!-- Padding body dikurangi dari p-9 ke p-6 -->
            <div class="col-lg-7 p-6 border-end border-gray-200 bg-white">
              <div class="d-flex justify-content-between align-items-center mb-6">
                <button 
                  class="btn btn-icon btn-sm btn-light-orange rounded-circle shadow-xs w-30px h-30px" 
                  @click="changeMonth(-1)" 
                  :disabled="loading">
                  <i class="ki-duotone ki-left fs-3"><span class="path1"></span><span class="path2"></span></i>
                </button>
                
                <div class="text-center">
                  <!-- Font size bulan dikurangi dari fs-2 ke fs-5 -->
                  <h3 class="fw-bolder m-0 text-gray-900 fs-5">{{ currentMonthName }} {{ currentYear }}</h3>
                  <div class="text-muted fs-10 fw-bold text-uppercase ls-1">Pilih Tanggal</div>
                </div>
                
                <button 
                  class="btn btn-icon btn-sm btn-light-orange rounded-circle shadow-xs w-30px h-30px" 
                  @click="changeMonth(1)" 
                  :disabled="loading">
                  <i class="ki-duotone ki-right fs-3"><span class="path1"></span><span class="path2"></span></i>
                </button>
              </div>

              <div class="calendar-container position-relative px-1">
                <transition name="fade">
                  <div v-if="loading" class="loading-overlay rounded-3">
                    <span class="spinner-border text-orange spinner-border-sm"></span>
                  </div>
                </transition>

                <div class="calendar-grid">
                  <div class="day-header" v-for="day in daysOfWeek" :key="day">{{ day }}</div>
                  <div v-for="n in paddingDays" :key="'pad-'+n" class="calendar-day empty"></div>
                  <div 
                    v-for="date in daysInMonth" 
                    :key="date" 
                    class="calendar-day"
                    :class="[getDayStatusClass(date), { 'is-selected': isSelected(date) }]"
                    @click="selectDate(date)">
                    <span class="day-number">{{ date }}</span>
                    <div v-if="hasBooking(date)" class="status-indicator"></div>
                  </div>
                </div>
              </div>
              
              <!-- Legend lebih ringkas -->
              <div class="legend-container mt-6 p-4 rounded-3 bg-light-subtle border border-gray-100">
                <div class="d-flex flex-wrap justify-content-center gap-4 text-center">
                  <div class="d-flex align-items-center gap-2">
                    <span class="legend-pill bg-occupied"></span>
                    <span class="fs-9 fw-bold text-gray-700">Terisi</span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="legend-pill bg-reserved"></span>
                    <span class="fs-9 fw-bold text-gray-700">Booking</span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="legend-pill bg-empty"></span>
                    <span class="fs-9 fw-bold text-gray-700">Kosong</span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="legend-pill bg-today-border"></span>
                    <span class="fs-9 fw-bold text-gray-700">Hari Ini</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-5 p-6 bg-light-subtle d-flex flex-column">
              <div class="detail-header mb-5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <h4 class="fw-bolder text-gray-900 m-0 fs-5">Daftar Tamu</h4>
                  <div class="symbol symbol-35px symbol-circle bg-white shadow-xs d-flex align-items-center justify-content-center">
                    <i class="ki-duotone ki-user fs-4 text-orange"><span class="path1"></span><span class="path2"></span></i>
                  </div>
                </div>
                <div class="text-muted fs-8 fw-bold border-start border-2 border-orange ps-2">{{ formattedSelectedDate }}</div>
              </div>
              
              <div v-if="filteredBookings.length === 0" class="flex-grow-1 d-flex flex-column align-items-center justify-content-center opacity-75 py-6 text-center">
                <div class="symbol symbol-80px symbol-circle bg-light mb-4">
                  <i class="ki-duotone ki-calendar-remove fs-3x text-gray-400"><span class="path1"></span><span class="path2"></span></i>
                </div>
                <h3 class="fw-bolder text-gray-600 mb-1 fs-6">Kamar Tersedia</h3>
                <p class="text-gray-400 fs-9">Tidak ada aktivitas pada tanggal ini.</p>
              </div>

              <div v-else class="scroll-y mh-350px pe-2 flex-grow-1">
                <transition-group name="list-stagger">
                  <div 
                    v-for="book in filteredBookings" 
                    :key="book.id" 
                    class="guest-card bg-white rounded-3 shadow-xs border border-gray-100 mb-3 overflow-hidden">
                    <div class="p-4">
                      <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-3">
                          <div class="symbol symbol-35px symbol-circle bg-light-orange border border-white">
                            <span class="symbol-label fw-bold text-orange fs-7">{{ getInitial(book) }}</span>
                          </div>
                          <div>
                            <div class="fw-bolder text-gray-900 fs-6 mb-0">{{ getGuestName(book) }}</div>
                            <span class="text-muted fs-10 fw-bold text-uppercase">ID-{{ book.id }}</span>
                          </div>
                        </div>
                        <span :class="getStatusBadgeClass(book.status)">{{ book.status }}</span>
                      </div>

                      <div class="p-3 bg-light rounded-2 d-flex align-items-center gap-2 border border-gray-100">
                        <div class="flex-grow-1 text-center">
                          <div class="text-muted fs-10 fw-bold mb-0">IN</div>
                          <div class="text-gray-900 fs-8 fw-bolder">{{ formatDateShort(book.check_in_date) }}</div>
                        </div>
                        <div class="h-20px w-1px bg-gray-300 opacity-50"></div>
                        <div class="flex-grow-1 text-center">
                          <div class="text-muted fs-10 fw-bold mb-0">OUT</div>
                          <div class="text-gray-900 fs-8 fw-bolder">{{ formatDateShort(book.check_out_date) }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </transition-group>
              </div>
              
              <div class="mt-4">
                 <button type="button" class="btn btn-orange w-100 fw-bold py-3 fs-7" data-bs-dismiss="modal">
                   Tutup
                 </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from "bootstrap";
import ApiService from "@/core/services/ApiService"; 
import moment from "moment";
import 'moment/locale/id';

export default {
    name: "RoomScheduleInteractive",
    data() {
        return {
            modalInstance: null,
            roomData: null,
            loading: false,
            bookings: [], 
            currentDate: new Date(),
            selectedDate: new Date(),
            daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
        };
    },
    computed: {
        currentYear() { return this.currentDate.getFullYear(); },
        currentMonth() { return this.currentDate.getMonth(); },
        currentMonthName() { return moment(this.currentDate).format('MMMM'); },
        daysInMonth() { return new Date(this.currentYear, this.currentMonth + 1, 0).getDate(); },
        paddingDays() { return new Date(this.currentYear, this.currentMonth, 1).getDay(); },
        formattedSelectedDate() { return moment(this.selectedDate).format('dddd, DD MMM YYYY'); },
        displayRoomNumber() {
            return this.roomData ? this.roomData.room_number : '';
        },
        getRoomType() {
            if (!this.roomData) return 'Tipe Kamar';
            return this.roomData.type?.name || this.roomData.type || 'Tipe Kamar';
        },
        filteredBookings() {
            const sel = moment(this.selectedDate).startOf('day');
            return this.bookings.filter(b => {
                const start = moment(b.check_in_date).startOf('day');
                const end = moment(b.check_out_date).startOf('day');
                return sel.isSameOrAfter(start) && sel.isBefore(end);
            });
        }
    },
    methods: {
        openModal(room) {
            this.roomData = room;
            this.currentDate = new Date();
            this.selectedDate = new Date();
            this.fetchSchedule();
            
            this.$nextTick(() => {
                const el = this.$refs.modalRef;
                if (el) {
                    this.modalInstance = new Modal(el);
                    this.modalInstance.show();
                }
            });
        },
        async fetchSchedule() {
            if(!this.roomData) return;
            this.loading = true;
            try {
                const { data } = await ApiService.query('/bookings', {
                    room_id: this.roomData.id,
                    status_in: 'paid,confirmed,verified,checked_in,completed,settlement' 
                });
                this.bookings = data.data || [];
            } catch (error) {   
                console.error("Gagal memuat jadwal:", error);
            } finally { this.loading = false; }
        },
        changeMonth(step) {
            this.currentDate = new Date(this.currentYear, this.currentMonth + step, 1);
        },
        selectDate(day) {
            this.selectedDate = new Date(this.currentYear, this.currentMonth, day);
        },
        isSelected(day) {
            const d = new Date(this.currentYear, this.currentMonth, day);
            return moment(d).isSame(this.selectedDate, 'day');
        },
        hasBooking(day) {
            const d = moment(new Date(this.currentYear, this.currentMonth, day)).startOf('day');
            return this.bookings.some(b => {
                const start = moment(b.check_in_date).startOf('day');
                const end = moment(b.check_out_date).startOf('day');
                return d.isSameOrAfter(start) && d.isBefore(end);
            });
        },
        getDayStatusClass(day) {
            const d = moment(new Date(this.currentYear, this.currentMonth, day)).startOf('day');
            const today = moment().startOf('day');
            
            const bookingAtDate = this.bookings.find(b => {
                const start = moment(b.check_in_date).startOf('day');
                const end = moment(b.check_out_date).startOf('day');
                return d.isSameOrAfter(start) && d.isBefore(end);
            });

            let classes = [];
            if (d.isSame(today, 'day')) classes.push('is-today');

            if (!bookingAtDate) {
                classes.push('is-empty');
            } else {
                if (bookingAtDate.status === 'checked_in') {
                    classes.push('is-occupied');
                } else if (['paid', 'confirmed', 'verified', 'settlement'].includes(bookingAtDate.status)) {
                    classes.push('is-reserved');
                } else if (bookingAtDate.status === 'completed') {
                    classes.push('is-history');
                }
            }
            return classes.join(' ');
        },
        getStatusBadgeClass(status) {
            const map = { 
                checked_in: 'badge badge-success fw-bold px-2 py-1 fs-10',
                completed: 'badge badge-light-info fw-bold px-2 py-1 fs-10',
                paid: 'badge badge-warning text-white fw-bold px-2 py-1 fs-10',
                confirmed: 'badge badge-primary fw-bold px-2 py-1 fs-10'
            };
            return map[status] || 'badge badge-light-secondary fw-bold px-2 py-1 fs-10';
        },
        formatDateShort(date) { return moment(date).format('DD MMM YYYY'); },
        getGuestName(book) {
            return book.guest ? book.guest.name : 'Tamu Luar';
        },
        getInitial(book) {
            return book.guest ? book.guest.name.charAt(0) : 'T';
        }
    }
};
</script>

<style scoped>
.text-orange { color: #F68B1E !important; }
.btn-orange { background-color: #F68B1E; color: #fff; border: none; transition: all 0.2s; }
.btn-orange:hover { background-color: #e07d1a; color: #fff; transform: translateY(-1px); }
.btn-light-orange { background-color: #FFF4E6; color: #F68B1E; border: none; }
.btn-light-orange:hover { background-color: #F68B1E; color: #fff; }
.bg-gradient-orange { background: linear-gradient(135deg, #F68B1E 0%, #FF6B35 100%); }
.pattern-dots { background-image: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 15px 15px; width: 100%; height: 100%; }

.calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; text-align: center; }
.day-header { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; color: #A1A5B7; padding: 8px 0; letter-spacing: 1px; }

.calendar-day { 
    aspect-ratio: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; 
    border-radius: 8px; font-weight: 700; font-size: 0.9rem; cursor: pointer; 
    transition: all 0.2s;
    position: relative;
}

.calendar-day.is-occupied { background-color: #E8FFF3; color: #17C653; border: 1px solid rgba(23, 198, 83, 0.1); }
.calendar-day.is-reserved { background-color: #FFF4E6; color: #F68B1E; border: 1px solid rgba(246, 139, 30, 0.1); }
.calendar-day.is-empty { background-color: #F9F9F9; color: #7E8299; border: 1px solid #F1F1F4; }
.calendar-day.is-history { background-color: #F1FAFF; color: #009EF7; }
.calendar-day.is-today { border: 1.5px dashed #17C653 !important; }
.calendar-day.is-selected { background-color: #1B1B29 !important; color: #fff !important; transform: scale(1.05); z-index: 5; border: 1.5px solid white !important; }
.calendar-day.empty { background: transparent; cursor: default; border: none; }

.status-indicator { width: 4px; height: 4px; border-radius: 50%; background: currentColor; position: absolute; bottom: 4px; opacity: 0.7; }

.legend-pill { width: 12px; height: 12px; border-radius: 4px; display: inline-block; }
.bg-occupied { background-color: #17C653; }
.bg-reserved { background-color: #F68B1E; }
.bg-empty { background-color: #E4E6EF; }
.bg-today-border { border: 1.5px dashed #17C653; background-color: #E8FFF3; }

.guest-card { transition: all 0.2s ease; }
.guest-card:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05) !important; }

.loading-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); backdrop-filter: blur(1px); z-index: 100; display: flex; align-items: center; justify-content: center; }

.scroll-y::-webkit-scrollbar { width: 4px; }
.scroll-y::-webkit-scrollbar-track { background: transparent; }
.scroll-y::-webkit-scrollbar-thumb { background: #E4E6EF; border-radius: 10px; }
</style>