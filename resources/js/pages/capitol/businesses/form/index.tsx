import { Head, useForm } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { PageHeader } from '@/components/capitol/page-header';
import type { Point } from '@/components/map/business-map';
import { LazyBusinessMap } from '@/components/map/lazy-business-map';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { store, update } from '@/routes/capitol/businesses';
import type { BusinessForm } from '@/types/workspace';
import { BusinessDocuments } from './business-documents';
import type { DocumentDraft } from './business-documents';

function Group({ title, children }: { title: string; children: ReactNode }) {
    return (
        <fieldset className="grid gap-4 rounded-sm border bg-card p-4 sm:grid-cols-2">
            <legend className="px-1 type-label">{title}</legend>
            {children}
        </fieldset>
    );
}

export default function BusinessFormPage({
    form: page,
}: {
    form: BusinessForm;
}) {
    const values = page.values ?? {};
    const text = (key: string) =>
        values[key] == null ? '' : String(values[key]);
    const firstTown =
        page.barangays.find((b) => b.value === values.barangay)?.town ??
        page.towns[0]?.value ??
        '';
    const form = useForm({
        submit: false,
        business_name: text('business_name'),
        category: text('category'),
        business_type: text('business_type'),
        description: text('description'),
        year_established: text('year_established'),
        is_storefront: values.is_storefront === false ? '0' : '1',
        town: firstTown,
        barangay: text('barangay'),
        street_address: text('street_address'),
        lng: text('lng'),
        lat: text('lat'),
        business_phone: text('business_phone'),
        business_email: text('business_email'),
        owner_name: text('owner_name'),
        contact_number: text('contact_number'),
        ownership_type: text('ownership_type'),
        size_category: text('size_category'),
        employee_count: text('employee_count'),
        registration_body: text('registration_body'),
        registration_number: text('registration_number'),
        registration_valid_until: text('registration_valid_until'),
        tin: text('tin'),
        rdo_code: text('rdo_code'),
        permit_number: text('permit_number'),
        permit_valid_from: text('permit_valid_from'),
        permit_valid_until: text('permit_valid_until'),
        documents: [] as DocumentDraft[],
    });
    const { data, setData, errors } = form;
    const error = (key: string) =>
        (errors as Record<string, string | undefined>)[key];
    const point: Point | null =
        data.lng && data.lat
            ? { lng: Number(data.lng), lat: Number(data.lat) }
            : null;
    type TextKey = Exclude<keyof typeof data, 'documents' | 'submit'>;
    const types =
        page.categories.find((c) => c.code === data.category)?.types ?? [];

    // A pin dropped in another barangay of the town selects that barangay.
    const pin = ({ lng, lat }: Point, barangay: string | null) => {
        form.setData((previous) => ({
            ...previous,
            lng: lng.toFixed(6),
            lat: lat.toFixed(6),
            barangay: barangay ?? previous.barangay,
        }));
    };

    const submit = (send: boolean) => {
        form.transform((current) => {
            const { category: _category, town: _town, ...rest } = current;
            void _category;
            void _town;
            return {
                ...rest,
                submit: send ? '1' : '0',
                ...(page.publicId ? { _method: 'put' } : {}),
            };
        });
        form.post(page.publicId ? update(page.publicId).url : store().url, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const field = (
        key: TextKey,
        label: string,
        control: ReactNode,
        required = true,
        wide = false,
    ) => (
        <Field
            data-invalid={!!error(key)}
            className={wide ? 'sm:col-span-2' : undefined}
        >
            <FieldLabel htmlFor={key} required={required}>
                {label}
            </FieldLabel>
            {control}
            <FieldError>{error(key)}</FieldError>
        </Field>
    );
    const input = (
        key: TextKey,
        label: string,
        props: React.ComponentProps<typeof Input> = {},
        required = true,
        wide = false,
    ) =>
        field(
            key,
            label,
            <Input
                id={key}
                required={required}
                value={data[key]}
                aria-invalid={!!error(key)}
                onChange={(event) => setData(key, event.target.value)}
                {...props}
            />,
            required,
            wide,
        );
    const choose = (
        key: TextKey,
        label: string,
        options: { value: string; label: string }[],
        onChange?: (value: string) => void,
    ) =>
        field(
            key,
            label,
            <Select
                value={data[key]}
                onValueChange={(value) =>
                    onChange ? onChange(value) : setData(key, value)
                }
            >
                <SelectTrigger
                    id={key}
                    className="w-full"
                    aria-invalid={!!error(key)}
                >
                    <SelectValue placeholder="Choose" />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>,
        );
    return (
        <>
            <Head title={page.publicId ? 'Edit Business' : 'Add a Business'} />
            <PageHeader
                title={page.publicId ? 'Edit Business' : 'Add a Business'}
            />
            {page.reviewNote && (
                <Alert>
                    <AlertTitle>Returned for Resubmission</AlertTitle>
                    <AlertDescription>{page.reviewNote}</AlertDescription>
                </Alert>
            )}
            <form
                className="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_26rem]"
                onSubmit={(event) => {
                    event.preventDefault();
                    submit(true);
                }}
            >
                <div className="flex min-w-0 flex-col gap-4">
                    <Group title="Business">
                        {input(
                            'business_name',
                            'Business Name',
                            { maxLength: page.limits.businessNameMax },
                            true,
                            true,
                        )}
                        {choose(
                            'category',
                            'Category',
                            page.categories.map((c) => ({
                                value: c.code,
                                label: c.name,
                            })),
                            (value) =>
                                form.setData((previous) => ({
                                    ...previous,
                                    category: value,
                                    business_type: '',
                                })),
                        )}
                        {choose('business_type', 'Business Type', types)}
                        {field(
                            'description',
                            'Description',
                            <Textarea
                                id="description"
                                required
                                maxLength={page.limits.descriptionMax}
                                value={data.description}
                                aria-invalid={!!error('description')}
                                onChange={(event) =>
                                    setData('description', event.target.value)
                                }
                            />,
                            true,
                            true,
                        )}
                        {input('year_established', 'Year Established', {
                            inputMode: 'numeric',
                            maxLength: 4,
                        })}
                        {choose('is_storefront', 'Location Type', [
                            { value: '1', label: 'Storefront' },
                            { value: '0', label: 'Home Based' },
                        ])}
                    </Group>

                    <Group title="Business Contact">
                        {input(
                            'business_phone',
                            'Phone',
                            { type: 'tel', maxLength: 20 },
                            false,
                        )}
                        {input(
                            'business_email',
                            'Email',
                            { type: 'email', maxLength: page.limits.emailMax },
                            false,
                        )}
                    </Group>

                    <Group title="Owner">
                        {input('owner_name', 'Owner Name', {
                            maxLength: page.limits.ownerNameMax,
                        })}
                        {input('contact_number', 'Owner Contact', {
                            type: 'tel',
                            maxLength: 20,
                        })}
                        {choose(
                            'ownership_type',
                            'Ownership',
                            page.choices.ownership,
                        )}
                        {choose('size_category', 'Size', page.choices.size)}
                        {input('employee_count', 'Employees', {
                            inputMode: 'numeric',
                        })}
                    </Group>

                    <Group title="Registration">
                        {choose(
                            'registration_body',
                            'Registered With',
                            page.choices.registration,
                        )}
                        {input('registration_number', 'Registration Number', {
                            maxLength: page.limits.registrationNumberMax,
                        })}
                        {input(
                            'registration_valid_until',
                            'Registration Valid Until',
                            { type: 'date' },
                            false,
                        )}
                        {input(
                            'tin',
                            'TIN',
                            {
                                placeholder:
                                    text('tin_masked') || '000-000-000-000',
                                maxLength: 17,
                            },
                            !page.publicId,
                        )}
                        {input('rdo_code', 'RDO Code', {
                            placeholder: '013',
                            maxLength: 4,
                        })}
                    </Group>

                    <Group title="Business Permit">
                        {input(
                            'permit_number',
                            'Permit Number',
                            { maxLength: page.limits.permitNumberMax },
                            false,
                            true,
                        )}
                        {input(
                            'permit_valid_from',
                            'Valid From',
                            { type: 'date' },
                            false,
                        )}
                        {input(
                            'permit_valid_until',
                            'Valid Until',
                            { type: 'date' },
                            false,
                        )}
                    </Group>

                    <BusinessDocuments
                        existing={page.documents}
                        choices={page.choices.documents}
                        limit={page.limits.documentsMax}
                        documents={data.documents}
                        error={error}
                        onChange={(documents) =>
                            setData('documents', documents)
                        }
                    />
                </div>

                <aside className="flex flex-col gap-4 lg:sticky lg:top-6">
                    <fieldset className="grid gap-4 rounded-sm border bg-card p-4">
                        <legend className="px-1 type-label">Location</legend>
                        {choose('town', 'Municipality', page.towns, (value) =>
                            form.setData((previous) => ({
                                ...previous,
                                town: value,
                                barangay: '',
                            })),
                        )}
                        {choose(
                            'barangay',
                            'Barangay',
                            page.barangays.filter(
                                (barangay) => barangay.town === data.town,
                            ),
                        )}
                        {input('street_address', 'Street Address', {
                            maxLength: page.limits.addressMax,
                        })}
                        <Field data-invalid={!!(error('lng') || error('lat'))}>
                            <FieldLabel required>Map Pin</FieldLabel>
                            <div className="h-72">
                                <LazyBusinessMap
                                    townCode={data.town}
                                    barangay={data.barangay || null}
                                    pin={{ point }}
                                    label="Business location picker"
                                    onPick={pin}
                                />
                            </div>
                            <FieldError>
                                {error('lng') ?? error('lat')}
                            </FieldError>
                        </Field>
                    </fieldset>
                    <div className="flex flex-wrap gap-2">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Submit for Review
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={form.processing}
                            onClick={() => submit(false)}
                        >
                            Save Draft
                        </Button>
                    </div>
                </aside>
            </form>
        </>
    );
}
