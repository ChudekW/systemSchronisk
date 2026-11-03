import type { CareAnimal } from './care-task';
export type Adoption = {
    id: number;
    animal_id: number;
    animal: CareAnimal;
    candidate_name: string;
    email: string | null;
    phone: string | null;
    housing: string;
    candidate_notes: string | null;
    coordinator_id: number | null;
    coordinator: { id: number; name: string } | null;
    status: string;
    adopted_on: string | null;
    returned_on: string | null;
    return_reason: string | null;
    received_by: string | null;
    version: number;
    attachments: { id: number; name: string }[];
};
export type AdoptionHistory = {
    id: number;
    author_name: string;
    note: string | null;
    created_at: string;
    snapshot: Pick<
        Adoption,
        | 'candidate_name'
        | 'email'
        | 'phone'
        | 'housing'
        | 'candidate_notes'
        | 'status'
        | 'adopted_on'
    > & {
        coordinator_name: string | null;
        event_type?: 'aftercare' | 'return';
        contact_on?: string | null;
        returned_on?: string | null;
        return_reason?: string | null;
        received_by?: string | null;
    };
};
