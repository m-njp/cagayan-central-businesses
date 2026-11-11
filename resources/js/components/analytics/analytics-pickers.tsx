import { FieldHelp } from '@/components/capitol/field-help';
import { Field, FieldLabel } from '@/components/ui/field';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
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
type PickerField = 'town' | 'measure' | 'business' | 'presentation' | 'year';

const BUSINESS_SECTIONS = [
    { dimension: 'category', label: 'Business Categories' },
    { dimension: 'industry', label: 'Official Industries' },
] as const;

function Picker({
    id,
    label,
    value,
    options,
    sections,
    onChange,
    help,
    compact = false,
    hideLabel = false,
    placeholder = 'Not available',
}: {
    id: string;
    label: string;
    value: string;
    options?: Option[];
    sections?: { label: string; options: Option[] }[];
    help?: string;
    compact?: boolean;
    hideLabel?: boolean;
    placeholder?: string;
    onChange: (value: string) => void;
}) {
    const optionItem = (option: Option) => (
        <SelectItem
            key={option.value}
            value={option.value}
            textValue={option.label}
            disabled={option.disabled}
        >
            {option.label}
            {option.hint && (
                <span className="block type-data text-muted-foreground">
                    {option.hint}
                </span>
            )}
        </SelectItem>
    );

    return (
        <Field
            orientation={compact && !help ? 'horizontal' : 'vertical'}
            className="min-w-0 gap-1"
        >
            <div
                className={
                    hideLabel
                        ? 'sr-only'
                        : `flex items-center gap-1 ${compact && !help ? 'mr-auto shrink-0' : 'min-h-8'}`
                }
            >
                <FieldLabel
                    htmlFor={id}
                    className={compact ? 'text-xs' : undefined}
                >
                    {label}
                </FieldLabel>
                {help && <FieldHelp label={label}>{help}</FieldHelp>}
            </div>
            <Select
                value={value}
                onValueChange={onChange}
                disabled={!options?.length && !sections?.length}
            >
                <SelectTrigger
                    variant="quiet"
                    size={compact ? 'sm' : 'default'}
                    id={id}
                    className={
                        compact
                            ? `md:text-xs ${hideLabel ? 'max-w-full' : 'max-w-[65%]'}`
                            : 'w-full'
                    }
                    title={
                        compact
                            ? options?.find((option) => option.value === value)
                                  ?.label
                            : undefined
                    }
                >
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent align={compact && !hideLabel ? 'end' : 'start'}>
                    {options?.map(optionItem)}
                    {sections?.map((section) => (
                        <SelectGroup key={section.label}>
                            <SelectLabel>{section.label}</SelectLabel>
                            {section.options.map(optionItem)}
                        </SelectGroup>
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
    compact = false,
}: {
    catalog: AnalyticsCatalog;
    filters: AnalyticsFilters;
    fields: PickerField[];
    onChange: (patch: Partial<AnalyticsFilters>) => void;
    idPrefix: string;
    compact?: boolean;
}) {
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
                compact={compact}
                hideLabel={compact}
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
        business: () => {
            const business = catalog.businesses;
            if (!business || business.townCode !== filters.town) return null;

            const sections = BUSINESS_SECTIONS.map((section) => ({
                ...section,
                options: business.groups[section.dimension],
            })).filter((section) => section.options.length > 0);
            const selected = selectedBusiness(catalog, filters);

            if (compact) {
                return sections.map(({ dimension, options }) => (
                    <Picker
                        key={dimension}
                        id={`${idPrefix}-${dimension}`}
                        label={
                            dimension === 'category'
                                ? 'Business Category'
                                : 'Industry'
                        }
                        compact
                        placeholder="Choose"
                        value={
                            filters.dimension === dimension
                                ? (selected?.key ?? '')
                                : ''
                        }
                        options={options}
                        onChange={(group) => onChange({ dimension, group })}
                    />
                ));
            }

            return (
                <Picker
                    id={`${idPrefix}-business`}
                    label="Show Trends For"
                    help="Kita manages business categories. Official industries use the Philippine Standard Industrial Classification."
                    value={selected?.key ?? ''}
                    sections={sections}
                    onChange={(group) => {
                        const section = sections.find((item) =>
                            item.options.some(
                                (option) => option.value === group,
                            ),
                        );
                        if (section)
                            onChange({ dimension: section.dimension, group });
                    }}
                />
            );
        },
    };
    return (
        <>
            {fields.map((field) => {
                const picker = pickers[field]();
                return picker ? (
                    <div
                        key={field}
                        className={
                            compact
                                ? 'border-b pb-1 last:border-b-0'
                                : undefined
                        }
                    >
                        {picker}
                    </div>
                ) : null;
            })}
        </>
    );
}
