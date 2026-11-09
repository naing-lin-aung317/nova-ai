export type GenerationListItem = {
    id: number;
    content_type: string;
    language: string;
    length: string;
    status: string;
    topic: string;
    tone_label: string | null;
    credits_charged: number;
    can_delete: boolean;
    created_at: string | null;
    detail_url: string;
};

export type GenerationDetail = GenerationListItem & {
    audience: string | null;
    keywords: string | null;
    call_to_action: string | null;
    output_text: string | null;
    failure_reason: string | null;
    charged: boolean;
    no_charge: boolean;
    completed_at: string | null;
    can_edit: boolean;
    can_delete: boolean;
};

export type ToneOption = {
    id: number;
    name: string;
    description: string | null;
};

export type GenerationCreatePayload = {
    content_type: string;
    language: string;
    length: string;
    built_in_tone_id?: number;
    custom_tone_id?: number;
    topic: string;
    audience?: string;
    keywords?: string;
    call_to_action?: string;
};

export type StreamDoneEvent = {
    done: true;
    status: string;
    charged: boolean;
};
