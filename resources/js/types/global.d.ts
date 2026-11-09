import type { Auth, DemoLoginOption } from '@/types/auth';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            simulated: boolean;
            demoLogins?: DemoLoginOption[];
            [key: string]: unknown;
        };
    }
}
