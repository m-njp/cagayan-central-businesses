import { Head, useForm } from '@inertiajs/react';
import { PageHeader } from '@/components/capitol/page-header';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/capitol/profile';
import type { AccountProfile } from '@/types/workspace';

export default function Profile({ profile }: { profile: AccountProfile }) {
    const form = useForm({
        display_name: profile.displayName,
        birthday_month: profile.birthdayMonth?.toString() ?? '',
        birthday_day: profile.birthdayDay?.toString() ?? '',
    });
    const errors = Object.entries(form.errors);
    return (
        <>
            <Head title="Profile" />
            <PageHeader title="Profile" />
            <form
                className="flex max-w-measure flex-col gap-4 rounded-sm border bg-card p-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.patch(update().url, { preserveScroll: true });
                }}
            >
                {errors.length > 0 && (
                    <div
                        role="alert"
                        className="rounded-sm border border-destructive p-3 type-body"
                    >
                        <p>Check the following fields.</p>
                        <ul className="mt-2 list-inside list-disc">
                            {errors.map(([key, error]) => (
                                <li key={key}>
                                    <a href={'#' + key} className="underline">
                                        {error}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
                <Field data-invalid={!!form.errors.display_name}>
                    <FieldLabel required htmlFor="display_name">
                        Display Name
                    </FieldLabel>
                    <Input
                        id="display_name"
                        name="display_name"
                        autoComplete="name"
                        value={form.data.display_name}
                        maxLength={profile.displayNameMax}
                        required
                        aria-invalid={!!form.errors.display_name}
                        aria-describedby="name-error"
                        onChange={(e) =>
                            form.setData('display_name', e.target.value)
                        }
                    />
                    <FieldError id="name-error">
                        {form.errors.display_name}
                    </FieldError>
                </Field>
                <dl className="grid gap-3 border-y py-3 sm:grid-cols-2">
                    <div>
                        <dt className="type-label text-muted-foreground">
                            Email Address
                        </dt>
                        <dd className="mt-1 type-body break-words">
                            {profile.email}
                        </dd>
                    </div>
                    <div>
                        <dt className="type-label text-muted-foreground">
                            Role
                        </dt>
                        <dd className="mt-1 type-body">{profile.role}</dd>
                    </div>
                </dl>
                <fieldset className="flex flex-col gap-3">
                    <legend className="type-item">Birthday (Optional)</legend>
                    <div className="grid grid-cols-2 gap-3">
                        <Field data-invalid={!!form.errors.birthday_month}>
                            <FieldLabel htmlFor="birthday_month">
                                Month
                            </FieldLabel>
                            <Input
                                id="birthday_month"
                                name="birthday_month"
                                type="number"
                                min={1}
                                max={12}
                                step={1}
                                inputMode="numeric"
                                value={form.data.birthday_month}
                                aria-invalid={!!form.errors.birthday_month}
                                aria-describedby="month-error"
                                onChange={(e) =>
                                    form.setData(
                                        'birthday_month',
                                        e.target.value,
                                    )
                                }
                            />
                            <FieldError id="month-error">
                                {form.errors.birthday_month}
                            </FieldError>
                        </Field>
                        <Field data-invalid={!!form.errors.birthday_day}>
                            <FieldLabel htmlFor="birthday_day">Day</FieldLabel>
                            <Input
                                id="birthday_day"
                                name="birthday_day"
                                type="number"
                                min={1}
                                max={31}
                                step={1}
                                inputMode="numeric"
                                value={form.data.birthday_day}
                                aria-invalid={!!form.errors.birthday_day}
                                aria-describedby="day-error"
                                onChange={(e) =>
                                    form.setData('birthday_day', e.target.value)
                                }
                            />
                            <FieldError id="day-error">
                                {form.errors.birthday_day}
                            </FieldError>
                        </Field>
                    </div>
                </fieldset>
                <Button
                    type="submit"
                    disabled={form.processing}
                    className="w-fit"
                >
                    {form.processing && <Spinner />}Save Profile
                </Button>
            </form>
        </>
    );
}
