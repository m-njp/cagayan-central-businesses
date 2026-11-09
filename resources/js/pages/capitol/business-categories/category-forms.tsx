import { useForm } from '@inertiajs/react';
import { CategoryChip } from '@/components/capitol/category-chip';
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
import { titleCase } from '@/lib/category-table';
import { CATEGORY_COLORS } from '@/lib/category-colors';
import {
    store as storeCategory,
    update as updateCategory,
} from '@/routes/capitol/categories';
import {
    store as storeType,
    update as updateType,
} from '@/routes/capitol/categories/types';
import type {
    BusinessCategories,
    BusinessCategoryRow,
    BusinessTypeRow,
} from '@/types/workspace';

function NameField({
    id,
    label,
    value,
    maxLength,
    taken,
    error,
    onChange,
}: {
    id: string;
    label: string;
    value: string;
    maxLength: number;
    taken: boolean;
    error?: string;
    onChange: (value: string) => void;
}) {
    const suggestion = titleCase(value);
    const message =
        error ??
        (taken ? `A ${label} with this name already exists.` : undefined);
    return (
        <Field data-invalid={!!message}>
            <FieldLabel htmlFor={id} required>
                Name
            </FieldLabel>
            <Input
                id={id}
                required
                maxLength={maxLength}
                value={value}
                aria-invalid={!!message}
                onChange={(event) => onChange(event.target.value)}
            />
            {value.trim() !== '' && suggestion !== value.trim() && (
                <p className="type-meta">
                    Title Case:{' '}
                    <button
                        type="button"
                        className="underline underline-offset-2"
                        onClick={() => onChange(suggestion)}
                    >
                        {suggestion}
                    </button>
                </p>
            )}
            <FieldError>{message}</FieldError>
        </Field>
    );
}

export function BusinessTypeForm({
    type,
    categories,
    onSaved,
}: {
    type: BusinessTypeRow | null;
    categories: BusinessCategories;
    onSaved: () => void;
}) {
    const form = useForm({
        name: type?.name ?? '',
        industry: type?.industryCode ?? '',
        category: type?.category ?? '',
    });
    const taken = categories.types.some(
        (item) =>
            item.code !== type?.code &&
            item.name.toLowerCase() === form.data.name.trim().toLowerCase(),
    );
    return (
        <form
            className="flex flex-col gap-4 px-4 pb-6"
            onSubmit={(event) => {
                event.preventDefault();
                const options = { preserveScroll: true, onSuccess: onSaved };
                if (type) form.put(updateType(type.code).url, options);
                else form.post(storeType().url, options);
            }}
        >
            <NameField
                id="business-type-name"
                label="Business Type"
                value={form.data.name}
                maxLength={categories.typeNameMax}
                taken={taken}
                error={form.errors.name}
                onChange={(value) => form.setData('name', value)}
            />
            <Field data-invalid={!!form.errors.category}>
                <FieldLabel htmlFor="business-type-category" required>
                    Category
                </FieldLabel>
                <Select
                    value={form.data.category}
                    onValueChange={(value) => form.setData('category', value)}
                >
                    <SelectTrigger
                        id="business-type-category"
                        className="w-full"
                    >
                        <SelectValue placeholder="Choose a Category" />
                    </SelectTrigger>
                    <SelectContent>
                        {categories.categories.map((category) => (
                            <SelectItem
                                key={category.code}
                                value={category.code}
                            >
                                <CategoryChip
                                    name={category.name}
                                    color={category.color}
                                />
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <FieldError>{form.errors.category}</FieldError>
            </Field>
            <Field data-invalid={!!form.errors.industry}>
                <FieldLabel htmlFor="business-type-industry" required>
                    Industry
                </FieldLabel>
                <Select
                    value={form.data.industry}
                    onValueChange={(value) => form.setData('industry', value)}
                >
                    <SelectTrigger
                        id="business-type-industry"
                        className="w-full"
                    >
                        <SelectValue placeholder="Choose an Industry" />
                    </SelectTrigger>
                    <SelectContent>
                        {categories.industries.map((industry) => (
                            <SelectItem
                                key={industry.value}
                                value={industry.value}
                            >
                                {industry.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <FieldError>{form.errors.industry}</FieldError>
            </Field>
            <Button
                type="submit"
                disabled={form.processing || taken}
                className="w-fit"
            >
                {form.processing && <Spinner />}
                {type ? 'Save Business Type' : 'Add Business Type'}
            </Button>
        </form>
    );
}

export function BusinessCategoryForm({
    category,
    categories,
    onSaved,
}: {
    category: BusinessCategoryRow | null;
    categories: BusinessCategories;
    onSaved: () => void;
}) {
    const form = useForm({
        name: category?.name ?? '',
        color: category?.color ?? 1,
    });
    const taken = categories.categories.some(
        (item) =>
            item.code !== category?.code &&
            item.name.toLowerCase() === form.data.name.trim().toLowerCase(),
    );
    return (
        <form
            className="flex flex-col gap-4 px-4 pb-6"
            onSubmit={(event) => {
                event.preventDefault();
                const options = { preserveScroll: true, onSuccess: onSaved };
                if (category)
                    form.put(updateCategory(category.code).url, options);
                else {
                    form.transform(({ name }) => ({ name }));
                    form.post(storeCategory().url, options);
                }
            }}
        >
            <NameField
                id="business-category-name"
                label="Category"
                value={form.data.name}
                maxLength={categories.categoryNameMax}
                taken={taken}
                error={form.errors.name}
                onChange={(value) => form.setData('name', value)}
            />
            {category && (
                <fieldset className="flex flex-col gap-2">
                    <legend className="type-label">Color</legend>
                    <div
                        role="radiogroup"
                        aria-label="Color"
                        className="flex flex-wrap gap-2"
                    >
                        {CATEGORY_COLORS.slice(0, categories.colors).map(
                            (color, index) => (
                                <button
                                    key={color.name}
                                    type="button"
                                    role="radio"
                                    aria-checked={form.data.color === index + 1}
                                    aria-label={color.name}
                                    title={color.name}
                                    onClick={() =>
                                        form.setData('color', index + 1)
                                    }
                                    className={`size-8 rounded-full ${color.dot} ${form.data.color === index + 1 ? 'ring-2 ring-foreground ring-offset-2 ring-offset-background' : ''}`}
                                />
                            ),
                        )}
                    </div>
                    <FieldError>{form.errors.color}</FieldError>
                </fieldset>
            )}
            {category && form.data.name.trim() !== '' && (
                <CategoryChip
                    name={form.data.name.trim()}
                    color={form.data.color}
                />
            )}
            <Button
                type="submit"
                disabled={form.processing || taken}
                className="w-fit"
            >
                {form.processing && <Spinner />}
                {category ? 'Save Category' : 'Add Category'}
            </Button>
        </form>
    );
}
