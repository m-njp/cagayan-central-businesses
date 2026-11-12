import { Head } from '@inertiajs/react';
import { SourceLink } from '@/components/source-link';
import { Surface } from '@/components/ui/surface';
import { PSA_CENSUS_RELEASE } from '@/lib/public-sources';
import type { FinanceCitation } from '@/types/economic-performance';
import { ReportLibrary } from './report-library';

export default function PublicSources({
    citations,
}: {
    citations: FinanceCitation[];
}) {
    return (
        <div className="flex flex-col gap-4 pt-4">
            <Head title="About the Data" />
            <h1 className="type-title">About the Data</h1>
            <div className="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <ReportLibrary citations={citations} />
                <div className="grid gap-4">
                    <Surface title="Government Finances" padded>
                        <p className="type-body">
                            Government finance figures are reviewed before
                            publication. Original reports retain their fiscal
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
