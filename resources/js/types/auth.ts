export type UserRole = 'trainer' | 'client' | 'admin';

export type Plan = 'free' | 'pro';

export type User = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    avatar?: string | null;
    email_verified_at: string | null;
};

export type TrainerProfile = {
    id: number;
    business_name: string;
    currency: string;
    plan: Plan;
    onboarded: boolean;
    logo_url: string | null;
};

export type Auth = {
    user: User | null;
    trainerProfile: TrainerProfile | null;
};

export type FlashData = {
    success?: string | null;
    error?: string | null;
};
