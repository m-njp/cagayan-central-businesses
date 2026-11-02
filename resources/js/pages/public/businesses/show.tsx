import { SimulatedLabel } from '@/components/privacy/simulated-label';
import { Head, Link, router } from '@inertiajs/react';
import { Achievements } from '@/components/business-badges';
import { CategoryIcon } from '@/components/category-icon';
import { DetailField, DetailGrid } from '@/components/detail-field';
import { LazyBusinessMap } from '@/components/map/lazy-business-map';
import { ProfileSection } from '@/components/profile-section';
import { formatDate } from '@/lib/format';
import { icons } from '@/lib/icons';
import { index, show } from '@/routes/public/businesses';

type Profile = {
    publicId: string;
    name: string;
    type: string;
    category: { code: string; name: string };
    barangay: string;
    barangayCode: string;
    town: string;
    townCode: string;
    description: string;
    yearEstablished: number;
    businessPhone: string | null;
    businessEmail: string | null;
    birVerifiedOn: string | null;
    permitStatus: string;
    permitValidUntil: string | null;
    streak: number;
    milestone: number | null;
    location: { lng: number; lat: number } | null;
    nearby: {
        id: string;
        name: string;
        category: { code: string; name: string };
    }[];
    neighbors: { id: string; point: [number, number] }[];
};

const PERMIT: Record<string, string> = {
    valid: 'Valid until',
    expiring: 'Expiring',
    expired: 'Expired',
};

export default function PublicBusiness({ business }: { business: Profile }) {
    const Location = icons.location;
    const Back = icons.back;
    const Bir = icons.birRegistered;
    const link = 'underline underline-offset-2';

    return (
        <>
            <Head title={business.name} />
            <header className="flex flex-col gap-1">
                <Link
                    href={index({
                        query: {
                            town: business.townCode,
                            barangay: business.barangayCode,
                        },
                    })}
                    className="inline-flex w-fit items-center gap-1 rounded-sm type-meta hover:text-foreground hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <Back className="size-4" aria-hidden="true" />
                    Businesses
                </Link>
                <h1 className="type-title">{business.name}</h1>
                <p className="inline-flex items-center gap-1.5 type-body text-muted-foreground">
                    <Location className="size-4 shrink-0" aria-hidden="true" />
                    {business.barangay}, {business.town}
                </p>
            </header>

            <div className="grid items-start gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                <div className="flex min-w-0 flex-col gap-3">
                    <ProfileSection title="About">
                        <div className="flex justify-end">
                            <SimulatedLabel />
                        </div>
                        <p className="type-body">{business.description}</p>
                        <DetailGrid>
                            <DetailField label="Business Type">
                                {business.type}
                            </DetailField>
                            <DetailField label="Category">
                                {business.category.name}
                            </DetailField>
                            <DetailField label="Established">
                                <span className="font-mono tabular-nums">
                                    {business.yearEstablished}
                                </span>
                            </DetailField>
                            <DetailField label="Renewal Streak">
                                {business.streak === 0 ? (
                                    'Not renewed this year'
                                ) : (
                                    <span>
                                        <span className="font-mono tabular-nums">
                                            {business.streak}
                                        </span>{' '}
                                        {business.streak === 1
                                            ? 'year'
                                            : 'years in a row'}
                                    </span>
                                )}
                            </DetailField>
                            <DetailField label="Business Permit">
                                {business.permitValidUntil &&
                                    `${PERMIT[business.permitStatus] ?? 'Valid until'} ${formatDate(business.permitValidUntil)}`}
                            </DetailField>
                            <DetailField label="BIR Registration">
                                {business.birVerifiedOn && (
                                    <span className="inline-flex items-center gap-1.5">
                                        <Bir
                                            className="size-4 shrink-0 text-primary"
                                            aria-hidden="true"
                                        />
                                        Certificate checked{' '}
                                        {formatDate(business.birVerifiedOn)}
                                    </span>
                                )}
                            </DetailField>
                        </DetailGrid>
                    </ProfileSection>

                    <Achievements
                        milestone={business.milestone}
                        since={business.yearEstablished}
                        streak={business.streak}
                        bir={business.birVerifiedOn}
                    />

                    <ProfileSection title="Contact">
                        <DetailGrid>
                            <DetailField label="Phone">
                                {business.businessPhone && (
                                    <a
                                        href={`tel:${business.businessPhone}`}
                                        className={link}
                                    >
                                        {business.businessPhone}
                                    </a>
                                )}
                            </DetailField>
                            <DetailField label="Email">
                                {business.businessEmail && (
                                    <a
                                        href={`mailto:${business.businessEmail}`}
                                        className={link}
                                    >
                                        {business.businessEmail}
                                    </a>
                                )}
                            </DetailField>
                        </DetailGrid>
                    </ProfileSection>

                    {business.nearby.length > 0 && (
                        <ProfileSection title={`More in ${business.barangay}`}>
                            <ul className="grid gap-x-4 gap-y-1 sm:grid-cols-2">
                                {business.nearby.map((near) => (
                                    <li key={near.id}>
                                        <Link
                                            href={show(near.id)}
                                            className="flex items-center gap-2 rounded-sm py-1.5 type-body hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                        >
                                            <CategoryIcon
                                                {...near.category}
                                                className="size-4"
                                            />
                                            <span className="truncate">
                                                {near.name}
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </ProfileSection>
                    )}
                </div>

                <ProfileSection title="Location" className="lg:sticky lg:top-6">
                    <div className="h-[24rem] lg:h-[32rem]">
                        <LazyBusinessMap
                            townCode={business.townCode}
                            barangay={business.barangayCode}
                            businesses={business.neighbors}
                            pin={{
                                point: business.location,
                                category: business.category.code,
                            }}
                            onSelect={(id) => router.visit(show(id))}
                            label={`Map of ${business.name} and other businesses in ${business.barangay}`}
                        />
                    </div>
                </ProfileSection>
            </div>
        </>
    );
}
