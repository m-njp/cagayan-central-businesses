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
import type { BusinessForm } from '@/types/workspace';

export type DocumentDraft = {
    kind: string;
    file: File | null;
    issued_on: string;
    expires_on: string;
};

const dates = [
    { key: 'issued_on', id: 'issued', label: 'Issued On' },
    { key: 'expires_on', id: 'expires', label: 'Expires On' },
] as const;

export function BusinessDocuments({
    existing,
    choices,
    limit,
    documents,
    error,
    onChange,
}: {
    existing: BusinessForm['documents'];
    choices: BusinessForm['choices']['documents'];
    limit: number;
    documents: DocumentDraft[];
    error: (key: string) => string | undefined;
    onChange: (documents: DocumentDraft[]) => void;
}) {
    const update = (index: number, patch: Partial<DocumentDraft>) =>
        onChange(
            documents.map((document, position) =>
                position === index ? { ...document, ...patch } : document,
            ),
        );

    return (
        <fieldset className="flex flex-col gap-3 rounded-sm border bg-card p-4">
            <legend className="px-1 type-label">Documents</legend>
            {existing.length > 0 && (
                <ul className="flex flex-col gap-1 type-meta">
                    {existing.map((document, index) => (
                        <li key={index}>
                            {document.label}: {document.name}
                        </li>
                    ))}
                </ul>
            )}
            {documents.map((document, index) => {
                const kindError = error(`documents.${index}.kind`);
                const fileError = error(`documents.${index}.file`);
                return (
                    <div
                        key={index}
                        className="grid gap-3 border-t pt-3 sm:grid-cols-2"
                    >
                        <Field data-invalid={!!kindError}>
                            <FieldLabel
                                htmlFor={`document-kind-${index}`}
                                required
                            >
                                Document
                            </FieldLabel>
                            <Select
                                value={document.kind}
                                onValueChange={(kind) =>
                                    update(index, { kind })
                                }
                            >
                                <SelectTrigger
                                    id={`document-kind-${index}`}
                                    className="w-full"
                                    aria-invalid={!!kindError}
                                >
                                    <SelectValue placeholder="Choose" />
                                </SelectTrigger>
                                <SelectContent>
                                    {choices.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <FieldError>{kindError}</FieldError>
                        </Field>
                        <Field data-invalid={!!fileError}>
                            <FieldLabel
                                htmlFor={`document-file-${index}`}
                                required
                            >
                                File
                            </FieldLabel>
                            <Input
                                id={`document-file-${index}`}
                                type="file"
                                required
                                accept=".pdf,.jpg,.jpeg,.png"
                                aria-invalid={!!fileError}
                                onChange={(event) =>
                                    update(index, {
                                        file: event.target.files?.[0] ?? null,
                                    })
                                }
                            />
                            <FieldError>{fileError}</FieldError>
                        </Field>
                        {dates.map(({ key, id, label }) => (
                            <Field key={key}>
                                <FieldLabel htmlFor={`document-${id}-${index}`}>
                                    {label}
                                </FieldLabel>
                                <Input
                                    id={`document-${id}-${index}`}
                                    type="date"
                                    value={document[key]}
                                    onChange={(event) =>
                                        update(index, {
                                            [key]: event.target.value,
                                        })
                                    }
                                />
                            </Field>
                        ))}
                        <Button
                            type="button"
                            variant="ghost"
                            className="justify-self-start"
                            onClick={() =>
                                onChange(
                                    documents.filter(
                                        (_, position) => position !== index,
                                    ),
                                )
                            }
                        >
                            Remove
                        </Button>
                    </div>
                );
            })}
            {documents.length + existing.length < limit && (
                <Button
                    type="button"
                    variant="outline"
                    className="self-start"
                    onClick={() =>
                        onChange([
                            ...documents,
                            {
                                kind: '',
                                file: null,
                                issued_on: '',
                                expires_on: '',
                            },
                        ])
                    }
                >
                    Add a Document
                </Button>
            )}
        </fieldset>
    );
}
