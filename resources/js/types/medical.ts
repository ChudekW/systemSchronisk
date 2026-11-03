export type MedicalRecord = {
    id: number;
    user_id: number | null;
    author_name: string;
    kind: string;
    title: string;
    occurred_on: string;
    performed_by: string | null;
    description: string;
    recommendations: string | null;
    weight_kg: string | null;
    correction_of_id: number | null;
    correction_reason: string | null;
    corrections_count: number;
    created_at: string;
    attachments: { id: number; name: string; size: number }[];
    care_task: {
        id: number;
        title: string;
        due_date: string;
        status: string;
    } | null;
};
