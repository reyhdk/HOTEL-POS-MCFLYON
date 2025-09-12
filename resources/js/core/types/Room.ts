
export interface Facility {
  id: number;
  name: string;
  icon: string | null;
}

export interface CheckIn {
  id: number;
  guest_id: number;
}

export interface Room {
  id: number;
  room_number: string;
  type: string;
  status: string;
  price_per_night: number;
  description: string | null;
  image: string | null;
  image_url: string | null;
  tersedia_mulai: string | null;
  tersedia_sampai: string | null;
  facilities: Facility[];
  created_at: string;
  updated_at: string;
  check_ins: CheckIn[];
}