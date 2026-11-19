import type { ReactNode } from 'react';
import { useId } from 'react';
import { Field, FieldLabel } from '@/components/ui/field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Surface } from '@/components/ui/surface';

export function WorkspaceFilters({
    children,
    title = 'Filters',
    toolbar,
}: {
    children: ReactNode;
    title?: string;
    toolbar?: ReactNode;
}) {
    return (
        <Surface padded title={title} toolbar={toolbar} aria-label={title}>
            {children}
        </Surface>
    );
}

export function WorkspaceFilterLayout({
    results,
    children,
}: {
    results: ReactNode;
    children: ReactNode;
}) {
    return (
        <div className="grid min-w-0 items-start gap-4 lg:grid-cols-[minmax(0,1fr)_var(--workspace-panel-width)]">
            <div className="grid min-w-0 gap-2 lg:col-start-2 lg:row-start-1">
                {children}
            </div>
            <div className="min-w-0 lg:col-start-1 lg:row-start-1">
                {results}
            </div>
        </div>
    );
}

export function WorkspaceFilterSelect({
    label,
    value,
    options,
    onChange,
}: {
    label: string;
    value: string;
    options: readonly { value: string; label: string }[];
    onChange: (value: string) => void;
}) {
    const id = useId();

    return (
        <Field
            orientation="horizontal"
            className="min-w-0 gap-1.5 border-b pb-1 last:border-b-0"
        >
            <FieldLabel htmlFor={id} className="shrink-0 text-xs">
                {label}
            </FieldLabel>
            <Select value={value} onValueChange={onChange}>
                <SelectTrigger
                    id={id}
                    variant="quiet"
                    size="sm"
                    className="max-w-[60%] md:text-xs"
                    title={
                        options.find((option) => option.value === value)?.label
                    }
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent align="end">
                    <SelectItem value="all">All</SelectItem>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </Field>
    );
}
