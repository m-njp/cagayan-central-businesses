// Mirrors app/Data/SessionUser. Only these fields of the account reach the browser (P10).
export type SessionUser = {
    name: string;
};

// Mirrors app/Data/DemoLoginOption. Present only on a local machine's login page.
export type DemoLoginOption = {
    role: string;
    label: string;
};

export type Auth = {
    user: SessionUser | null;
};
