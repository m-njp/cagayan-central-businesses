import { Form, Head, usePage } from '@inertiajs/react';
import { KitaLogo } from '@/components/kita-logo';
import { lazy, Suspense } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';

const ProvinceMap = lazy(() => import('@/components/map/province-map'));

// Posts to its own URL, not a Wayfinder import, so the secret path never enters the bundle (decisions.md, D20).
// The demo buttons exist only on a local machine (D33).
export default function Login() {
    const { url, props } = usePage();

    return (
        <>
            <Head title="Sign In">
                <meta name="robots" content="noindex, nofollow" />
            </Head>
            <main className="grid min-h-screen bg-background lg:grid-cols-2">
                <div className="hidden flex-col justify-between gap-10 bg-brand p-10 text-brand-foreground lg:flex">
                    <KitaLogo
                        variant="stacked"
                        reverse
                        label={props.name}
                        className="w-28"
                    />
                    <Suspense fallback={null}>
                        <ProvinceMap
                            label="Map of the towns of Cagayan"
                            className="max-h-[70vh] w-full"
                        />
                    </Suspense>
                </div>
                <div className="flex items-center justify-center px-4 py-12">
                    <Card className="w-full max-w-sm">
                        <CardHeader>
                            <CardTitle className="type-title">
                                Sign In
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {typeof props.notice === 'string' && (
                                <p role="status" className="mb-4 type-body">
                                    {props.notice}
                                </p>
                            )}
                            <Form
                                action={url}
                                method="post"
                                resetOnSuccess={['password']}
                                resetOnError={['password']}
                            >
                                {({ processing, errors }) => (
                                    <FieldGroup className="gap-6">
                                        <Field data-invalid={!!errors.email}>
                                            <FieldLabel htmlFor="email">
                                                Email Address
                                            </FieldLabel>
                                            <Input
                                                id="email"
                                                type="email"
                                                name="email"
                                                required
                                                autoFocus
                                                autoComplete="username"
                                                aria-invalid={!!errors.email}
                                            />
                                            <FieldError>
                                                {errors.email}
                                            </FieldError>
                                        </Field>
                                        <Field data-invalid={!!errors.password}>
                                            <FieldLabel htmlFor="password">
                                                Password
                                            </FieldLabel>
                                            <Input
                                                id="password"
                                                type="password"
                                                name="password"
                                                required
                                                autoComplete="current-password"
                                                aria-invalid={!!errors.password}
                                            />
                                            <FieldError>
                                                {errors.password}
                                            </FieldError>
                                        </Field>
                                        <Button
                                            type="submit"
                                            className="w-full"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            Sign In
                                        </Button>
                                    </FieldGroup>
                                )}
                            </Form>
                            {!!props.demoLogins?.length && (
                                <>
                                    <Separator className="my-6" />
                                    <div className="grid gap-3">
                                        {props.demoLogins.map(
                                            ({ role, label }) => (
                                                <Form
                                                    key={role}
                                                    action={`${url.split('?')[0]}/demo/${role}`}
                                                    method="post"
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            type="submit"
                                                            variant="outline"
                                                            className="w-full"
                                                            disabled={
                                                                processing
                                                            }
                                                        >
                                                            {processing && (
                                                                <Spinner />
                                                            )}
                                                            {label}
                                                        </Button>
                                                    )}
                                                </Form>
                                            ),
                                        )}
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}
