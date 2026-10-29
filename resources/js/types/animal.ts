export type AnimalOptions = {
    species: Record<string, string>;
    sexes: Record<string, string>;
    statuses: Record<string, string>;
    admissionReasons: Record<string, string>;
    sterilization: Record<string, string>;
};
export type Animal = {
    id: number;
    name: string;
    registration_number: string;
    species: string;
    breed: string | null;
    sex: string;
    birth_date: string | null;
    birth_date_estimated: boolean;
    color: string | null;
    weight_kg: string | null;
    microchip: string | null;
    admitted_at: string;
    admission_reason: string;
    found_location: string | null;
    location: string | null;
    status: string;
    sterilization_status: string;
    health_notes: string | null;
    behavior_notes: string | null;
    care_notes: string | null;
    description: string | null;
    has_photo: boolean;
};
export type AnimalSummary = Pick<
    Animal,
    | 'id'
    | 'name'
    | 'registration_number'
    | 'species'
    | 'sex'
    | 'status'
    | 'location'
    | 'admitted_at'
    | 'has_photo'
>;
export type Paginated<T> = {
    data: T[];
    total: number;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
