import { Form } from '@inertiajs/react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
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
import { store, update } from '@/routes/public/wishes';
import type { SelectOption, WishPage } from '@/types/data';

type SpecificBusiness = { value: string; visible: boolean };

export function WishForm({
    page,
    editing,
    formToken,
    town,
    type,
    onChoose,
    specific,
    onSpecificChange,
    onBeforeSubmit,
    onEndEdit,
}: {
    page: WishPage;
    editing: boolean;
    formToken: string;
    town: string;
    type: string;
    onChoose: (selection: { town: string; type: string }) => void;
    specific: SpecificBusiness;
    onSpecificChange: (specific: SpecificBusiness) => void;
    onBeforeSubmit: () => void;
    onEndEdit: () => void;
}) {
    const wish = editing ? page.myWish : null;

    return (
        <div className="flex flex-col gap-5">
            {editing && <h2 className="type-heading">Edit Your Suggestion</h2>}
            <Form
                key={editing ? 'edit' : 'new'}
                {...(wish ? update.form(wish.id) : store.form())}
                options={{ preserveScroll: true }}
                onBefore={onBeforeSubmit}
                onSuccess={onEndEdit}
            >
                {({ processing, errors }) => {
                    const problem =
                        errors.wish ?? errors.form_token ?? errors.website;

                    return (
                        <FieldGroup className="gap-6">
                            <WishOption
                                id="town"
                                label="Town"
                                placeholder="Choose a town"
                                options={page.towns}
                                value={town}
                                error={errors.town}
                                required
                                onChange={(town) => onChoose({ town, type })}
                            />
                            <WishOption
                                id="type"
                                label="Business Type"
                                placeholder="Choose a business type"
                                options={page.types}
                                value={type}
                                error={errors.type}
                                onChange={(type) => onChoose({ town, type })}
                            />

                            <Button
                                type="button"
                                variant="link"
                                className="h-auto self-start p-0 text-left whitespace-normal"
                                aria-expanded={specific.visible}
                                aria-controls={
                                    specific.visible
                                        ? 'specific-business-field'
                                        : undefined
                                }
                                onClick={() =>
                                    onSpecificChange({
                                        visible: !specific.visible,
                                        value: specific.visible
                                            ? ''
                                            : specific.value,
                                    })
                                }
                            >
                                {specific.visible
                                    ? 'Use Business Type Only'
                                    : 'Not on the List? Specify a Business'}
                            </Button>
                            {specific.visible && (
                                <Field
                                    id="specific-business-field"
                                    data-invalid={!!errors.specific_business}
                                >
                                    <FieldLabel htmlFor="specific_business">
                                        Specific Business
                                    </FieldLabel>
                                    <Input
                                        id="specific_business"
                                        name="specific_business"
                                        value={specific.value}
                                        maxLength={page.specificBusinessMax}
                                        placeholder="Jollibee or Krispy Kreme"
                                        aria-invalid={
                                            !!errors.specific_business
                                        }
                                        onChange={(event) =>
                                            onSpecificChange({
                                                visible: true,
                                                value: event.target.value,
                                            })
                                        }
                                    />
                                    <FieldError>
                                        {errors.specific_business}
                                    </FieldError>
                                </Field>
                            )}

                            <Field data-invalid={!!errors.reason}>
                                <FieldLabel htmlFor="reason">
                                    Why Do You Wish to Have This in Your Area?
                                </FieldLabel>
                                <Textarea
                                    id="reason"
                                    name="reason"
                                    rows={3}
                                    maxLength={page.reasonMax}
                                    defaultValue={wish?.reason ?? ''}
                                    aria-invalid={!!errors.reason}
                                />
                                <FieldError>{errors.reason}</FieldError>
                            </Field>
                            <input
                                type="hidden"
                                name="form_token"
                                value={formToken}
                            />
                            <div aria-hidden="true" className="sr-only">
                                <label htmlFor="website">Website</label>
                                <input
                                    id="website"
                                    name="website"
                                    tabIndex={-1}
                                    autoComplete="off"
                                />
                            </div>

                            {problem && (
                                <Alert variant="destructive">
                                    <AlertDescription>
                                        {problem}
                                    </AlertDescription>
                                </Alert>
                            )}
                            <div className="flex flex-wrap gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {editing
                                        ? 'Save Changes'
                                        : 'Send Your Wishes'}
                                </Button>
                                {editing && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={onEndEdit}
                                    >
                                        Cancel
                                    </Button>
                                )}
                            </div>
                        </FieldGroup>
                    );
                }}
            </Form>
        </div>
    );
}

function WishOption({
    id,
    label,
    placeholder,
    options,
    value,
    error,
    onChange,
    required = false,
}: {
    id: string;
    label: string;
    placeholder: string;
    options: SelectOption[];
    value: string;
    error?: string;
    onChange: (value: string) => void;
    required?: boolean;
}) {
    return (
        <Field data-invalid={!!error}>
            <FieldLabel htmlFor={id} required={required}>
                {label}
            </FieldLabel>
            <Select
                name={id}
                value={value}
                onValueChange={onChange}
                required={required}
            >
                <SelectTrigger
                    id={id}
                    className="w-full"
                    aria-invalid={!!error}
                    aria-required={required}
                >
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <FieldError>{error}</FieldError>
        </Field>
    );
}
