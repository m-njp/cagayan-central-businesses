import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { FinanceReportList } from '@/components/analytics/source-line';
import { SourceLink } from '@/components/source-link';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Surface } from '@/components/ui/surface';
import { municipalityCitationReports } from '@/lib/performance-format';
import { PSA_CENSUS_RELEASE } from '@/lib/public-sources';
import type { FinanceCitation } from '@/types/economic-performance';
import { SourceArchiveDownload } from './source-archive-download';

export default function PublicSources({
    citations,
}: {
    citations: FinanceCitation[];
}) {
    const municipalities = municipalityCitationReports(citations);
    const [search, setSearch] = useState('');
    const filtered = municipalities.filter(({ town }) =>
        town.toLocaleLowerCase().includes(search.trim().toLocaleLowerCase()),
    );

    return (
        <div className="flex flex-col gap-4 pt-4">
            <Head title="About the Data" />
            <h1 className="type-title">About the Data</h1>
            <div className="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <Surface
                    id="coa-reports"
                    title="COA Audit Reports"
                    className="scroll-mt-4"
                    toolbar={
                        municipalities.length > 0 && <SourceArchiveDownload />
                    }
                >
                    {municipalities.length === 0 ? (
                        <p className="p-panel type-body text-muted-foreground">
                            Verified reports are not available.
                        </p>
                    ) : (
                        <>
                            <div className="border-b p-panel">
                                <Field
                                    orientation="horizontal"
                                    className="max-w-sm gap-2"
                                >
                                    <FieldLabel htmlFor="report-town">
                                        Municipality
                                    </FieldLabel>
                                    <Input
                                        id="report-town"
                                        type="search"
                                        value={search}
                                        onChange={(event) =>
                                            setSearch(event.target.value)
                                        }
                                        placeholder="Search municipalities"
                                    />
                                </Field>
                            </div>
                            <div className="divide-y">
                                {filtered.map(({ town, years }) => (
                                    <section
                                        key={town}
                                        className="px-panel pt-2"
                                    >
                                        <h3 className="type-item">{town}</h3>
                                        <FinanceReportList
                                            citations={years}
                                            showIcons
                                        />
                                    </section>
                                ))}
                                {filtered.length === 0 && (
                                    <p
                                        role="status"
                                        className="p-panel type-body text-muted-foreground"
                                    >
                                        No matching municipalities.
                                    </p>
                                )}
                            </div>
                        </>
                    )}
                </Surface>
                <div className="grid gap-4">
                    <Surface title="Government Finances" padded>
                        <p className="type-body">
                            The figures come from verified municipal and city
                            audit reports. Each report keeps its original fiscal
                            year.
                        </p>
                    </Surface>
                    <Surface title="Population" padded>
                        <p className="type-body">
                            <SourceLink href={PSA_CENSUS_RELEASE}>
                                PSA 2024 Census of Population
                            </SourceLink>
                        </p>
                    </Surface>
                    <Surface title="Map Boundaries" padded>
                        <p className="type-body">
                            Simplified 2023 PSGC boundaries from{' '}
                            <SourceLink href="https://github.com/faeldon/philippines-json-maps">
                                Philippines JSON Maps
                            </SourceLink>
                            .
                        </p>
                        <p className="type-body">
                            Basemap data from{' '}
                            <SourceLink href="https://www.openstreetmap.org/copyright">
                                OpenStreetMap contributors
                            </SourceLink>
                            .
                        </p>
                    </Surface>
                </div>
            </div>
        </div>
    );
}
