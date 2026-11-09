import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';

const messages: Record<number, { title: string; message: string }> = {
    403: {
        title: 'Access Denied',
        message: 'You do not have access to this page.',
    },
    404: {
        title: 'Page Not Found',
        message: 'The page you requested does not exist.',
    },
    419: {
        title: 'Session Expired',
        message:
            'Your session has expired. Return and sign in again before submitting changes.',
    },
    429: {
        title: 'Too Many Requests',
        message: 'Please wait a moment before trying again.',
    },
    500: {
        title: 'Something Went Wrong',
        message: 'We could not complete your request. Please try again later.',
    },
    503: {
        title: 'Temporarily Unavailable',
        message:
            'The service is temporarily unavailable. Please try again later.',
    },
};
export default function Status({ status }: { status: number }) {
    const message = messages[status] ?? messages[500];
    return (
        <>
            <Head title={message.title} />
            <main className="flex min-h-screen items-center justify-center bg-background px-gutter text-foreground">
                <section className="flex w-full max-w-measure flex-col items-start gap-4 rounded-sm border bg-card p-6">
                    <p className="type-label text-muted-foreground">{status}</p>
                    <h1 className="type-heading">{message.title}</h1>
                    <p className="type-body">{message.message}</p>
                    <div className="flex gap-3">
                        <Button
                            variant="outline"
                            onClick={() => window.location.reload()}
                        >
                            Try Again
                        </Button>
                        <Button asChild>
                            <Link href={home()}>Public Home</Link>
                        </Button>
                    </div>
                </section>
            </main>
        </>
    );
}
