import GraduationList from '@/views/Admin/GraduationList.vue';
export interface loginForma {
     email: string;
    password: string;
};

export interface User {
    id: number;
    fullname: string;
    email: string;
    role: string;
    college_id: string;
    position: string | null;
  college?: College | null;
  is_active:boolean | null;
};


export interface College {
    id: number;
    polytechnic: string;
    short_name: string;

};

// ✅ Fixed positions
export interface Position {
  value: string
  label: string
}

// 1. Ensure values are unique
// export const Positions: Position[] = [
//   { value: 'admin', label: 'Admin' },
//   { value: 'scanner', label: 'Scanner' }, // Changed value to be unique
//   { value: 'record_officer', label: 'Record Officer' },
// ]
export interface EventCategory {
  value: string
  label: string
}

export const EventCategories: EventCategory[] =[
  {value: 'Graduation', label: 'Graduation'},
  {value: 'Career_Guidance_Program', label: 'Career Guidance Program'},
  {value: 'Innovation_Research_Expo', label: 'Innovation & Research Expo'}
]


export interface LaravelPagination<T> {
  current_page: number;
  data: T[];
  first_page_url: string;
  from: number | null;
  last_page: number;
  last_page_url: string;
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
}


// src/types/index.ts

export interface EventForm {
  id: number
  category: string
  event_name: string
  starting_date: string
  ending_date: string
  expected_invitation: number
  generated_at: string
  image: string | null
  is_active: number
  created_at: Date
  updated_at: Date
}

export interface GraduationList {
  id: number
  reg_no: string | null
  college_name: string | null 
  first_name: string | null
  last_name: string | null
  status: string | null
  scanned_number: string | null
  degree: string | null
}


export interface Gown {
  id: number;
  user_id: number
  reg_no: string;
  status: string;
  expected_returning_date: Date;
  receiver_id: string | null;
  returned_date: string;
  created_at: string;
  updated_at: string | null;
  notes: string | null;

}