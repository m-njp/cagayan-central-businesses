import { FieldHelp } from '@/components/capitol/field-help';
import { Field, FieldLabel } from '@/components/ui/field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    ALL_TOWNS,
    measureCovers,
    selectedBusiness,
    selectedYear,
} from '@/lib/analytics';
import type {
    AnalyticsCatalog,
    AnalyticsFilters,
} from '@/types/economic-performance';

type Option = {
    value: string;
    label: string;
    disabled?: boolean;
    hint?: string;
};
type PickerField =
    | 'town'
    | 'measure'
    | 'dimension'
    | 'group'
    | 'presentation'
    | 'year';

const DIMENSIONS: Option[] = [
    { value: 'industry', label: 'Industry' },
    { value: 'category', label: 'Business Category' },
];

function Picker({
    id,
    label,
    value,
    options,
    onChange,
    help,
}: {
    id: string;
    label: string;
    value: string;
    options: Option[];
    help?: string;
    onChange: (value: string) => void;
}) {
    return (
        <Field className="min-w-0 gap-1">
            <div className="flex min-h-8 items-center gap-1">
                <FieldLabel htmlFor={id}>{label}</FieldLabel>
                {help && <FieldHelp label={label}>{help}</FieldHelp>}
            </div>
            <Select
                value={value}
                onValueChange={onChange}
                disabled={options.length === 0}
            >
                <SelectTrigger id={id} className="min-h-10 w-full">
                    <SelectValue placeholder="Not available" />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem
                            key={option.value}
                            value={option.value}
                            disabled={option.disabled}
                        >
                            {option.label}
                            {option.hint && (
                                <span className="type-data text-muted-foreground">
                                    {option.hint}
                                </span>
                            )}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </Field>
    );
}

/** The analytics selections, shared by the public Economy page and the Capitol Analytics page. */
export function AnalyticsPickers({
    catalog,
    filters,
    fields,
    onChange,
    idPrefix,
}: {
    catalog: AnalyticsCatalog;
    filters: AnalyticsFilters;
    fields: PickerField[];
    onChange: (patch: Partial<AnalyticsFilters>) => void;
    idPrefix: string;
}) {
    const business = selectedBusiness(catalog, filters);
    const finance = catalog.finances[filters.town]?.[filters.measure];
    const year = selectedYear(catalog, filters);
    const topic = catalog.guide.topics.find(
        (item) => item.id === filters.measure,
    );
    const pickers: Record<PickerField, () => React.ReactNode> = {
        town: () => (
            <Picker
                id={`${idPrefix}-town`}
                label="Municipality"
                value={filters.town}
                options={catalog.towns}
                onChange={(town) => onChange({ town })}
            />
        ),
        // The Executive Summary always shows its fixed sections, so it has no Show picker.
        measure: () =>
            filters.presentation !== 'statement' && (
                <Picker
                    id={`${idPrefix}-measure`}
                    label="Show"
                    help={topic?.summary}
                    value={filters.measure}
                    options={catalog.measures.map((measure) => {
                        const covered = measureCovers(measure, filters.town);
                        // The sum of reporting towns also has the measure, but it is not a town to name.
                        const towns = (measure.towns ?? []).filter(
                            (code) => code !== ALL_TOWNS,
                        );
                        const only =
                            towns.length === 1
                                ? catalog.towns.find(
                                      (town) => town.value === towns[0],
                                  )?.label
                                : null;
                        return {
                            value: measure.value,
                            label: measure.label,
                            disabled: !covered,
                            hint: covered
                                ? undefined
                                : only
                                  ? ` ${only} Only`
                                  : ' Not Loaded for This Municipality',
                        };
                    })}
                    onChange={(measure) => onChange({ measure })}
                />
            ),
        presentation: () => (
            <Picker
                id={`${idPrefix}-presentation`}
                label="View"
                value={filters.presentation}
                options={[
                    { value: 'statement', label: 'Executive Summary' },
                    { value: 'summary', label: 'Yearly Summary' },
                    { value: 'trends', label: 'Trends' },
                ]}
                onChange={(presentation) =>
                    onChange({
                        presentation:
                            presentation as AnalyticsFilters['presentation'],
                    })
                }
            />
        ),
        year: () =>
            filters.presentation === 'summary' && (
                <Picker
                    id={`${idPrefix}-year`}
                    label="Fiscal Year"
                    value={year === null ? '' : String(year)}
                    options={(finance?.rows ?? [])
                        .map((row) => ({
                            value: String(row.year),
                            label: String(row.year),
                        }))
                        .reverse()}
                    onChange={(value) => onChange({ year: Number(value) })}
                />
            ),
        dimension: () =>
            business && (
                <Picker
                    id={`${idPrefix}-dimension`}
                    label="Group By"
                    value={filters.dimension}
                    options={DIMENSIONS}
                    onChange={(dimension) =>
                        onChange({
                            dimension:
                                dimension as AnalyticsFilters['dimension'],
                        })
                    }
                />
            ),
        group: () =>
            business && (
                <Picker
                    id={`${idPrefix}-group`}
                    label={
                        filters.dimension === 'category'
                            ? 'Business Category'
                            : 'Industry'
                    }
                    value={business.key}
                    options={business.groups}
                    onChange={(group) => onChange({ group })}
                />
            ),
    };
    return (
        <>
            {fields.map((field) => {
                const picker = pickers[field]();
                return picker ? <div key={field}>{picker}</div> : null;
            })}
        </>
    );
}
