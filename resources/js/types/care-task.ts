export type CareTask = {
    id: number;
    animal_id: number;
    title: string;
    description: string | null;
    assignee_id: number | null;
    assignee: { id: number; name: string; is_active: boolean } | null;
    animal?: { id: number; name: string; registration_number: string };
    due_date: string;
    status: string;
    priority: string;
    version: number;
};
export type CareOptions = {
    statuses: Record<string, string>;
    priorities: Record<string, string>;
    members: { id: number; name: string }[];
};
export type CareAnimal = {
    id: number;
    name: string;
    registration_number: string;
};
export type CareHistory = {
    id: number;
    author_name: string;
    created_at: string;
    note: string | null;
    snapshot: {
        title: string;
        description: string | null;
        due_date: string;
        priority: string;
        status: string;
        assignee_name: string;
    };
};
