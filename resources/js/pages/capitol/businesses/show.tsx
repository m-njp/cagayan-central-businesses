import { Head, Link, useForm } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Achievements } from '@/components/business-badges';
import { DetailField, DetailGrid } from '@/components/detail-field';
import { LazyBusinessMap } from '@/components/map/lazy-business-map';
import { ProfileSection } from '@/components/profile-section';
import {
    PermitBadge,
    RecordBadge,
    StatusBadge,
} from '@/components/registry-badges';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatDate, formatDateTime, NOT_AVAILABLE } from '@/lib/format';
import { icons } from '@/lib/icons';
import {
    BUSINESS_STATUS_DOTS,
    BUSINESS_STATUS_LABELS,
    RECORD_STATUS,
} from '@/lib/registry';
import { edit } from '@/routes/capitol/businesses';
import { show as document } from '@/routes/capitol/businesses/documents';
import { store as review } from '@/routes/capitol/businesses/reviews';
import type { BusinessProfile } from '@/types/workspace';

function Facts({ items }: { items: [string, ReactNode][] }) {
    return (
        <DetailGrid>
            {items.map(([term, value]) => (
                <DetailField key={term} label={term}>
                    {value}
                </DetailField>
            ))}
        </DetailGrid>
    );
}

export default function BusinessShow({
    business,
}: {
    business: BusinessProfile;
}) {
    const { admin } = business;
    const DocumentIcon = icons.document;
    const form = useForm({ decision: '', note: '' });
    const decide = (decision: 'approved' | 'returned') => {
        form.transform((data) => ({ ...data, decision }));
        form.post(review(business.publicId).url, { preserveScroll: true });
    };

    return (
        <>
            <Head title={business.name} />
            <header className="flex flex-col gap-3 rounded-sm border bg-card p-3 sm:flex-row sm:items-start">
                <div className="flex min-w-0 flex-1 flex-col gap-1">
                    <h1 className="type-heading">{business.name}</h1>
                    <p className="type-body text-muted-foreground">
                        {business.barangay}, {business.town}
                    </p>
                    <div className="flex flex-wrap items-center gap-2 empty:hidden">
                        <RecordBadge status={business.recordStatus} />
                        <PermitBadge
                            status={business.permitStatus}
                            validUntil={business.permitValidUntil}
                        />
                    </div>
                </div>
                {business.can.edit && (
                    <Button asChild variant="outline" className="self-start">
                        <Link href={edit(business.publicId)}>Edit</Link>
                    </Button>
                )}
            </header>

            {business.recordStatus === 'returned' && admin?.reviewNote && (
                <Alert>
                    <AlertTitle>Returned for Resubmission</AlertTitle>
                    <AlertDescription>{admin.reviewNote}</AlertDescription>
                </Alert>
            )}

            <div className="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div className="flex min-w-0 flex-col gap-4">
                    <ProfileSection title="About">
                        <p className="type-body">{business.description}</p>
                        <Facts
                            items={[
                                ['Business Type', business.type],
                                ['Category', business.category.name],
                                ['Established', business.yearEstablished],
                                [
                                    'Location Type',
                                    business.isStorefront
                                        ? 'Storefront'
                                        : 'Home based',
                                ],
                                [
                                    'BIR Registration',
                                    business.birVerifiedOn &&
                                        `Certificate checked ${formatDate(business.birVerifiedOn)}`,
                                ],
                            ]}
                        />
                    </ProfileSection>

                    <Achievements
                        milestone={business.milestone}
                        since={business.yearEstablished}
                        streak={business.streak}
                        bir={business.birVerifiedOn}
                    />

                    <div className="grid gap-4 xl:grid-cols-2">
                        {admin && (
                            <ProfileSection title="Owner and Registration">
                                <Facts
                                    items={[
                                        ['Owner', admin.ownerName],
                                        ['Owner Contact', admin.ownerContact],
                                        ['Ownership', admin.ownership],
                                        [
                                            'Size',
                                            `${admin.size}, ${admin.employees} employees`,
                                        ],
                                        [
                                            admin.registrationBody ??
                                                'Registration',
                                            admin.registrationNumber,
                                        ],
                                        [
                                            'Registration Valid Until',
                                            admin.registrationValidUntil &&
                                                formatDate(
                                                    admin.registrationValidUntil,
                                                ),
                                        ],
                                        ['TIN', admin.tin],
                                        ['RDO', admin.rdoCode],
                                    ]}
                                />
                            </ProfileSection>
                        )}
                        <ProfileSection title="Permits">
                            {business.permits.length === 0 ? (
                                <p className="type-body text-muted-foreground">
                                    No permit on file.
                                </p>
                            ) : (
                                <ul className="flex max-h-56 flex-col divide-y overflow-y-auto">
                                    {business.permits.map((permit) => (
                                        <li
                                            key={permit.year}
                                            className="flex items-baseline justify-between gap-3 py-2 first:pt-0"
                                        >
                                            <span className="font-mono type-body tabular-nums">
                                                {permit.year}
                                            </span>
                                            {permit.number && (
                                                <span className="truncate font-mono type-meta">
                                                    {permit.number}
                                                </span>
                                            )}
                                            <span className="type-meta">
                                                Until{' '}
                                                {formatDate(permit.validUntil)}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </ProfileSection>
                    </div>

                    {admin && (
                        <ProfileSection title="Documents">
                            {business.documents.length === 0 ? (
                                <p className="type-body text-muted-foreground">
                                    No documents uploaded.
                                </p>
                            ) : (
                                <ul className="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                    {business.documents.map((file) => (
                                        <li key={file.publicId}>
                                            <a
                                                href={
                                                    document({
                                                        business:
                                                            business.publicId,
                                                        document: file.publicId,
                                                    }).url
                                                }
                                                target="_blank"
                                                rel="noreferrer"
                                                className="flex items-start gap-3 rounded-sm border p-3 hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                            >
                                                <DocumentIcon
                                                    className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                                    aria-hidden="true"
                                                />
                                                <span className="flex min-w-0 flex-col">
                                                    <span className="type-body font-medium">
                                                        {file.label}
                                                    </span>
                                                    <span className="truncate type-meta">
                                                        {file.issuedOn
                                                            ? `Issued ${formatDate(file.issuedOn)}`
                                                            : file.name}
                                                    </span>
                                                    {file.expiresOn && (
                                                        <span className="type-meta">
                                                            Expires{' '}
                                                            {formatDate(
                                                                file.expiresOn,
                                                            )}
                                                        </span>
                                                    )}
                                                </span>
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </ProfileSection>
                    )}

                    <ProfileSection title="Registration History">
                        {business.history.length === 0 ? (
                            <p className="type-body text-muted-foreground">
                                No yearly records yet.
                            </p>
                        ) : (
                            <ol className="flex flex-wrap gap-2">
                                {business.history.map((year) => (
                                    <li
                                        key={year.year}
                                        className="flex flex-col gap-1 rounded-sm border px-3 py-2"
                                    >
                                        <span className="font-mono type-body tabular-nums">
                                            {year.year}
                                        </span>
                                        <StatusBadge
                                            dot={
                                                BUSINESS_STATUS_DOTS[
                                                    year.status
                                                ] ?? ''
                                            }
                                            label={
                                                BUSINESS_STATUS_LABELS[
                                                    year.status
                                                ] ?? year.status
                                            }
                                        />
                                    </li>
                                ))}
                            </ol>
                        )}
                    </ProfileSection>
                </div>

                <aside className="flex flex-col gap-4">
                    <ProfileSection title="Location">
                        <div className="h-56">
                            <LazyBusinessMap
                                townCode={business.townCode}
                                barangay={business.barangayCode}
                                pin={{
                                    point: business.location,
                                    category: business.category.code,
                                }}
                                label={`Location of ${business.name}`}
                            />
                        </div>
                        <p className="type-body">
                            {admin?.streetAddress ?? NOT_AVAILABLE}
                        </p>
                    </ProfileSection>
                    <ProfileSection title="Business Contact">
                        <Facts
                            items={[
                                ['Phone', business.businessPhone],
                                ['Email', business.businessEmail],
                            ]}
                        />
                    </ProfileSection>
                    <ProfileSection title="Review">
                        <Facts
                            items={[
                                [
                                    'Status',
                                    RECORD_STATUS[business.recordStatus],
                                ],
                                [
                                    'Submitted',
                                    business.submittedAt &&
                                        `${formatDateTime(business.submittedAt)}${business.submittedBy ? ` by ${business.submittedBy}` : ''}`,
                                ],
                                [
                                    'Reviewed',
                                    business.reviewedAt &&
                                        `${formatDateTime(business.reviewedAt)}${business.reviewedBy ? ` by ${business.reviewedBy}` : ''}`,
                                ],
                            ]}
                        />
                        {business.can.review && (
                            <form
                                className="flex flex-col gap-3 border-t pt-3"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    decide('returned');
                                }}
                            >
                                <Button
                                    type="button"
                                    disabled={form.processing}
                                    onClick={() => decide('approved')}
                                >
                                    {form.processing && <Spinner />}
                                    Approve and Publish
                                </Button>
                                <Field data-invalid={!!form.errors.note}>
                                    <FieldLabel htmlFor="review-note" required>
                                        Reason for Return
                                    </FieldLabel>
                                    <Textarea
                                        id="review-note"
                                        required
                                        value={form.data.note}
                                        maxLength={business.reviewNoteMax}
                                        aria-invalid={!!form.errors.note}
                                        onChange={(event) =>
                                            form.setData(
                                                'note',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <FieldError>{form.errors.note}</FieldError>
                                </Field>
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={form.processing}
                                >
                                    Return for Resubmission
                                </Button>
                            </form>
                        )}
                    </ProfileSection>
                </aside>
            </div>
        </>
    );
}
